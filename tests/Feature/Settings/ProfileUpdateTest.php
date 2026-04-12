<?php

use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->get(route('filament.admin.tenant.profile', ['tenant' => $team->slug]))
        ->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(EditProfile::class)
        ->fillForm([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(EditProfile::class)
        ->fillForm([
            'name' => 'Updated Name',
            'email' => $user->email,
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});
