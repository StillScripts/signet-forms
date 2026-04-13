<?php

namespace Database\Factories;

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
        ];
    }
}
