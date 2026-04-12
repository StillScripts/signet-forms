<?php

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

// --- Model & Relationships ---

test('project belongs to a team', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);

    expect($project->team->id)->toBe($team->id);
});

test('team has many projects', function () {
    $team = Team::factory()->create();
    Project::factory()->count(3)->create(['team_id' => $team->id]);

    expect($team->projects)->toHaveCount(3);
});

// --- Slug Generation ---

test('project slug is auto-generated from name', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'My Great Project',
        'slug' => null,
    ]);

    expect($project->fresh()->slug)->toBe('my-great-project');
});

test('project slug is unique within a team', function () {
    $team = Team::factory()->create();

    Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Alpha',
        'slug' => 'alpha',
    ]);

    $second = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Alpha',
        'slug' => null,
    ]);

    expect($second->fresh()->slug)->toBe('alpha-1');
});

test('same slug can exist in different teams', function () {
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $projectA = Project::factory()->create([
        'team_id' => $teamA->id,
        'name' => 'Portal',
        'slug' => null,
    ]);

    $projectB = Project::factory()->create([
        'team_id' => $teamB->id,
        'name' => 'Portal',
        'slug' => null,
    ]);

    expect($projectA->fresh()->slug)->toBe('portal')
        ->and($projectB->fresh()->slug)->toBe('portal');
});

test('project slug updates when name changes', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create([
        'team_id' => $team->id,
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);

    $project->update(['name' => 'New Name']);

    expect($project->fresh()->slug)->toBe('new-name');
});

// --- Policy ---

test('team members can view projects', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $project = Project::factory()->create(['team_id' => $team->id]);

    expect($user->can('view', $project))->toBeTrue();
});

test('owners can create projects', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    expect($user->hasTeamPermission($team, TeamPermission::CreateProject))->toBeTrue();
});

test('admins can create projects', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    expect($user->hasTeamPermission($team, TeamPermission::CreateProject))->toBeTrue();
});

test('members cannot create projects', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->hasTeamPermission($team, TeamPermission::CreateProject))->toBeFalse();
});

test('members cannot delete projects', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->hasTeamPermission($team, TeamPermission::DeleteProject))->toBeFalse();
});

// --- Filament Resource ---

test('project list page can be rendered', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ListProjects::class)
        ->assertOk();
});

test('project list displays team projects', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $projects = Project::factory()->count(3)->create(['team_id' => $team->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ListProjects::class)
        ->loadTable()
        ->assertCanSeeTableRecords($projects);
});

test('projects are scoped to their team', function () {
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $projectA = Project::factory()->create(['team_id' => $teamA->id, 'name' => 'Team A Project']);
    $projectB = Project::factory()->create(['team_id' => $teamB->id, 'name' => 'Team B Project']);

    expect($teamA->projects)->toHaveCount(1)
        ->and($teamA->projects->first()->id)->toBe($projectA->id)
        ->and($teamB->projects)->toHaveCount(1)
        ->and($teamB->projects->first()->id)->toBe($projectB->id);
});

test('owners can create a project via filament', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(CreateProject::class)
        ->fillForm([
            'name' => 'Client Portal',
            'description' => 'Portal for client submissions',
            'url' => 'https://example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('projects', [
        'team_id' => $team->id,
        'name' => 'Client Portal',
        'slug' => 'client-portal',
        'description' => 'Portal for client submissions',
        'url' => 'https://example.com',
    ]);
});

test('project can be created with only a name', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(CreateProject::class)
        ->fillForm([
            'name' => 'Minimal Project',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('projects', [
        'team_id' => $team->id,
        'name' => 'Minimal Project',
        'slug' => 'minimal-project',
    ]);
});

test('project name is required', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(CreateProject::class)
        ->fillForm([
            'name' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('project url must be valid', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(CreateProject::class)
        ->fillForm([
            'name' => 'Test',
            'url' => 'not-a-url',
        ])
        ->call('create')
        ->assertHasFormErrors(['url' => 'url']);
});

test('owners can edit a project via filament', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Project Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Project Name',
    ]);
});

test('deleting a project soft deletes it', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->callAction('delete');

    $this->assertSoftDeleted('projects', [
        'id' => $project->id,
    ]);
});

test('view page renders for project with url', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create([
        'team_id' => $team->id,
        'url' => 'https://my.trustloop.local/',
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertOk();
});

test('view page renders for project without url', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create([
        'team_id' => $team->id,
        'url' => null,
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertOk();
});

test('guests cannot access project pages', function () {
    $team = Team::factory()->create();

    $response = $this->get(route('filament.admin.resources.projects.index', ['tenant' => $team->slug]));

    $response->assertRedirect(route('filament.admin.auth.login'));
});
