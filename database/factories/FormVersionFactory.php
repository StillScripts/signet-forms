<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormVersion>
 */
class FormVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'version' => 1,
            'schema' => ['pages' => [['id' => fake()->uuid(), 'title' => null, 'heading' => null, 'subheading' => null, 'submit_button_text' => null, 'fields' => []]]],
        ];
    }
}
