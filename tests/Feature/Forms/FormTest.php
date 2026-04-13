<?php

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Filament\Resources\Projects\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Projects\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Projects\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Projects\Resources\Forms\Pages\ViewForm;
use App\Models\Form;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

// --- Model & Relationships ---

test('form belongs to a project', function () {
    $project = Project::factory()->create();
    $form = Form::factory()->create(['project_id' => $project->id]);

    expect($form->project->id)->toBe($project->id);
});

test('project has many forms', function () {
    $project = Project::factory()->create();
    Form::factory()->count(3)->create(['project_id' => $project->id]);

    expect($project->forms)->toHaveCount(3);
});

// --- Slug Generation ---

test('form slug is auto-generated from name', function () {
    $project = Project::factory()->create();
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'name' => 'Contact Form',
        'slug' => null,
    ]);

    expect($form->fresh()->slug)->toBe('contact-form');
});

test('form slug is unique within a project', function () {
    $project = Project::factory()->create();

    Form::factory()->create([
        'project_id' => $project->id,
        'name' => 'Feedback',
        'slug' => 'feedback',
    ]);

    $second = Form::factory()->create([
        'project_id' => $project->id,
        'name' => 'Feedback',
        'slug' => null,
    ]);

    expect($second->fresh()->slug)->toBe('feedback-1');
});

test('same slug can exist in different projects', function () {
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    $formA = Form::factory()->create([
        'project_id' => $projectA->id,
        'name' => 'Survey',
        'slug' => null,
    ]);

    $formB = Form::factory()->create([
        'project_id' => $projectB->id,
        'name' => 'Survey',
        'slug' => null,
    ]);

    expect($formA->fresh()->slug)->toBe('survey')
        ->and($formB->fresh()->slug)->toBe('survey');
});

test('form slug updates when name changes', function () {
    $project = Project::factory()->create();
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);

    $form->update(['name' => 'New Name']);

    expect($form->fresh()->slug)->toBe('new-name');
});

// --- Casts ---

test('form schema is cast to array', function () {
    $schema = ['pages' => [[
        'id' => fake()->uuid(),
        'title' => null,
        'heading' => null,
        'subheading' => null,
        'submit_button_text' => null,
        'fields' => [
            ['type' => 'text', 'data' => ['label' => 'Name']],
            ['type' => 'email', 'data' => ['label' => 'Email']],
        ],
    ]]];

    $form = Form::factory()->create(['schema' => $schema]);

    expect($form->fresh()->schema)->toBe($schema);
});

test('form is_published is cast to boolean', function () {
    $form = Form::factory()->create(['is_published' => true]);

    expect($form->fresh()->is_published)->toBeTrue();
});

// --- Policy ---

test('team members can view forms', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    expect($user->can('view', $form))->toBeTrue();
});

test('owners can create forms', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    expect($user->hasTeamPermission($team, TeamPermission::CreateForm))->toBeTrue();
});

test('admins can create forms', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    expect($user->hasTeamPermission($team, TeamPermission::CreateForm))->toBeTrue();
});

test('members cannot create forms', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->hasTeamPermission($team, TeamPermission::CreateForm))->toBeFalse();
});

test('members cannot delete forms', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->hasTeamPermission($team, TeamPermission::DeleteForm))->toBeFalse();
});

// --- Filament Resource ---

test('form list page can be rendered', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ListForms::class, [
        'parentRecord' => $project,
    ])
        ->assertOk();
});

test('form list displays project forms', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    $forms = Form::factory()->count(3)->create(['project_id' => $project->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ListForms::class, [
        'parentRecord' => $project,
    ])
        ->loadTable()
        ->assertCanSeeTableRecords($forms);
});

test('owners can create a form via filament', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(CreateForm::class, [
        'parentRecord' => $project,
    ])
        ->fillForm([
            'name' => 'Contact Us',
            'description' => 'General inquiry form',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('forms', [
        'project_id' => $project->id,
        'name' => 'Contact Us',
        'slug' => 'contact-us',
        'description' => 'General inquiry form',
    ]);
});

test('form can be created with only a name', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(CreateForm::class, [
        'parentRecord' => $project,
    ])
        ->fillForm([
            'name' => 'Minimal Form',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('forms', [
        'project_id' => $project->id,
        'name' => 'Minimal Form',
        'slug' => 'minimal-form',
    ]);
});

test('form name is required', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(CreateForm::class, [
        'parentRecord' => $project,
    ])
        ->fillForm([
            'name' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('owners can edit a form via filament', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(EditForm::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated Form Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('forms', [
        'id' => $form->id,
        'name' => 'Updated Form Name',
    ]);
});

test('deleting a form soft deletes it', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(EditForm::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->callAction('delete');

    $this->assertSoftDeleted('forms', [
        'id' => $form->id,
    ]);
});

test('view page renders for a form', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewForm::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertOk();
});
