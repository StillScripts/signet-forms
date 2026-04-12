<?php

use App\Filament\Pages\Auth\Register;
use App\Models\User;
use Livewire\Livewire;

test('registration screen can be rendered', function () {
    $response = $this->get(route('filament.admin.auth.register'));

    $response->assertOk();
});

test('new users can register', function () {
    $this->setUpFilamentPanel();

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'passwordConfirmation' => 'password',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('John Doe');

    $this->assertAuthenticated();
});

test('new users get a personal team on registration', function () {
    $this->setUpFilamentPanel();

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'passwordConfirmation' => 'password',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'jane@example.com')->first();

    expect($user->personalTeam())->not->toBeNull();
    expect($user->personalTeam()->is_personal)->toBeTrue();
    expect($user->currentTeam)->not->toBeNull();
});
