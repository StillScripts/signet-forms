<?php

use App\Enums\FormFieldType;
use App\Enums\FormTemplateCategory;
use App\Enums\TeamRole;
use App\Filament\Pages\TemplateLibrary;
use App\Models\Form;
use App\Models\FormTemplate;
use App\Models\FormVersion;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\FormTemplateSeeder;
use Livewire\Livewire;

function setUpTemplateLibrary(): array
{
    $user = User::factory()->create();
    $team = $user->currentTeam;

    test()->actingAs($user);
    test()->setUpFilamentPanel($team);

    return [$user, $team];
}

// --- Model ---

test('form template has uuid primary key', function () {
    $template = FormTemplate::factory()->create();

    expect($template->id)->toBeString()
        ->and(strlen($template->id))->toBe(36);
});

test('form template auto-generates slug from name', function () {
    $template = FormTemplate::factory()->create([
        'name' => 'My Custom Template',
        'slug' => null,
    ]);

    expect($template->fresh()->slug)->toBe('my-custom-template');
});

test('form template casts schema to array', function () {
    $schema = ['pages' => [[
        'id' => fake()->uuid(),
        'title' => null,
        'heading' => null,
        'subheading' => null,
        'submit_button_text' => null,
        'fields' => [
            ['type' => 'text-input', 'key' => 'name', 'sort' => 0, 'data' => ['label' => 'Name']],
        ],
    ]]];

    $template = FormTemplate::factory()->create(['schema' => $schema]);

    expect($template->fresh()->schema)->toBeArray()
        ->and($template->fresh()->schema['pages'])->toHaveCount(1);
});

test('form template casts category to enum', function () {
    $template = FormTemplate::factory()->create(['category' => FormTemplateCategory::Healthcare]);

    expect($template->fresh()->category)->toBe(FormTemplateCategory::Healthcare);
});

// --- Seeder ---

test('form template seeder creates 12 templates', function () {
    (new FormTemplateSeeder)->run();

    expect(FormTemplate::count())->toBe(12);
});

test('form template seeder covers all categories', function () {
    (new FormTemplateSeeder)->run();

    $categories = FormTemplate::pluck('category')->unique()->sort()->values();

    expect($categories)->toContain(FormTemplateCategory::General)
        ->toContain(FormTemplateCategory::Business)
        ->toContain(FormTemplateCategory::Education)
        ->toContain(FormTemplateCategory::Events)
        ->toContain(FormTemplateCategory::Government)
        ->toContain(FormTemplateCategory::Healthcare)
        ->toContain(FormTemplateCategory::Nonprofit)
        ->toContain(FormTemplateCategory::Feedback);
});

test('form template seeder is idempotent', function () {
    $seeder = new FormTemplateSeeder;
    $seeder->run();
    $seeder->run();

    expect(FormTemplate::count())->toBe(12);
});

test('seeded templates have valid schema structure', function () {
    (new FormTemplateSeeder)->run();

    FormTemplate::all()->each(function (FormTemplate $template) {
        expect($template->schema)->toHaveKey('pages')
            ->and($template->schema['pages'])->toBeArray()->not->toBeEmpty();

        $page = $template->schema['pages'][0];
        expect($page)->toHaveKeys(['id', 'title', 'heading', 'subheading', 'submit_button_text', 'fields'])
            ->and($page['fields'])->toBeArray()->not->toBeEmpty();

        foreach ($page['fields'] as $field) {
            expect($field)->toHaveKeys(['type', 'key', 'sort', 'data']);
            expect(FormFieldType::tryFrom($field['type']))->not->toBeNull(
                "Invalid field type '{$field['type']}' in template '{$template->name}'"
            );
        }
    });
});

// --- Template Library Page ---

test('guests cannot access template library', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->get(route('filament.admin.pages.template-library', ['tenant' => $team->slug]))
        ->assertRedirect();
});

test('authenticated users can access template library', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->get(route('filament.admin.pages.template-library', ['tenant' => $team->slug]))
        ->assertOk();
});

test('template library displays seeded templates', function () {
    (new FormTemplateSeeder)->run();
    setUpTemplateLibrary();

    Livewire::test(TemplateLibrary::class)
        ->assertSee('Contact Us')
        ->assertSee('Feedback Survey')
        ->assertSee('Event Registration');
});

test('template library filters by category', function () {
    (new FormTemplateSeeder)->run();
    setUpTemplateLibrary();

    $component = Livewire::test(TemplateLibrary::class)
        ->set('categoryFilter', FormTemplateCategory::Healthcare->value);

    $templates = $component->instance()->getTemplates();

    expect($templates)->toHaveCount(1)
        ->and($templates->first()->name)->toBe('Patient Intake');
});

test('template library filters by search', function () {
    (new FormTemplateSeeder)->run();
    setUpTemplateLibrary();

    $component = Livewire::test(TemplateLibrary::class)
        ->set('search', 'Bug');

    $templates = $component->instance()->getTemplates();

    expect($templates)->toHaveCount(1)
        ->and($templates->first()->name)->toBe('Bug Report');
});

test('template library shows all templates when no filter applied', function () {
    (new FormTemplateSeeder)->run();
    setUpTemplateLibrary();

    $component = Livewire::test(TemplateLibrary::class);
    $templates = $component->instance()->getTemplates();

    expect($templates)->toHaveCount(12);
});

test('template library counts fields correctly', function () {
    $template = FormTemplate::factory()->create([
        'schema' => ['pages' => [
            [
                'id' => fake()->uuid(),
                'title' => null,
                'heading' => null,
                'subheading' => null,
                'submit_button_text' => null,
                'fields' => [
                    ['type' => 'text-input', 'key' => 'a', 'sort' => 0, 'data' => []],
                    ['type' => 'text-input', 'key' => 'b', 'sort' => 1, 'data' => []],
                ],
            ],
            [
                'id' => fake()->uuid(),
                'title' => null,
                'heading' => null,
                'subheading' => null,
                'submit_button_text' => null,
                'fields' => [
                    ['type' => 'textarea', 'key' => 'c', 'sort' => 0, 'data' => []],
                ],
            ],
        ]],
    ]);

    setUpTemplateLibrary();

    $page = Livewire::test(TemplateLibrary::class)->instance();

    expect($page->getFieldCount($template))->toBe(3);
});

// --- Use Template Action ---

test('user with create permission can create form from template', function () {
    $template = FormTemplate::factory()->create([
        'name' => 'Test Template',
        'schema' => ['pages' => [[
            'id' => fake()->uuid(),
            'title' => null,
            'heading' => null,
            'subheading' => null,
            'submit_button_text' => null,
            'fields' => [
                ['type' => 'text-input', 'key' => 'name', 'sort' => 0, 'data' => FormFieldType::TextInput->defaultData()],
            ],
        ]]],
    ]);

    [$user, $team] = setUpTemplateLibrary();
    $project = Project::factory()->create(['team_id' => $team->id]);

    Livewire::test(TemplateLibrary::class)
        ->callAction('createFromTemplate', data: [
            'name' => 'My Contact Form',
            'project_id' => $project->id,
        ], arguments: ['template_id' => $template->id])
        ->assertHasNoActionErrors();

    $form = Form::where('project_id', $project->id)->where('name', 'My Contact Form')->first();

    expect($form)->not->toBeNull()
        ->and($form->schema)->toBe($template->schema)
        ->and($form->is_published)->toBeFalse();
});

test('form created from template gets a version record', function () {
    $template = FormTemplate::factory()->create();

    [$user, $team] = setUpTemplateLibrary();
    $project = Project::factory()->create(['team_id' => $team->id]);

    Livewire::test(TemplateLibrary::class)
        ->callAction('createFromTemplate', data: [
            'name' => 'From Template',
            'project_id' => $project->id,
        ], arguments: ['template_id' => $template->id])
        ->assertHasNoActionErrors();

    $form = Form::where('name', 'From Template')->first();
    $version = FormVersion::where('form_id', $form->id)->first();

    expect($version)->not->toBeNull()
        ->and($version->version)->toBe(1)
        ->and($version->schema)->toBe($template->schema);
});

test('user without create permission cannot use template', function () {
    $template = FormTemplate::factory()->create();

    $owner = User::factory()->create();
    $team = $owner->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);

    $viewer = User::factory()->create();
    $team->members()->attach($viewer, ['role' => TeamRole::Viewer->value]);
    $viewer->switchTeam($team);

    test()->actingAs($viewer);
    test()->setUpFilamentPanel($team);

    Livewire::test(TemplateLibrary::class)
        ->assertActionHidden('createFromTemplate');
});

test('form name is required when creating from template', function () {
    $template = FormTemplate::factory()->create();

    [$user, $team] = setUpTemplateLibrary();
    $project = Project::factory()->create(['team_id' => $team->id]);

    Livewire::test(TemplateLibrary::class)
        ->callAction('createFromTemplate', data: [
            'name' => '',
            'project_id' => $project->id,
        ], arguments: ['template_id' => $template->id])
        ->assertHasActionErrors(['name' => 'required']);
});

test('project is required when creating from template', function () {
    $template = FormTemplate::factory()->create();

    [$user, $team] = setUpTemplateLibrary();

    Livewire::test(TemplateLibrary::class)
        ->callAction('createFromTemplate', data: [
            'name' => 'Test Form',
            'project_id' => null,
        ], arguments: ['template_id' => $template->id])
        ->assertHasActionErrors(['project_id' => 'required']);
});

// --- FormTemplateCategory Enum ---

test('all categories have a label', function () {
    foreach (FormTemplateCategory::cases() as $category) {
        expect($category->label())->toBeString()->not->toBeEmpty();
    }
});

test('all categories have an icon', function () {
    foreach (FormTemplateCategory::cases() as $category) {
        expect($category->icon())->not->toBeNull();
    }
});

test('all categories have a color', function () {
    foreach (FormTemplateCategory::cases() as $category) {
        expect($category->color())->toBeString()->not->toBeEmpty();
    }
});
