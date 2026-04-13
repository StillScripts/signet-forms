<?php

use App\Enums\TeamRole;
use App\Filament\Resources\Submissions\Pages\ListSubmissions;
use App\Filament\Resources\Submissions\Pages\ViewSubmission;
use App\Models\Form;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

function setUpSubmissionResource(): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    test()->actingAs($user);
    test()->setUpFilamentPanel($team);

    return [$user, $team, $project];
}

test('submissions list page can be rendered', function () {
    [, $team, $project] = setUpSubmissionResource();

    Livewire::test(ListSubmissions::class)
        ->assertOk();
});

test('submissions list displays team submissions', function () {
    [, $team, $project] = setUpSubmissionResource();

    $form = Form::factory()->create(['project_id' => $project->id]);
    $submissions = Submission::factory()->count(3)->create(['form_id' => $form->id]);

    Livewire::test(ListSubmissions::class)
        ->loadTable()
        ->assertCanSeeTableRecords($submissions);
});

test('submissions list does not show other team submissions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    // Create "other" data before panel setup to avoid Filament's tenant auto-association
    $otherTeam = Team::factory()->create();
    $otherProject = Project::factory()->create(['team_id' => $otherTeam->id]);
    $otherForm = Form::factory()->create(['project_id' => $otherProject->id]);
    $otherSubmission = Submission::factory()->create(['form_id' => $otherForm->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    $ownProject = Project::factory()->create(['team_id' => $team->id]);
    $ownForm = Form::factory()->create(['project_id' => $ownProject->id]);
    $ownSubmission = Submission::factory()->create(['form_id' => $ownForm->id]);

    Livewire::test(ListSubmissions::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$ownSubmission])
        ->assertCanNotSeeTableRecords([$otherSubmission]);
});

test('submission view page can be rendered', function () {
    [, $team, $project] = setUpSubmissionResource();

    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create(['form_id' => $form->id]);

    Livewire::test(ViewSubmission::class, [
        'record' => $submission->getRouteKey(),
    ])
        ->assertOk();
});

test('submission view shows form name', function () {
    [, $team, $project] = setUpSubmissionResource();

    $form = Form::factory()->create(['project_id' => $project->id, 'name' => 'Contact Form']);
    $submission = Submission::factory()->create(['form_id' => $form->id]);

    Livewire::test(ViewSubmission::class, [
        'record' => $submission->getRouteKey(),
    ])
        ->assertSee('Contact Form');
});

test('submission view shows response data', function () {
    [, $team, $project] = setUpSubmissionResource();

    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create([
        'form_id' => $form->id,
        'data' => ['name' => 'John Doe', 'email' => 'john@example.com'],
    ]);

    Livewire::test(ViewSubmission::class, [
        'record' => $submission->getRouteKey(),
    ])
        ->assertSee('John Doe')
        ->assertSee('john@example.com');
});
