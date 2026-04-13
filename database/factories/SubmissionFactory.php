<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Form;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'data' => [
                'name' => fake()->name(),
                'email' => fake()->email(),
            ],
            'status' => SubmissionStatus::Pending,
        ];
    }

    public function withMetadata(): static
    {
        return $this->state(fn () => [
            'metadata' => [
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'referer' => fake()->url(),
            ],
        ]);
    }

    public function status(SubmissionStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
        ]);
    }
}
