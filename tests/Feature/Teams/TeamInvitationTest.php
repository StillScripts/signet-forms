<?php

use App\Enums\TeamRole;
use App\Filament\Pages\TeamSettings;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('team invitations can be created via team settings invite action', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->callTableAction('invite', data: [
            'email' => 'invited@example.com',
            'role' => TeamRole::Editor->value,
        ]);

    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'role' => TeamRole::Editor->value,
    ]);
});

test('team invitations cannot be created by editors', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Editor->value]);

    $this->actingAs($member);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->assertTableActionHidden('invite');
});

test('team invitations can be cancelled by owner', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner);
    $this->setUpFilamentPanel($team);

    Livewire::test(TeamSettings::class)
        ->call('cancelInvitation', $invitation->code);

    $this->assertDatabaseMissing('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('team invitations can be accepted via controller', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'role' => TeamRole::Editor,
        'invited_by' => $owner->id,
    ]);

    $response = $this->actingAs($invitedUser)
        ->get(route('invitations.accept', $invitation));

    $response->assertRedirect(route('filament.admin.pages.dashboard', ['tenant' => $team->slug]));

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($invitedUser->fresh()->belongsToTeam($team))->toBeTrue();
});

test('team invitations cannot be accepted by user that was not invited', function () {
    $owner = User::factory()->create();
    $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->actingAs($uninvitedUser)
        ->get(route('invitations.accept', $invitation));

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect($uninvitedUser->fresh()->belongsToTeam($team))->toBeFalse();
});

test('expired invitations cannot be accepted', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->actingAs($invitedUser)
        ->get(route('invitations.accept', $invitation));

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect($invitedUser->fresh()->belongsToTeam($team))->toBeFalse();
});

test('already accepted invitations cannot be accepted again', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->actingAs($invitedUser)
        ->get(route('invitations.accept', $invitation));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
