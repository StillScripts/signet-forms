<?php

use App\Enums\FormFieldType;
use App\Livewire\PublicFormPage;
use App\Models\Form;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use Livewire\Livewire;

function makePublicSchema(array $fields = [], ?string $title = null): array
{
    return ['pages' => [[
        'id' => fake()->uuid(),
        'title' => $title,
        'heading' => null,
        'subheading' => null,
        'submit_button_text' => null,
        'fields' => $fields,
    ]]];
}

function makePublicField(string $type, string $key, array $data = []): array
{
    $fieldType = FormFieldType::from($type);

    return [
        'type' => $type,
        'key' => $key,
        'sort' => 0,
        'data' => array_merge($fieldType->defaultData(), $data),
    ];
}

function setUpPublicForm(array $fields = [], bool $published = true): array
{
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'schema' => makePublicSchema($fields),
        'is_published' => $published,
    ]);

    return [$team, $project, $form];
}

test('published form is accessible via public url', function () {
    [$team, , $form] = setUpPublicForm();

    $this->get(route('forms.show', ['team' => $team, 'formSlug' => $form->slug]))
        ->assertOk();
});

test('unpublished form returns 404', function () {
    [$team, , $form] = setUpPublicForm(published: false);

    $this->get(route('forms.show', ['team' => $team, 'formSlug' => $form->slug]))
        ->assertNotFound();
});

test('nonexistent form returns 404', function () {
    $team = Team::factory()->create();

    $this->get(route('forms.show', ['team' => $team, 'formSlug' => 'does-not-exist']))
        ->assertNotFound();
});

test('form from different team returns 404', function () {
    [$teamA] = setUpPublicForm();
    $teamB = Team::factory()->create();

    $form = Form::factory()->create([
        'project_id' => Project::factory()->create(['team_id' => $teamA->id])->id,
        'is_published' => true,
        'schema' => makePublicSchema(),
    ]);

    $this->get(route('forms.show', ['team' => $teamB, 'formSlug' => $form->slug]))
        ->assertNotFound();
});

test('public form page displays form name', function () {
    $fields = [makePublicField('text-input', 'name', ['label' => 'Full Name'])];
    [$team, , $form] = setUpPublicForm($fields);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->assertSee($form->name);
});

test('public form page displays form description', function () {
    [$team, , $form] = setUpPublicForm();
    $form->update(['description' => 'Please fill out this form.']);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->assertSee('Please fill out this form.');
});

test('can submit a single page form', function () {
    $fields = [
        makePublicField('text-input', 'name', ['label' => 'Name']),
        makePublicField('text-input', 'email', ['label' => 'Email']),
    ];
    [$team, , $form] = setUpPublicForm($fields);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.name', 'Jane Smith')
        ->set('data.email', 'jane@example.com')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Submission::where('form_id', $form->id)->count())->toBe(1);

    $submission = Submission::where('form_id', $form->id)->first();
    expect($submission->data['name'])->toBe('Jane Smith')
        ->and($submission->data['email'])->toBe('jane@example.com');
});

test('required field validation is enforced', function () {
    $fields = [
        makePublicField('text-input', 'name', ['label' => 'Name', 'is_required' => true]),
    ];
    [$team, , $form] = setUpPublicForm($fields);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->call('submit')
        ->assertHasErrors(['data.name' => 'required']);

    expect(Submission::where('form_id', $form->id)->count())->toBe(0);
});

test('empty form can be submitted', function () {
    [$team, , $form] = setUpPublicForm();

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Submission::where('form_id', $form->id)->count())->toBe(1);
});

test('success page shows default message', function () {
    [$team, , $form] = setUpPublicForm();

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->call('submit')
        ->assertSee('Thank you!')
        ->assertSee('Your response has been recorded.');
});

test('success page shows custom heading and message', function () {
    [$team, , $form] = setUpPublicForm();
    $form->update([
        'success_heading' => 'All done!',
        'success_message' => 'We will get back to you soon.',
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->call('submit')
        ->assertSee('All done!')
        ->assertSee('We will get back to you soon.');
});

test('multi page form is detected', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => ['pages' => [
            [
                'id' => fake()->uuid(),
                'title' => 'Step 1',
                'heading' => null,
                'subheading' => null,
                'submit_button_text' => null,
                'fields' => [makePublicField('text-input', 'name')],
            ],
            [
                'id' => fake()->uuid(),
                'title' => 'Step 2',
                'heading' => null,
                'subheading' => null,
                'submit_button_text' => null,
                'fields' => [makePublicField('text-input', 'email')],
            ],
        ]],
    ]);

    $component = Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ]);

    expect($component->instance()->isMultiPage())->toBeTrue();
});
