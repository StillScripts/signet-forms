<?php

use App\Enums\FormFieldType;
use App\Enums\TeamRole;
use App\Models\Form;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

/**
 * @return array{0: User, 1: Team, 2: Project, 3: Form}
 */
function setUpLayoutBuilderTest(): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id, 'schema' => null]);

    test()->actingAs($user);
    test()->setUpFilamentPanel($team);

    return [$user, $team, $project, $form];
}

/**
 * @param  list<array<string, mixed>>  $fields
 * @return array<string, mixed>
 */
function makeLayoutFormSchema(array $fields): array
{
    return ['pages' => [[
        'id' => fake()->uuid(),
        'title' => null,
        'heading' => null,
        'subheading' => null,
        'submit_button_text' => null,
        'fields' => $fields,
    ]]];
}

/**
 * @param  array<string, mixed>  $data
 * @return array<string, mixed>
 */
function makeLayoutField(FormFieldType $type, string $key, array $data = []): array
{
    return [
        'type' => $type->value,
        'key' => $key,
        'sort' => 0,
        'data' => array_merge($type->defaultData(), $data),
    ];
}
