<?php

use App\Enums\FormFieldType;
use App\Filament\Resources\Projects\Resources\Forms\Pages\FormBuilderPage;
use App\Livewire\PublicFormPage;
use App\Models\Form;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use Livewire\Livewire;

test('all layout element types report isLayout true', function () {
    expect(FormFieldType::SectionHeader->isLayout())->toBeTrue()
        ->and(FormFieldType::Divider->isLayout())->toBeTrue()
        ->and(FormFieldType::InstructionalText->isLayout())->toBeTrue()
        ->and(FormFieldType::Image->isLayout())->toBeTrue();
});

test('data field types report isLayout false', function () {
    expect(FormFieldType::TextInput->isLayout())->toBeFalse()
        ->and(FormFieldType::Select->isLayout())->toBeFalse()
        ->and(FormFieldType::FileUpload->isLayout())->toBeFalse();
});

test('grouped field types includes Layout category with all four types', function () {
    $grouped = FormFieldType::grouped();

    expect($grouped)->toHaveKey('Layout')
        ->and($grouped['Layout'])->toContain(FormFieldType::SectionHeader)
        ->and($grouped['Layout'])->toContain(FormFieldType::Divider)
        ->and($grouped['Layout'])->toContain(FormFieldType::InstructionalText)
        ->and($grouped['Layout'])->toContain(FormFieldType::Image);
});

test('layout element defaults omit data-field keys', function () {
    $headerDefaults = FormFieldType::SectionHeader->defaultData();

    expect($headerDefaults)->toHaveKey('heading')
        ->and($headerDefaults)->toHaveKey('column_span')
        ->and($headerDefaults)->not->toHaveKey('label')
        ->and($headerDefaults)->not->toHaveKey('placeholder')
        ->and($headerDefaults)->not->toHaveKey('is_required');
});

test('builder adds a section header element', function () {
    [, , $project, $form] = setUpLayoutBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', FormFieldType::SectionHeader->value);

    $fields = $component->get('fields');
    expect($fields)->toHaveCount(1)
        ->and($fields[0]['type'])->toBe('section-header')
        ->and($fields[0]['data']['heading'])->toBe('Section Heading');
});

test('builder adds all four layout element types', function () {
    [, , $project, $form] = setUpLayoutBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'section-header')
        ->call('addField', 'divider')
        ->call('addField', 'instructional-text')
        ->call('addField', 'image');

    $fields = $component->get('fields');
    expect($fields)->toHaveCount(4)
        ->and(collect($fields)->pluck('type')->all())->toBe([
            'section-header', 'divider', 'instructional-text', 'image',
        ]);
});

test('layout elements persist through save and reload', function () {
    [, , $project, $form] = setUpLayoutBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'section-header')
        ->call('addField', 'text-input')
        ->call('addField', 'divider')
        ->call('save');

    $form->refresh();
    $fields = $form->schema['pages'][0]['fields'];

    expect($fields)->toHaveCount(3)
        ->and($fields[0]['type'])->toBe('section-header')
        ->and($fields[1]['type'])->toBe('text-input')
        ->and($fields[2]['type'])->toBe('divider');

    $reload = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ]);

    expect($reload->get('fields'))->toHaveCount(3);
});

test('public form renders section header heading and subheading', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => makeLayoutFormSchema([
            makeLayoutField(FormFieldType::SectionHeader, 'header_1', [
                'heading' => 'Contact Details',
                'subheading' => 'Tell us how to reach you',
            ]),
        ]),
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->assertSee('Contact Details')
        ->assertSee('Tell us how to reach you');
});

test('public form renders instructional text content', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => makeLayoutFormSchema([
            makeLayoutField(FormFieldType::InstructionalText, 'info_1', [
                'content' => 'Please answer honestly; responses are confidential.',
            ]),
        ]),
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->assertSee('Please answer honestly; responses are confidential.');
});

test('submission data excludes layout element keys', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => makeLayoutFormSchema([
            makeLayoutField(FormFieldType::SectionHeader, 'header_1', ['heading' => 'About You']),
            makeLayoutField(FormFieldType::InstructionalText, 'info_1', ['content' => 'Fill these out.']),
            [
                'type' => 'text-input',
                'key' => 'name',
                'sort' => 2,
                'data' => array_merge(FormFieldType::TextInput->defaultData(), ['label' => 'Name']),
            ],
            makeLayoutField(FormFieldType::Divider, 'divider_1'),
            [
                'type' => 'text-input',
                'key' => 'email',
                'sort' => 4,
                'data' => array_merge(FormFieldType::TextInput->defaultData(), ['label' => 'Email']),
            ],
        ]),
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->set('data.name', 'Jane Doe')
        ->set('data.email', 'jane@example.com')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::where('form_id', $form->id)->firstOrFail();

    expect(array_keys($submission->data))->toBe(['name', 'email'])
        ->and($submission->data)->not->toHaveKey('header_1')
        ->and($submission->data)->not->toHaveKey('info_1')
        ->and($submission->data)->not->toHaveKey('divider_1');
});

test('layout elements do not trigger required validation errors', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => makeLayoutFormSchema([
            makeLayoutField(FormFieldType::SectionHeader, 'header_1', ['heading' => 'Required Section']),
            makeLayoutField(FormFieldType::Divider, 'divider_1'),
            makeLayoutField(FormFieldType::InstructionalText, 'info_1', ['content' => 'Must read']),
            makeLayoutField(FormFieldType::Image, 'image_1', ['url' => 'https://example.com/x.png', 'alt' => 'example']),
        ]),
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect(Submission::where('form_id', $form->id)->count())->toBe(1);
});

test('required data field co-existing with layout elements still validates', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => makeLayoutFormSchema([
            makeLayoutField(FormFieldType::SectionHeader, 'header_1', ['heading' => 'Intro']),
            [
                'type' => 'text-input',
                'key' => 'name',
                'sort' => 1,
                'data' => array_merge(FormFieldType::TextInput->defaultData(), [
                    'label' => 'Name',
                    'is_required' => true,
                ]),
            ],
        ]),
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->call('submit')
        ->assertHasErrors(['data.name' => 'required']);

    expect(Submission::where('form_id', $form->id)->count())->toBe(0);
});

test('image layout element renders alt text on public form', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => makeLayoutFormSchema([
            makeLayoutField(FormFieldType::Image, 'image_1', [
                'url' => 'https://example.com/logo.png',
                'alt' => 'Company logo banner',
            ]),
        ]),
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->assertSeeHtml('Company logo banner')
        ->assertSeeHtml('https://example.com/logo.png');
});

test('public form omits image layout when URL is empty', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'schema' => makeLayoutFormSchema([
            makeLayoutField(FormFieldType::Image, 'image_1', [
                'url' => '',
                'alt' => 'Placeholder alt must not appear alone',
            ]),
        ]),
    ]);

    Livewire::test(PublicFormPage::class, [
        'team' => $team,
        'formSlug' => $form->slug,
    ])
        ->assertDontSee('No image URL provided')
        ->assertDontSee('Placeholder alt must not appear alone');
});

test('builder preview shows placeholder when image URL is empty', function () {
    [, , $project, $form] = setUpLayoutBuilderTest();

    Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', FormFieldType::Image->value)
        ->call('setActiveTab', 'preview')
        ->assertSee('No image URL provided');
});

test('builder updates section header heading via settings', function () {
    [, , $project, $form] = setUpLayoutBuilderTest();

    $component = Livewire::test(FormBuilderPage::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->call('addField', 'section-header');

    $key = $component->get('fields')[0]['key'];

    $component->call('updateFieldData', $key, 'heading', 'New Heading');

    expect($component->get('fields')[0]['data']['heading'])->toBe('New Heading');
});
