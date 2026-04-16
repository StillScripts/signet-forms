<?php

use App\Enums\FormFieldType;
use App\Livewire\PublicFormPage;
use App\Mail\SubmissionResumeLink;
use App\Models\Form;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

function makeResumeField(string $type, string $key, array $data = []): array
{
    $fieldType = FormFieldType::from($type);

    return [
        'type' => $type,
        'key' => $key,
        'sort' => 0,
        'data' => array_merge($fieldType->defaultData(), $data),
    ];
}

function makeResumePage(array $fields = [], ?string $title = null): array
{
    return [
        'id' => fake()->uuid(),
        'title' => $title,
        'heading' => null,
        'subheading' => null,
        'submit_button_text' => null,
        'fields' => $fields,
    ];
}

function setUpResumeForm(array $pages = []): array
{
    if (empty($pages)) {
        $pages = [makeResumePage([
            makeResumeField('text-input', 'name', ['label' => 'Name']),
            makeResumeField('text-input', 'email', ['label' => 'Email']),
        ])];
    }

    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'schema' => ['pages' => $pages],
        'is_published' => true,
    ]);

    return [$team, $project, $form];
}

test('save progress creates a draft submission with a resume token', function () {
    Mail::fake();
    [$team, , $form] = setUpResumeForm();

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.name', 'Jane Doe')
        ->set('saveEmail', 'jane@example.com')
        ->call('saveProgress')
        ->assertSet('saveEmailSent', true);

    $submission = Submission::where('form_id', $form->id)->first();

    expect($submission)->not->toBeNull()
        ->and($submission->is_draft)->toBeTrue()
        ->and($submission->resume_token)->not->toBeEmpty()
        ->and(strlen($submission->resume_token))->toBe(64)
        ->and($submission->resume_token_expires_at?->isFuture())->toBeTrue()
        ->and($submission->data['name'])->toBe('Jane Doe')
        ->and($submission->respondent_email)->toBe('jane@example.com');
});

test('save progress skips required field validation', function () {
    Mail::fake();
    $pages = [makeResumePage([
        makeResumeField('text-input', 'name', ['label' => 'Name', 'is_required' => true]),
    ])];
    [$team, , $form] = setUpResumeForm($pages);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('saveEmail', 'jane@example.com')
        ->call('saveProgress')
        ->assertHasNoErrors();

    expect(Submission::where('form_id', $form->id)->where('is_draft', true)->count())->toBe(1);
});

test('save progress requires a valid email', function () {
    Mail::fake();
    [$team, , $form] = setUpResumeForm();

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('saveEmail', 'not-an-email')
        ->call('saveProgress')
        ->assertHasErrors(['saveEmail']);

    expect(Submission::where('form_id', $form->id)->count())->toBe(0);
});

test('save progress queues resume link email', function () {
    Mail::fake();
    [$team, , $form] = setUpResumeForm();

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('saveEmail', 'jane@example.com')
        ->call('saveProgress');

    Mail::assertQueued(SubmissionResumeLink::class, function (SubmissionResumeLink $mail) {
        return $mail->hasTo('jane@example.com');
    });
});

test('saving twice updates the same draft', function () {
    Mail::fake();
    [$team, , $form] = setUpResumeForm();

    $component = Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.name', 'First Save')
        ->set('saveEmail', 'jane@example.com')
        ->call('saveProgress');

    $submissionId = $component->get('submissionId');

    $component
        ->set('data.name', 'Second Save')
        ->call('saveProgress');

    expect(Submission::where('form_id', $form->id)->count())->toBe(1)
        ->and(Submission::find($submissionId)->data['name'])->toBe('Second Save');
});

test('resume route loads draft data into the form', function () {
    Mail::fake();
    [$team, , $form] = setUpResumeForm();

    $submission = Submission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Jane', 'email' => 'jane@example.com'],
        'is_draft' => true,
        'respondent_email' => 'jane@example.com',
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDays(7),
    ]);

    $this->get(route('forms.resume', [
        'team' => $team->slug,
        'formSlug' => $form->slug,
        'token' => $submission->resume_token,
    ]))->assertOk();

    $component = Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
        'token' => $submission->resume_token,
    ]);

    expect($component->get('submissionId'))->toBe($submission->id)
        ->and($component->get('data')['name'])->toBe('Jane')
        ->and($component->get('data')['email'])->toBe('jane@example.com');
});

test('expired resume token returns 404', function () {
    [$team, , $form] = setUpResumeForm();

    $submission = Submission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Jane'],
        'is_draft' => true,
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->subDay(),
    ]);

    $this->get(route('forms.resume', [
        'team' => $team->slug,
        'formSlug' => $form->slug,
        'token' => $submission->resume_token,
    ]))->assertNotFound();
});

test('unknown resume token returns 404', function () {
    [$team, , $form] = setUpResumeForm();

    $this->get(route('forms.resume', [
        'team' => $team->slug,
        'formSlug' => $form->slug,
        'token' => str_repeat('a', 64),
    ]))->assertNotFound();
});

test('token for a different form returns 404', function () {
    [$teamA, , $formA] = setUpResumeForm();
    [, , $formB] = setUpResumeForm();

    $submission = Submission::create([
        'form_id' => $formA->id,
        'data' => [],
        'is_draft' => true,
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDay(),
    ]);

    $this->get(route('forms.resume', [
        'team' => $teamA->slug,
        'formSlug' => $formB->slug,
        'token' => $submission->resume_token,
    ]))->assertNotFound();
});

test('submitting after resume promotes draft to completed submission', function () {
    Mail::fake();
    [$team, , $form] = setUpResumeForm();

    $submission = Submission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Jane'],
        'is_draft' => true,
        'respondent_email' => 'jane@example.com',
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDays(7),
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
        'token' => $submission->resume_token,
    ])
        ->set('data.name', 'Jane Doe')
        ->set('data.email', 'jane@example.com')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission->refresh();

    expect(Submission::where('form_id', $form->id)->count())->toBe(1)
        ->and($submission->is_draft)->toBeFalse()
        ->and($submission->resume_token)->toBeNull()
        ->and($submission->resume_token_expires_at)->toBeNull()
        ->and($submission->resume_page_index)->toBeNull()
        ->and($submission->data['name'])->toBe('Jane Doe');
});

test('completed scope filters out drafts', function () {
    $form = Form::factory()->create();

    Submission::factory()->create(['form_id' => $form->id, 'is_draft' => false]);
    Submission::factory()->create(['form_id' => $form->id, 'is_draft' => true]);

    expect(Submission::query()->count())->toBe(2)
        ->and(Submission::query()->completed()->count())->toBe(1);
});

test('multi page resume opens at saved page', function () {
    Mail::fake();
    $pages = [
        makeResumePage([makeResumeField('text-input', 'name', ['label' => 'Name'])], 'Step 1'),
        makeResumePage([makeResumeField('text-input', 'email', ['label' => 'Email'])], 'Step 2'),
        makeResumePage([makeResumeField('textarea', 'message', ['label' => 'Message'])], 'Step 3'),
    ];
    [$team, , $form] = setUpResumeForm($pages);

    $submission = Submission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Jane', 'email' => 'jane@example.com'],
        'is_draft' => true,
        'resume_page_index' => 1,
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDay(),
    ]);

    $component = Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
        'token' => $submission->resume_token,
    ]);

    expect($component->get('resumedPageIndex'))->toBe(1)
        ->and($component->instance()->isResumed())->toBeTrue();
});

test('is resumable returns true when draft token is valid', function () {
    $submission = Submission::factory()->make([
        'is_draft' => true,
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDay(),
    ]);

    expect($submission->isResumable())->toBeTrue();
});

test('is resumable returns false when token expired', function () {
    $submission = Submission::factory()->make([
        'is_draft' => true,
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->subDay(),
    ]);

    expect($submission->isResumable())->toBeFalse();
});

test('is resumable returns false when not draft', function () {
    $submission = Submission::factory()->make([
        'is_draft' => false,
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDay(),
    ]);

    expect($submission->isResumable())->toBeFalse();
});

test('resume link mail builds url with token', function () {
    [$team, , $form] = setUpResumeForm();

    $submission = Submission::create([
        'form_id' => $form->id,
        'data' => [],
        'is_draft' => true,
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDays(7),
    ]);

    $mail = new SubmissionResumeLink($submission->load('form.project.team'));
    $content = $mail->content();

    $expectedUrl = route('forms.resume', [
        'team' => $team->slug,
        'formSlug' => $form->slug,
        'token' => $submission->resume_token,
    ]);

    expect($content->with['resumeUrl'])->toBe($expectedUrl)
        ->and($content->with['form']->id)->toBe($form->id);
});

test('resume link mail subject references form name', function () {
    [, , $form] = setUpResumeForm();
    $form->update(['name' => 'Patient Intake']);

    $submission = Submission::create([
        'form_id' => $form->id,
        'data' => [],
        'is_draft' => true,
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDay(),
    ]);

    $mail = new SubmissionResumeLink($submission->load('form.project.team'));
    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('Patient Intake');
});

test('exports skip draft submissions', function () {
    $form = Form::factory()->create();

    Submission::factory()->create(['form_id' => $form->id, 'is_draft' => false]);
    Submission::factory()->create(['form_id' => $form->id, 'is_draft' => true]);

    $query = Submission::query()
        ->completed()
        ->where('form_id', $form->id);

    expect($query->count())->toBe(1);
});

test('resume token is cleared on submit for fresh submission', function () {
    Mail::fake();
    [$team, , $form] = setUpResumeForm();

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.name', 'Jane')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->first();

    expect($submission->is_draft)->toBeFalse()
        ->and($submission->resume_token)->toBeNull();
});

test('save progress is rate limited after repeated attempts', function () {
    Mail::fake();
    RateLimiter::clear('public-form:save:minute:'.sha1('127.0.0.1|jane@example.com'));
    RateLimiter::clear('public-form:save:hour:'.sha1('127.0.0.1|jane@example.com'));

    [$team, , $form] = setUpResumeForm();

    $component = Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('saveEmail', 'jane@example.com');

    for ($i = 0; $i < 5; $i++) {
        $component->call('saveProgress')->assertHasNoErrors('saveEmail');
    }

    $component->call('saveProgress')->assertHasErrors('saveEmail');

    Mail::assertQueuedCount(5);
    expect(Submission::where('form_id', $form->id)->count())->toBe(1);
});

test('respondent email is not overwritten by a second save', function () {
    Mail::fake();
    RateLimiter::clear('public-form:save:minute:'.sha1('127.0.0.1|first@example.com'));
    RateLimiter::clear('public-form:save:hour:'.sha1('127.0.0.1|first@example.com'));
    RateLimiter::clear('public-form:save:minute:'.sha1('127.0.0.1|second@example.com'));
    RateLimiter::clear('public-form:save:hour:'.sha1('127.0.0.1|second@example.com'));

    [$team, , $form] = setUpResumeForm();

    $component = Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('saveEmail', 'first@example.com')
        ->call('saveProgress');

    $component
        ->set('saveEmail', 'second@example.com')
        ->call('saveProgress');

    $submission = Submission::where('form_id', $form->id)->first();

    expect($submission->respondent_email)->toBe('first@example.com');
});

test('submit on already completed submission shows already submitted state', function () {
    Mail::fake();
    [$team, , $form] = setUpResumeForm();

    $submission = Submission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Jane'],
        'is_draft' => true,
        'respondent_email' => 'jane@example.com',
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDay(),
    ]);

    $component = Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
        'token' => $submission->resume_token,
    ]);

    // Simulate a stale tab: complete the submission behind the component's back.
    $submission->update([
        'is_draft' => false,
        'resume_token' => null,
        'resume_token_expires_at' => null,
    ]);

    $component
        ->call('submit')
        ->assertSet('submitted', false)
        ->assertSet('alreadySubmitted', true);

    expect(Submission::where('form_id', $form->id)->count())->toBe(1)
        ->and($submission->fresh()->is_draft)->toBeFalse();
});

test('forms table submissions count excludes drafts', function () {
    $form = Form::factory()->create();

    Submission::factory()->count(2)->create(['form_id' => $form->id, 'is_draft' => false]);
    Submission::factory()->count(3)->create(['form_id' => $form->id, 'is_draft' => true]);

    $result = Form::query()
        ->withCount([
            'submissions' => fn ($q) => $q->where('is_draft', false),
        ])
        ->where('id', $form->id)
        ->first();

    expect($result->submissions_count)->toBe(2);
});

test('single page form resume shows welcome back banner', function () {
    Mail::fake();
    [$team, , $form] = setUpResumeForm();

    $submission = Submission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Jane'],
        'is_draft' => true,
        'respondent_email' => 'jane@example.com',
        'resume_token' => Submission::generateResumeToken(),
        'resume_token_expires_at' => now()->addDay(),
    ]);

    $component = Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
        'token' => $submission->resume_token,
    ]);

    expect($component->get('resumedPageIndex'))->toBeNull()
        ->and($component->get('wasResumedFromToken'))->toBeTrue()
        ->and($component->instance()->isResumed())->toBeTrue();

    $component->assertSee('Welcome back');
});
