<?php

use App\Enums\TeamRole;
use App\Filament\Pages\TeamSettings;
use App\Filament\Pages\Tenancy\EditTeamProfile;
use App\Filament\Pages\Tenancy\RegisterTeam;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('teams can be created via register team page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->setUpFilamentPanel();

    Livewire::test(RegisterTeam::class)
        ->fillForm([
            'name' => 'Test Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('teams', [
        'name' => 'Test Team',
        'is_personal' => false,
    ]);
});

test('team slug is generated automatically on creation', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->setUpFilamentPanel();

    Livewire::test(RegisterTeam::class)
        ->fillForm([
            'name' => 'My Awesome Team',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('teams', [
        'name' => 'My Awesome Team',
        'slug' => 'my-awesome-team',
    ]);
});

test('team slug uses next available suffix', function () {
    $user = User::factory()->create();

    Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Team::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Team::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    $this->actingAs($user);
    $this->setUpFilamentPanel();

    Livewire::test(RegisterTeam::class)
        ->fillForm([
            'name' => 'Acme',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('teams', [
        'name' => 'Acme',
        'slug' => 'acme-11',
    ]);
});

test('team name can be updated by owner via edit team profile', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Original Name']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(EditTeamProfile::class)
        ->fillForm([
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'name' => 'Updated Name',
    ]);
});

test('team settings page can be rendered by member', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this->actingAs($user);

    $response = $this->get(route('filament.admin.pages.team-settings', ['tenant' => $team->slug]));

    $response->assertOk();
});

test('teams can be deleted by owners via team settings action', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callAction('deleteTeam', data: [
            'confirmName' => $team->name,
        ]);

    $this->assertSoftDeleted('teams', [
        'id' => $team->id,
    ]);
});

test('team deletion requires name confirmation', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callAction('deleteTeam', data: [
            'confirmName' => 'Wrong Name',
        ])
        ->assertHasActionErrors(['confirmName']);

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'deleted_at' => null,
    ]);
});

test('deleting current team switches to alphabetically first remaining team', function () {
    $user = User::factory()->create(['name' => 'Mike']);

    $zuluTeam = Team::factory()->create(['name' => 'Zulu Team']);
    $zuluTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $alphaTeam = Team::factory()->create(['name' => 'Alpha Team']);
    $alphaTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $betaTeam = Team::factory()->create(['name' => 'Beta Team']);
    $betaTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $user->update(['current_team_id' => $zuluTeam->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($zuluTeam);

    Livewire::test(TeamSettings::class)
        ->callAction('deleteTeam', data: [
            'confirmName' => $zuluTeam->name,
        ]);

    $this->assertSoftDeleted('teams', [
        'id' => $zuluTeam->id,
    ]);

    expect($user->fresh()->current_team_id)->toEqual($alphaTeam->id);
});

test('deleting current team falls back to personal team when alphabetically first', function () {
    $user = User::factory()->create();
    $personalTeam = $user->personalTeam();
    $team = Team::factory()->create(['name' => 'Zulu Team']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callAction('deleteTeam', data: [
            'confirmName' => $team->name,
        ]);

    $this->assertSoftDeleted('teams', [
        'id' => $team->id,
    ]);

    expect($user->fresh()->current_team_id)->toEqual($personalTeam->id);
});

test('deleting non current team leaves current team unchanged', function () {
    $user = User::factory()->create();
    $personalTeam = $user->personalTeam();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $user->update(['current_team_id' => $personalTeam->id]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callAction('deleteTeam', data: [
            'confirmName' => $team->name,
        ]);

    $this->assertSoftDeleted('teams', [
        'id' => $team->id,
    ]);

    expect($user->fresh()->current_team_id)->toEqual($personalTeam->id);
});

test('deleting team switches other affected users to their personal team', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $owner->update(['current_team_id' => $team->id]);
    $member->update(['current_team_id' => $team->id]);

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callAction('deleteTeam', data: [
            'confirmName' => $team->name,
        ]);

    expect($member->fresh()->current_team_id)->toEqual($member->personalTeam()->id);
});

test('personal teams cannot be deleted', function () {
    $user = User::factory()->create();
    $personalTeam = $user->personalTeam();

    $this->actingAs($user);
    $this->setUpFilamentPanel($personalTeam);

    Livewire::test(TeamSettings::class)
        ->assertActionHidden('deleteTeam');

    $this->assertDatabaseHas('teams', [
        'id' => $personalTeam->id,
        'deleted_at' => null,
    ]);
});

test('guests cannot access team pages', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('filament.admin.pages.team-settings', ['tenant' => $team->slug]));

    $response->assertRedirect(route('filament.admin.auth.login'));
});
