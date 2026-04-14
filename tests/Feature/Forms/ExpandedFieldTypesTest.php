<?php

use App\Enums\FieldCategory;
use App\Enums\FieldComponentRenderTarget;
use App\Enums\FormFieldType;
use App\Filament\Resources\Projects\Resources\Forms\Pages\FormBuilderPage;
use App\Livewire\PublicFormPage;
use App\Models\Form;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Services\FieldComponentBuilder;
use Livewire\Livewire;

// --- FieldCategory coverage ---

test('every field type belongs to a FieldCategory', function () {
    foreach (FormFieldType::cases() as $type) {
        expect($type->category())->toBeInstanceOf(FieldCategory::class);
    }
});

test('grouped field types returns keys for every category in order', function () {
    $grouped = FormFieldType::grouped();

    expect(array_keys($grouped))->toBe([
        FieldCategory::Basic->label(),
        FieldCategory::Choice->label(),
        FieldCategory::Advanced->label(),
        FieldCategory::Layout->label(),
    ]);
});

test('basic category contains expected types', function () {
    expect(FormFieldType::TextInput->category())->toBe(FieldCategory::Basic)
        ->and(FormFieldType::Email->category())->toBe(FieldCategory::Basic)
        ->and(FormFieldType::Phone->category())->toBe(FieldCategory::Basic)
        ->and(FormFieldType::MarkdownEditor->category())->toBe(FieldCategory::Basic);
});

test('choice category contains expected types', function () {
    expect(FormFieldType::Select->category())->toBe(FieldCategory::Choice)
        ->and(FormFieldType::MultiSelect->category())->toBe(FieldCategory::Choice)
        ->and(FormFieldType::CheckboxList->category())->toBe(FieldCategory::Choice)
        ->and(FormFieldType::ToggleButtons->category())->toBe(FieldCategory::Choice)
        ->and(FormFieldType::YesNo->category())->toBe(FieldCategory::Choice)
        ->and(FormFieldType::Rating->category())->toBe(FieldCategory::Choice)
        ->and(FormFieldType::Ranking->category())->toBe(FieldCategory::Choice);
});

test('advanced category contains expected types', function () {
    expect(FormFieldType::DateTimePicker->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::Time->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::DateRange->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::Signature->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::Address->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::Slider->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::ColorPicker->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::TagsInput->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::KeyValue->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::CodeEditor->category())->toBe(FieldCategory::Advanced)
        ->and(FormFieldType::Repeater->category())->toBe(FieldCategory::Advanced);
});

// --- Enum coverage: each case has label, description, icon, defaults ---

test('every field type exposes label, description, icon and default data', function (FormFieldType $type) {
    expect($type->label())->toBeString()->not->toBeEmpty()
        ->and($type->description())->toBeString()->not->toBeEmpty()
        ->and($type->icon())->not->toBeNull()
        ->and($type->defaultData())->toBeArray();
})->with(FormFieldType::cases());

test('type-specific defaults cover configuration knobs', function () {
    expect(FormFieldType::Rating->defaultData())->toHaveKey('max')->toHaveKey('icon')
        ->and(FormFieldType::Slider->defaultData())->toHaveKey('min')->toHaveKey('max')->toHaveKey('step')
        ->and(FormFieldType::CodeEditor->defaultData())->toHaveKey('language')
        ->and(FormFieldType::ColorPicker->defaultData())->toHaveKey('format')
        ->and(FormFieldType::KeyValue->defaultData())->toHaveKey('key_label')->toHaveKey('value_label')
        ->and(FormFieldType::Repeater->defaultData())->toHaveKey('item_label')
        ->and(FormFieldType::TagsInput->defaultData())->toHaveKey('suggestions');
});

// --- Builder adds each new type ---

test('builder adds every new data-field type with correct defaults', function (FormFieldType $type) {
    [, , $project, $form] = setUpLayoutBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', $type->value);

    $fields = $component->get('fields');

    expect($fields)->toHaveCount(1)
        ->and($fields[0]['type'])->toBe($type->value)
        ->and($fields[0]['data']['label'])->toBe($type->label());
})->with([
    FormFieldType::Email,
    FormFieldType::Phone,
    FormFieldType::MarkdownEditor,
    FormFieldType::MultiSelect,
    FormFieldType::CheckboxList,
    FormFieldType::ToggleButtons,
    FormFieldType::YesNo,
    FormFieldType::Rating,
    FormFieldType::Ranking,
    FormFieldType::DateTimePicker,
    FormFieldType::Time,
    FormFieldType::DateRange,
    FormFieldType::Signature,
    FormFieldType::Address,
    FormFieldType::Slider,
    FormFieldType::ColorPicker,
    FormFieldType::TagsInput,
    FormFieldType::KeyValue,
    FormFieldType::CodeEditor,
    FormFieldType::Repeater,
]);

// --- Builder component instantiation (no public-form rendering) ---

test('field component builder produces a component for every non-file-upload data type on public forms', function (FormFieldType $type) {
    $service = app(FieldComponentBuilder::class);

    $component = $service->buildFilamentComponent(
        makeLayoutField($type, 'sample_'.$type->value),
        FieldComponentRenderTarget::PublicForm,
    );

    expect($component)->not->toBeNull();
})->with([
    FormFieldType::Email,
    FormFieldType::Phone,
    FormFieldType::MarkdownEditor,
    FormFieldType::MultiSelect,
    FormFieldType::CheckboxList,
    FormFieldType::ToggleButtons,
    FormFieldType::YesNo,
    FormFieldType::Rating,
    FormFieldType::Ranking,
    FormFieldType::DateTimePicker,
    FormFieldType::Time,
    FormFieldType::DateRange,
    FormFieldType::Signature,
    FormFieldType::Address,
    FormFieldType::Slider,
    FormFieldType::ColorPicker,
    FormFieldType::TagsInput,
    FormFieldType::KeyValue,
    FormFieldType::CodeEditor,
    FormFieldType::Repeater,
]);

test('file upload still returns null on public form render target', function () {
    $service = app(FieldComponentBuilder::class);

    $component = $service->buildFilamentComponent(
        makeLayoutField(FormFieldType::FileUpload, 'attachment'),
        FieldComponentRenderTarget::PublicForm,
    );

    expect($component)->toBeNull();
});

// --- Public form submissions store expected data shape ---

function makePublicFormWithField(FormFieldType $type, string $key, array $data = []): array
{
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => makeLayoutFormSchema([
            makeLayoutField($type, $key, $data),
        ]),
    ]);

    return [$team, $form];
}

test('email field submission stores string value', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::Email, 'email');

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.email', 'respondent@example.com')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data)->toHaveKey('email')
        ->and($submission->data['email'])->toBe('respondent@example.com');
});

test('phone field submission stores string value', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::Phone, 'phone');

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.phone', '+61 400 000 000')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data['phone'])->toBe('+61 400 000 000');
});

test('address field submission stores nested object', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::Address, 'address');

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.address.street', '1 Swanston Street')
        ->set('data.address.suburb', 'Melbourne')
        ->set('data.address.state', 'VIC')
        ->set('data.address.postcode', '3000')
        ->set('data.address.country', 'Australia')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data['address'])->toBe([
        'street' => '1 Swanston Street',
        'suburb' => 'Melbourne',
        'state' => 'VIC',
        'postcode' => '3000',
        'country' => 'Australia',
    ]);
});

test('date range field submission stores start and end', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::DateRange, 'availability');

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.availability.start', '2026-05-01')
        ->set('data.availability.end', '2026-05-10')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data['availability'])->toMatchArray([
        'start' => '2026-05-01',
        'end' => '2026-05-10',
    ]);
});

test('multi select field submission stores array of selected values', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::MultiSelect, 'interests', [
        'options' => [
            ['label' => 'Cats', 'value' => 'cats'],
            ['label' => 'Dogs', 'value' => 'dogs'],
            ['label' => 'Birds', 'value' => 'birds'],
        ],
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.interests', ['cats', 'birds'])
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data['interests'])->toBe(['cats', 'birds']);
});

test('checkbox list field submission stores array of selected values', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::CheckboxList, 'toppings', [
        'options' => [
            ['label' => 'Cheese', 'value' => 'cheese'],
            ['label' => 'Ham', 'value' => 'ham'],
        ],
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.toppings', ['cheese'])
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data['toppings'])->toBe(['cheese']);
});

test('yes no field submission stores yes or no string', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::YesNo, 'consented');

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.consented', 'yes')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data['consented'])->toBe('yes');
});

test('rating field submission stores numeric value', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::Rating, 'score', [
        'max' => 5,
        'icon' => 'star',
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.score', 4)
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect((int) $submission->data['score'])->toBe(4);
});

test('tags input field submission stores list of tags', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::TagsInput, 'skills');

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.skills', ['php', 'laravel', 'pest'])
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data['skills'])->toBe(['php', 'laravel', 'pest']);
});

test('key value field submission stores associative pairs', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::KeyValue, 'headers');

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.headers', ['Authorization' => 'Bearer 123', 'Accept' => 'application/json'])
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data['headers'])->toBe([
        'Authorization' => 'Bearer 123',
        'Accept' => 'application/json',
    ]);
});

test('signature field submission stores data url string', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::Signature, 'signed');

    $dataUrl = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.signed', $dataUrl)
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect($submission->data['signed'])->toStartWith('data:image/png;base64,');
});

test('required new-type field blocks submission when empty', function () {
    [$team, $form] = makePublicFormWithField(FormFieldType::Email, 'email', [
        'is_required' => true,
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->call('submit')
        ->assertHasErrors(['data.email']);

    expect(Submission::where('form_id', $form->id)->count())->toBe(0);
});

test('slider respects configured min max and step defaults', function () {
    $defaults = FormFieldType::Slider->defaultData();

    expect($defaults['min'])->toBe(0)
        ->and($defaults['max'])->toBe(100)
        ->and($defaults['step'])->toBe(1);
});

test('rating defaults to 5 stars', function () {
    $defaults = FormFieldType::Rating->defaultData();

    expect($defaults['max'])->toBe(5)
        ->and($defaults['icon'])->toBe('star');
});
