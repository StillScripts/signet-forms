<?php

use App\Enums\FormFieldType;
use App\Enums\TeamRole;
use App\Filament\Resources\Projects\Resources\Forms\Pages\FormBuilderPage;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

function makeSchema(array $fields = [], ?string $title = null): array
{
    return ['pages' => [[
        'id' => fake()->uuid(),
        'title' => $title,
        'heading' => null,
        'subheading' => null,
        'submit_button_text' => null,
        'fields' => $fields,
    ]]];
}

function setUpVersioningTest(): array
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

// --- Model Relationships ---

test('form has many versions', function () {
    $form = Form::factory()->create();

    FormVersion::factory()->create(['form_id' => $form->id, 'version' => 1]);
    FormVersion::factory()->create(['form_id' => $form->id, 'version' => 2]);

    expect($form->versions)->toHaveCount(2)
        ->and($form->versions->first()->version)->toBe(1)
        ->and($form->versions->last()->version)->toBe(2);
});

test('form latest version returns highest version', function () {
    $form = Form::factory()->create();

    FormVersion::factory()->create(['form_id' => $form->id, 'version' => 1, 'schema' => makeSchema()]);
    FormVersion::factory()->create(['form_id' => $form->id, 'version' => 2, 'schema' => makeSchema([['type' => 'text-input']])]);

    $latest = $form->latestVersion();

    expect($latest->version)->toBe(2)
        ->and($latest->schema['pages'][0]['fields'])->toHaveCount(1);
});

test('form version belongs to form', function () {
    $form = Form::factory()->create();
    $version = FormVersion::factory()->create(['form_id' => $form->id]);

    expect($version->form->id)->toBe($form->id);
});

test('form versions are deleted when form is force deleted', function () {
    $form = Form::factory()->create();
    FormVersion::factory()->create(['form_id' => $form->id, 'version' => 1]);

    $form->forceDelete();

    expect(FormVersion::where('form_id', $form->id)->count())->toBe(0);
});

// --- Save Creates Version ---

test('save creates a new version record', function () {
    [, , $project, $form] = setUpVersioningTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('save');

    expect($form->versions()->count())->toBe(1);

    $version = $form->versions()->first();
    $fields = $version->schema['pages'][0]['fields'];
    expect($version->version)->toBe(1)
        ->and($fields)->toHaveCount(1)
        ->and($fields[0]['type'])->toBe('text-input');
});

test('subsequent saves increment version number', function () {
    [, , $project, $form] = setUpVersioningTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('save')
        ->assertSet('currentVersion', 1)
        ->call('addField', 'textarea')
        ->call('save')
        ->assertSet('currentVersion', 2);

    expect($form->versions()->count())->toBe(2);
});

test('save syncs schema to form model', function () {
    [, , $project, $form] = setUpVersioningTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('save');

    $form->refresh();
    $fields = $form->schema['pages'][0]['fields'];
    expect($fields)->toHaveCount(1)
        ->and($fields[0]['type'])->toBe('text-input');
});

// --- Undo/Redo with Versions ---

test('undo loads previous saved version', function () {
    [, , $project, $form] = setUpVersioningTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('save')
        ->assertSet('currentVersion', 1)
        ->call('addField', 'text-input')
        ->call('save')
        ->assertSet('currentVersion', 2)
        ->assertCount('fields', 1)
        ->call('undo')
        ->assertSet('currentVersion', 1)
        ->assertCount('fields', 0)
        ->assertSet('hasUnsavedChanges', false);
});

test('undo is disabled at version 1', function () {
    [, , $project, $form] = setUpVersioningTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('save')
        ->assertSet('currentVersion', 1)
        ->call('undo')
        ->assertSet('currentVersion', 1);
});

test('redo loads next saved version', function () {
    [, , $project, $form] = setUpVersioningTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('save')
        ->call('addField', 'text-input')
        ->call('save')
        ->call('undo')
        ->assertCount('fields', 0)
        ->call('redo')
        ->assertCount('fields', 1)
        ->assertSet('currentVersion', 2);
});

test('redo is disabled at latest version', function () {
    [, , $project, $form] = setUpVersioningTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('save')
        ->assertSet('currentVersion', 1)
        ->call('redo')
        ->assertSet('currentVersion', 1);
});

test('save after undo appends new version without branching', function () {
    [, , $project, $form] = setUpVersioningTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('save')
        ->call('addField', 'text-input')
        ->call('save')
        ->call('addField', 'textarea')
        ->call('save')
        ->assertSet('currentVersion', 3)
        ->call('undo')
        ->assertSet('currentVersion', 2)
        ->call('addField', 'number')
        ->call('save')
        ->assertSet('currentVersion', 4)
        ->assertSet('latestVersion', 4);

    expect($form->versions()->count())->toBe(4);
});

// --- Version Label ---

test('version label shows version number and relative time', function () {
    [, , $project, $form] = setUpVersioningTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('save');

    $label = $component->instance()->getCurrentVersionLabel();

    expect($label)->toStartWith('v1 (');
});

test('version label is null when no versions exist', function () {
    [, , $project, $form] = setUpVersioningTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $label = $component->instance()->getCurrentVersionLabel();

    expect($label)->toBeNull();
});

// --- Reset with Versions ---

test('reset restores current version fields discarding unsaved changes', function () {
    [, , $project, $form] = setUpVersioningTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('save')
        ->assertCount('fields', 1)
        ->call('addField', 'textarea')
        ->assertCount('fields', 2)
        ->assertSet('hasUnsavedChanges', true)
        ->call('resetForm')
        ->assertCount('fields', 1)
        ->assertSet('hasUnsavedChanges', false);
});

// --- Mount with Existing Versions ---

test('mount loads latest version fields', function () {
    [, , $project, $form] = setUpVersioningTest();

    $fields = [
        ['type' => 'text-input', 'key' => 'name', 'sort' => 0, 'data' => FormFieldType::TextInput->defaultData()],
    ];

    FormVersion::factory()->create(['form_id' => $form->id, 'version' => 1, 'schema' => makeSchema()]);
    FormVersion::factory()->create(['form_id' => $form->id, 'version' => 2, 'schema' => makeSchema($fields)]);

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertSet('currentVersion', 2)
        ->assertSet('latestVersion', 2)
        ->assertCount('fields', 1);
});
