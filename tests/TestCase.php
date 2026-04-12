<?php

namespace Tests;

use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set up the Filament admin panel context for testing.
     */
    protected function setUpFilamentPanel(?Team $tenant = null): void
    {
        $panel = Filament::getPanel('admin');
        $panel->boot();
        Filament::setCurrentPanel($panel);

        if ($tenant) {
            Filament::setTenant($tenant, isQuiet: true);
        }
    }

    /**
     * Create a user and set up Filament panel with their current team as the tenant.
     *
     * @return array{user: User, team: Team}
     */
    protected function createUserWithFilamentContext(): array
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $this->actingAs($user);
        $this->setUpFilamentPanel($team);

        return ['user' => $user, 'team' => $team];
    }
}
