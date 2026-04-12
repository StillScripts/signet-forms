<?php

use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('password can be updated via profile page', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);
    $team = $user->currentTeam;

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(EditProfile::class)
        ->fillForm([
            'name' => $user->name,
            'email' => $user->email,
            'currentPassword' => 'password',
            'password' => 'new-password',
            'passwordConfirmation' => 'new-password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

// Two-factor authentication tests are skipped until Filament MFA is configured.
test('two factor security settings are not yet available')->skip('Filament MFA not yet configured');
