<?php

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'resource_type' => 'form',
            'resource_id' => fake()->uuid(),
            'action' => fake()->randomElement(AuditAction::cases())->value,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'details' => [],
            'created_at' => now(),
        ];
    }
}
