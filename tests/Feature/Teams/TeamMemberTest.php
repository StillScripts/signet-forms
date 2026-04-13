<?php

use App\Enums\TeamRole;
use App\Filament\Pages\TeamSettings;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('team member role can be updated by owner via table action', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $editor->id)
        ->first();

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callTableAction('changeRole', $membership, data: [
            'role' => TeamRole::Admin->value,
        ]);

    expect(
        $team->members()->where('user_id', $editor->id)->first()->pivot->role->value
    )->toEqual(TeamRole::Admin->value);
});

test('team member role can be updated by admin via table action', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $editor = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $editor->id)
        ->first();

    $this->actingAs($admin);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callTableAction('changeRole', $membership, data: [
            'role' => TeamRole::Reviewer->value,
        ]);

    expect(
        $team->members()->where('user_id', $editor->id)->first()->pivot->role->value
    )->toEqual(TeamRole::Reviewer->value);
});

test('team member role cannot be updated by editor', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $viewer = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);
    $team->members()->attach($viewer, ['role' => TeamRole::Viewer->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $viewer->id)
        ->first();

    $this->actingAs($editor);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->assertTableActionHidden('changeRole', $membership);
});

test('team member can be removed by owner via table action', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $editor->id)
        ->first();

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callTableAction('remove', $membership);

    expect($editor->fresh()->belongsToTeam($team))->toBeFalse();
});

test('team member can be removed by admin via table action', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $editor = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $editor->id)
        ->first();

    $this->actingAs($admin);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callTableAction('remove', $membership);

    expect($editor->fresh()->belongsToTeam($team))->toBeFalse();
});

test('team member cannot be removed by editors', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $viewer = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);
    $team->members()->attach($viewer, ['role' => TeamRole::Viewer->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $viewer->id)
        ->first();

    $this->actingAs($editor);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->assertTableActionHidden('remove', $membership);
});

test('removed members current team is set to personal team', function () {
    $owner = User::factory()->create();
    $editor = User::factory()->create();
    $personalTeam = $editor->personalTeam();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);

    $editor->update(['current_team_id' => $team->id]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $editor->id)
        ->first();

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callTableAction('remove', $membership);

    expect($editor->fresh()->current_team_id)->toEqual($personalTeam->id);
});
