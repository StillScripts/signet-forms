<?php

use App\Enums\TeamRole;
use App\Filament\Pages\TeamSettings;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('team member role can be updated by owner via table action', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $member->id)
        ->first();

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callTableAction('changeRole', $membership, data: [
            'role' => TeamRole::Admin->value,
        ]);

    expect(
        $team->members()->where('user_id', $member->id)->first()->pivot->role->value
    )->toEqual(TeamRole::Admin->value);
});

test('team member role cannot be updated by non owner', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $member->id)
        ->first();

    $this->actingAs($admin);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->assertTableActionHidden('changeRole', $membership);
});

test('team member can be removed by owner via table action', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $member->id)
        ->first();

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callTableAction('remove', $membership);

    expect($member->fresh()->belongsToTeam($team))->toBeFalse();
});

test('team member cannot be removed by non owners', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $member->id)
        ->first();

    $this->actingAs($admin);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->assertTableActionHidden('remove', $membership);
});

test('removed members current team is set to personal team', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $personalTeam = $member->personalTeam();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $member->update(['current_team_id' => $team->id]);

    $membership = Membership::where('team_id', $team->id)
        ->where('user_id', $member->id)
        ->first();

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callTableAction('remove', $membership);

    expect($member->fresh()->current_team_id)->toEqual($personalTeam->id);
});
