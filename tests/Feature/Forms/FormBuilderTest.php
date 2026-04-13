<?php

use App\Enums\FormFieldType;
use App\Enums\TeamRole;
use App\Filament\Resources\Projects\Resources\Forms\Pages\FormBuilderPage;
use App\Models\Form;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

function setUpBuilderTest(): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id, 'fields' => null]);

    test()->actingAs($user);
    test()->setUpFilamentPanel($team);

    return [$user, $team, $project, $form];
}

// --- Page Rendering ---

test('form builder page can be rendered', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertOk();
});

test('form builder page shows form name', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertSee($form->name);
});

// --- Adding Fields ---

test('can add a text input field', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->assertSet('hasUnsavedChanges', true)
        ->assertCount('fields', 1);
});

test('can add multiple field types', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    foreach (FormFieldType::cases() as $type) {
        $component->call('addField', $type->value);
    }

    $component->assertCount('fields', count(FormFieldType::cases()));
});

test('added field has correct default data', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'select');

    $fields = $component->get('fields');
    expect($fields[0]['type'])->toBe('select')
        ->and($fields[0]['data']['label'])->toBe('Select')
        ->and($fields[0]['data']['options'])->toHaveCount(2);
});

test('adding a field selects it', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input');

    $fields = $component->get('fields');
    $component->assertSet('selectedFieldKey', $fields[0]['key']);
});

// --- Removing Fields ---

test('can remove a field', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('addField', 'textarea');

    $fields = $component->get('fields');
    $firstKey = $fields[0]['key'];

    $component
        ->call('removeField', $firstKey)
        ->assertCount('fields', 1);

    $remaining = $component->get('fields');
    expect($remaining[0]['type'])->toBe('textarea');
});

test('removing selected field clears selection', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input');

    $key = $component->get('fields')[0]['key'];

    $component
        ->call('removeField', $key)
        ->assertSet('selectedFieldKey', null);
});

// --- Field Settings ---

test('can update field label', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input');

    $key = $component->get('fields')[0]['key'];

    $component->call('updateFieldData', $key, 'label', 'Full Name');

    $fields = $component->get('fields');
    expect($fields[0]['data']['label'])->toBe('Full Name');
});

test('field key auto-generates from label', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input');

    $key = $component->get('fields')[0]['key'];
    $component->call('updateFieldData', $key, 'label', 'Date of Birth');

    $fields = $component->get('fields');
    expect($fields[0]['key'])->toBe('date_of_birth');
});

test('can manually set field key', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input');

    $key = $component->get('fields')[0]['key'];
    $component->call('updateFieldKey', $key, 'custom_name');

    $fields = $component->get('fields');
    expect($fields[0]['key'])->toBe('custom_name');
});

test('can update field required status', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input');

    $key = $component->get('fields')[0]['key'];
    $component->call('updateFieldData', $key, 'is_required', true);

    $fields = $component->get('fields');
    expect($fields[0]['data']['is_required'])->toBeTrue();
});

test('can update column span', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input');

    $key = $component->get('fields')[0]['key'];
    $component->call('updateFieldData', $key, 'column_span', 2);

    $fields = $component->get('fields');
    expect($fields[0]['data']['column_span'])->toBe(2);
});

// --- Options (Select/Radio) ---

test('can add an option to a select field', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'select');

    $key = $component->get('fields')[0]['key'];
    $component->call('addOption', $key);

    $fields = $component->get('fields');
    expect($fields[0]['data']['options'])->toHaveCount(3);
});

test('can remove an option from a select field', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'select');

    $key = $component->get('fields')[0]['key'];
    $component->call('removeOption', $key, 0);

    $fields = $component->get('fields');
    expect($fields[0]['data']['options'])->toHaveCount(1);
});

// --- Saving ---

test('can save fields to database', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('addField', 'textarea')
        ->call('save');

    $form->refresh();
    expect($form->fields)->toHaveCount(2)
        ->and($form->fields[0]['type'])->toBe('text-input')
        ->and($form->fields[1]['type'])->toBe('textarea');
});

test('saving clears unsaved changes flag', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->assertSet('hasUnsavedChanges', true)
        ->call('save')
        ->assertSet('hasUnsavedChanges', false);
});

test('saved fields are loaded on page mount', function () {
    [, , $project, $form] = setUpBuilderTest();

    $form->update([
        'fields' => [
            ['type' => 'text-input', 'key' => 'name', 'sort' => 0, 'data' => FormFieldType::TextInput->defaultData()],
            ['type' => 'textarea', 'key' => 'bio', 'sort' => 1, 'data' => FormFieldType::Textarea->defaultData()],
        ],
    ]);

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    $component->assertCount('fields', 2);
    $fields = $component->get('fields');
    expect($fields[0]['key'])->toBe('name')
        ->and($fields[1]['key'])->toBe('bio');
});

test('saving empty form is allowed', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('save');

    $form->refresh();
    expect($form->fields)->toBe([]);
});

// --- Reordering ---

test('can reorder fields via sort', function () {
    [, , $project, $form] = setUpBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'text-input')
        ->call('addField', 'textarea')
        ->call('addField', 'number');

    $fields = $component->get('fields');
    $thirdKey = $fields[2]['key'];

    // Move third field to first position
    $component->call('handleSort', $thirdKey, 0);

    $reordered = $component->get('fields');
    expect($reordered[0]['type'])->toBe('number')
        ->and($reordered[1]['type'])->toBe('text-input')
        ->and($reordered[2]['type'])->toBe('textarea');
});

// --- Undo/Redo ---

test('can undo to previous saved version', function () {
    [, , $project, $form] = setUpBuilderTest();

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
        ->assertCount('fields', 0);
});

test('can redo after undo', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('save')
        ->call('addField', 'text-input')
        ->call('save')
        ->assertCount('fields', 1)
        ->call('undo')
        ->assertCount('fields', 0)
        ->call('redo')
        ->assertCount('fields', 1);
});

// --- Reset ---

test('reset restores last saved state', function () {
    [, , $project, $form] = setUpBuilderTest();

    $form->update([
        'fields' => [
            ['type' => 'text-input', 'key' => 'name', 'sort' => 0, 'data' => FormFieldType::TextInput->defaultData()],
        ],
    ]);

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertCount('fields', 1)
        ->call('addField', 'textarea')
        ->assertCount('fields', 2)
        ->call('resetForm')
        ->assertCount('fields', 1)
        ->assertSet('hasUnsavedChanges', false);
});

// --- Column Layout ---

test('can change column count', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertSet('columns', 2)
        ->call('updateColumns', 3)
        ->assertSet('columns', 3);
});

test('column count is clamped between 1 and 4', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('updateColumns', 0)
        ->assertSet('columns', 1)
        ->call('updateColumns', 10)
        ->assertSet('columns', 4);
});

// --- Tabs ---

test('can switch between builder and preview tabs', function () {
    [, , $project, $form] = setUpBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertSet('activeTab', 'builder')
        ->call('setActiveTab', 'preview')
        ->assertSet('activeTab', 'preview');
});
