<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('filament.admin.pages.dashboard', ['tenant' => $team->slug]));

    $response->assertRedirect(route('filament.admin.auth.login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('filament.admin.pages.dashboard', ['tenant' => $team->slug]));

    $response->assertOk();
});
