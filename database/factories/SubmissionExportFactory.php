<?php

namespace Database\Factories;

use App\Enums\SubmissionExportStatus;
use App\Models\Form;
use App\Models\SubmissionExport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionExport>
 */
class SubmissionExportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'form_id' => Form::factory(),
            'requested_by' => User::factory(),
            'status' => SubmissionExportStatus::Pending,
            'format' => 'csv',
            'filters' => null,
            'reason' => fake()->sentence(),
            'row_count' => null,
            'file_path' => null,
            'file_size' => null,
            'error_message' => null,
            'expires_at' => null,
            'completed_at' => null,
            'failed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => SubmissionExportStatus::Completed,
            'row_count' => fake()->numberBetween(1, 100),
            'file_path' => 'exports/'.fake()->uuid().'.csv',
            'file_size' => fake()->numberBetween(100, 10_000),
            'completed_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
