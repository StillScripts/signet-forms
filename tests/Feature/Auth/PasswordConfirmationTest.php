<?php

use App\Models\User;

test('authenticated users can access the application', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->actingAs($user)
        ->get(route('filament.admin.pages.dashboard', ['tenant' => $team->slug]));

    $response->assertOk();
});
