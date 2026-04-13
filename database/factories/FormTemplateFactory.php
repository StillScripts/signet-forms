<?php

namespace Database\Factories;

use App\Enums\FormFieldType;
use App\Enums\FormTemplateCategory;
use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormTemplate>
 */
class FormTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(FormTemplateCategory::cases()),
            'schema' => ['pages' => [[
                'id' => fake()->uuid(),
                'title' => null,
                'heading' => null,
                'subheading' => null,
                'submit_button_text' => null,
                'fields' => [
                    [
                        'type' => FormFieldType::TextInput->value,
                        'key' => 'name',
                        'sort' => 0,
                        'data' => array_merge(FormFieldType::TextInput->defaultData(), ['label' => 'Name', 'is_required' => true]),
                    ],
                ],
            ]]],
        ];
    }
}
