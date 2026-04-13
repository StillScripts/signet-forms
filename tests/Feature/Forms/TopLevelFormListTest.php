<?php

use App\Enums\TeamRole;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Models\Form;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

function setUpTopLevelTest(): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    test()->actingAs($user);
    test()->setUpFilamentPanel($team);

    return compact('user', 'team', 'project');
}

test('top-level forms list page renders', function () {
    setUpTopLevelTest();

    Livewire::test(ListForms::class)
        ->assertOk();
});

test('top-level forms list displays forms from current team', function () {
    ['team' => $team] = setUpTopLevelTest();

    $projectA = Project::factory()->create(['team_id' => $team->id]);
    $projectB = Project::factory()->create(['team_id' => $team->id]);

    $formsA = Form::factory()->count(2)->create(['project_id' => $projectA->id]);
    $formsB = Form::factory()->count(2)->create(['project_id' => $projectB->id]);

    Livewire::test(ListForms::class)
        ->loadTable()
        ->assertCanSeeTableRecords($formsA->merge($formsB));
});

test('top-level forms list excludes forms from other teams', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    // Create "other" data before panel setup to avoid Filament's tenant auto-association
    $otherTeam = Team::factory()->create();
    $otherProject = Project::factory()->create(['team_id' => $otherTeam->id]);
    $otherForm = Form::factory()->create(['project_id' => $otherProject->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    $ownProject = Project::factory()->create(['team_id' => $team->id]);
    $ownForm = Form::factory()->create(['project_id' => $ownProject->id]);

    Livewire::test(ListForms::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$ownForm])
        ->assertCanNotSeeTableRecords([$otherForm]);
});

test('top-level forms list shows project column', function () {
    ['project' => $project] = setUpTopLevelTest();

    Form::factory()->create(['project_id' => $project->id]);

    Livewire::test(ListForms::class)
        ->loadTable()
        ->assertCanRenderTableColumn('project.name');
});

test('top-level forms list is searchable by name', function () {
    ['project' => $project] = setUpTopLevelTest();

    $matchingForm = Form::factory()->create([
        'project_id' => $project->id,
        'name' => 'Contact Form',
    ]);
    $otherForm = Form::factory()->create([
        'project_id' => $project->id,
        'name' => 'Feedback Survey',
    ]);

    Livewire::test(ListForms::class)
        ->loadTable()
        ->searchTable('Contact')
        ->assertCanSeeTableRecords([$matchingForm])
        ->assertCanNotSeeTableRecords([$otherForm]);
});

test('top-level forms list is searchable by project name', function () {
    ['team' => $team] = setUpTopLevelTest();

    $projectA = Project::factory()->create(['team_id' => $team->id, 'name' => 'Alpha Project']);
    $projectB = Project::factory()->create(['team_id' => $team->id, 'name' => 'Beta Project']);

    $formA = Form::factory()->create(['project_id' => $projectA->id]);
    $formB = Form::factory()->create(['project_id' => $projectB->id]);

    Livewire::test(ListForms::class)
        ->loadTable()
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$formA])
        ->assertCanNotSeeTableRecords([$formB]);
});

test('owner can create a form from the top-level list', function () {
    ['project' => $project] = setUpTopLevelTest();

    Livewire::test(ListForms::class)
        ->callAction('create', [
            'project_id' => $project->id,
            'name' => 'New Form',
            'description' => 'A test form',
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('forms', [
        'project_id' => $project->id,
        'name' => 'New Form',
        'description' => 'A test form',
    ]);
});

test('create action requires project and name', function () {
    setUpTopLevelTest();

    Livewire::test(ListForms::class)
        ->callAction('create', [
            'project_id' => null,
            'name' => null,
        ])
        ->assertHasActionErrors([
            'project_id' => 'required',
            'name' => 'required',
        ]);
});
