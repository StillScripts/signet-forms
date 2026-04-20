<?php

use App\Models\User;

test('the home page renders successfully', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('guests see the app name and login and register calls to action', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('Signet Forms', false)
        ->assertSee(route('filament.admin.auth.login'), false)
        ->assertSee(route('filament.admin.auth.register'), false)
        ->assertDontSee('laravel.com/docs');
});

test('authenticated users see a dashboard call to action linking to their team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->actingAs($user)->get(route('home'));

    $response
        ->assertOk()
        ->assertSee(route('filament.admin.pages.dashboard', ['tenant' => $team->slug]), false)
        ->assertSee('Go to dashboard');
});
