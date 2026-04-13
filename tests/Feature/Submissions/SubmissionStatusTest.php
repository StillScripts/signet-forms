<?php

use App\Enums\FormFieldType;
use App\Enums\SubmissionStatus;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Filament\Resources\Submissions\Pages\ViewSubmission;
use App\Livewire\PublicFormPage;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

// --- SubmissionStatus Enum ---

test('pending can transition to in_review and archived', function () {
    $transitions = SubmissionStatus::Pending->transitions();

    expect($transitions)->toBe([SubmissionStatus::InReview, SubmissionStatus::Archived]);
});

test('in_review can transition to approved rejected and archived', function () {
    $transitions = SubmissionStatus::InReview->transitions();

    expect($transitions)->toBe([SubmissionStatus::Approved, SubmissionStatus::Rejected, SubmissionStatus::Archived]);
});

test('approved can only transition to archived', function () {
    expect(SubmissionStatus::Approved->transitions())->toBe([SubmissionStatus::Archived]);
});

test('rejected can only transition to archived', function () {
    expect(SubmissionStatus::Rejected->transitions())->toBe([SubmissionStatus::Archived]);
});

test('archived is terminal with no transitions', function () {
    expect(SubmissionStatus::Archived->transitions())->toBe([]);
});

test('canTransitionTo returns true for valid transition', function () {
    expect(SubmissionStatus::Pending->canTransitionTo(SubmissionStatus::InReview))->toBeTrue();
});

test('canTransitionTo returns false for invalid transition', function () {
    expect(SubmissionStatus::Pending->canTransitionTo(SubmissionStatus::Approved))->toBeFalse();
});

test('all statuses have labels', function () {
    foreach (SubmissionStatus::cases() as $status) {
        expect($status->label())->toBeString()->not->toBeEmpty();
    }
});

test('all statuses have colors', function () {
    foreach (SubmissionStatus::cases() as $status) {
        expect($status->color())->toBeString()->not->toBeEmpty();
    }
});

// --- Submission Model ---

test('submission defaults to pending status', function () {
    $submission = Submission::factory()->create();

    expect($submission->fresh()->status)->toBe(SubmissionStatus::Pending);
});

test('submission status is cast to enum', function () {
    $submission = Submission::factory()->create(['status' => 'approved']);

    expect($submission->fresh()->status)->toBe(SubmissionStatus::Approved);
});

test('submission metadata is cast to array', function () {
    $metadata = ['ip_address' => '127.0.0.1', 'user_agent' => 'TestAgent'];
    $submission = Submission::factory()->create(['metadata' => $metadata]);

    expect($submission->fresh()->metadata)->toBe($metadata);
});

test('submission can transition to valid status', function () {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::Pending]);

    $submission->transitionTo(SubmissionStatus::InReview);

    expect($submission->fresh()->status)->toBe(SubmissionStatus::InReview);
});

test('submission cannot transition to invalid status', function () {
    $submission = Submission::factory()->create(['status' => SubmissionStatus::Pending]);

    expect(fn () => $submission->transitionTo(SubmissionStatus::Approved))
        ->toThrow(InvalidArgumentException::class);
});

test('submission belongs to assigned reviewer', function () {
    $reviewer = User::factory()->create();
    $submission = Submission::factory()->create(['assigned_reviewer_id' => $reviewer->id]);

    expect($submission->assignedReviewer->id)->toBe($reviewer->id);
});

test('submission assigned reviewer is nullable', function () {
    $submission = Submission::factory()->create(['assigned_reviewer_id' => null]);

    expect($submission->assignedReviewer)->toBeNull();
});

// --- Metadata Capture on Public Submit ---

test('submission captures metadata on public form submit', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => ['pages' => [[
            'id' => fake()->uuid(),
            'title' => null,
            'heading' => null,
            'subheading' => null,
            'submit_button_text' => null,
            'fields' => [],
        ]]],
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])->call('submit');

    $submission = Submission::where('form_id', $form->id)->first();

    expect($submission->metadata)->toBeArray()
        ->and($submission->metadata)->toHaveKeys(['ip_address', 'user_agent', 'referer']);
});

test('submission captures form version on public submit', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => ['pages' => [[
            'id' => fake()->uuid(),
            'title' => null,
            'heading' => null,
            'subheading' => null,
            'submit_button_text' => null,
            'fields' => [],
        ]]],
    ]);
    FormVersion::factory()->create(['form_id' => $form->id, 'version' => 3]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])->call('submit');

    $submission = Submission::where('form_id', $form->id)->first();

    expect($submission->form_version)->toBe(3);
});

test('submission extracts respondent email from data', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);

    $fieldType = FormFieldType::from('text-input');
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => ['pages' => [[
            'id' => fake()->uuid(),
            'title' => null,
            'heading' => null,
            'subheading' => null,
            'submit_button_text' => null,
            'fields' => [
                ['type' => 'text-input', 'key' => 'email', 'sort' => 0, 'data' => array_merge($fieldType->defaultData(), ['label' => 'Email'])],
                ['type' => 'text-input', 'key' => 'name', 'sort' => 1, 'data' => array_merge($fieldType->defaultData(), ['label' => 'Name'])],
            ],
        ]]],
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.email', 'test@example.com')
        ->set('data.name', 'Test User')
        ->call('submit');

    $submission = Submission::where('form_id', $form->id)->first();

    expect($submission->respondent_email)->toBe('test@example.com')
        ->and($submission->respondent_name)->toBe('Test User');
});

// --- Policy ---

test('editors can review submissions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Editor->value]);

    expect($user->hasTeamPermission($team, TeamPermission::ReviewSubmission))->toBeTrue();
});

test('viewers cannot review submissions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Viewer->value]);

    expect($user->hasTeamPermission($team, TeamPermission::ReviewSubmission))->toBeFalse();
});

// --- View Submission Page ---

test('view submission page displays status badge', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create([
        'form_id' => $form->id,
        'status' => SubmissionStatus::InReview,
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewSubmission::class, ['record' => $submission->getRouteKey()])
        ->assertOk()
        ->assertSee('In Review');
});

test('view submission page shows change status action for reviewers', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Editor->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create([
        'form_id' => $form->id,
        'status' => SubmissionStatus::Pending,
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewSubmission::class, ['record' => $submission->getRouteKey()])
        ->assertActionVisible('changeStatus');
});

test('view submission page hides change status action for viewers', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Viewer->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create([
        'form_id' => $form->id,
        'status' => SubmissionStatus::Pending,
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewSubmission::class, ['record' => $submission->getRouteKey()])
        ->assertActionHidden('changeStatus');
});

test('status can be changed via view page action', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create([
        'form_id' => $form->id,
        'status' => SubmissionStatus::Pending,
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewSubmission::class, ['record' => $submission->getRouteKey()])
        ->callAction('changeStatus', data: ['status' => 'in_review']);

    expect($submission->fresh()->status)->toBe(SubmissionStatus::InReview);
});

test('reviewer can be assigned via view page action', function () {
    $owner = User::factory()->create();
    $reviewer = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($reviewer, ['role' => TeamRole::Reviewer->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create(['form_id' => $form->id]);

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewSubmission::class, ['record' => $submission->getRouteKey()])
        ->callAction('assignReviewer', data: ['assigned_reviewer_id' => $reviewer->id]);

    expect($submission->fresh()->assigned_reviewer_id)->toBe($reviewer->id);
});
