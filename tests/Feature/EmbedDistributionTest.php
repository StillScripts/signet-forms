<?php

use App\Enums\TeamRole;
use App\Filament\Resources\Projects\Resources\Forms\Pages\ViewForm;
use App\Models\Form;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\ValueObjects\FormSettings;
use App\ValueObjects\Settings\EmbedSettings;
use Livewire\Livewire;

// --- EmbedSettings value object ---

test('embed settings have sensible defaults', function () {
    $settings = new EmbedSettings;

    expect($settings->allowEmbedding)->toBeTrue()
        ->and($settings->resolvedWidth())->toBe('100%')
        ->and($settings->resolvedHeight())->toBe(600);
});

test('form settings round trip through embed section', function () {
    $payload = [
        'embed' => [
            'allow_embedding' => false,
            'width' => '800px',
            'height' => 500,
        ],
    ];

    $settings = FormSettings::fromArray($payload);

    expect($settings->embed->allowEmbedding)->toBeFalse()
        ->and($settings->embed->width)->toBe('800px')
        ->and($settings->embed->height)->toBe(500);

    $array = $settings->toArray();
    expect($array['embed'])->toBe($payload['embed']);
});

test('form settings default embed section when missing', function () {
    $settings = FormSettings::fromArray([]);

    expect($settings->embed->allowEmbedding)->toBeTrue()
        ->and($settings->embed->resolvedWidth())->toBe('100%')
        ->and($settings->embed->resolvedHeight())->toBe(600);
});

// --- Filament view page ---

test('view page shows embed section for published forms', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewForm::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertOk()
        ->assertSee('Embed')
        ->assertSee('iframe')
        ->assertSee('?embed=1');
});

test('view page hides embed section for unpublished forms', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => false,
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewForm::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertOk()
        ->assertDontSee('iframe');
});

test('view page hides embed section when embedding is disabled', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'settings' => [
            'embed' => [
                'allow_embedding' => false,
                'width' => '100%',
                'height' => 600,
            ],
        ],
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewForm::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertOk()
        ->assertDontSee('iframe');
});

test('embed snippet uses configured width and height', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'settings' => [
            'embed' => [
                'allow_embedding' => true,
                'width' => '720px',
                'height' => 480,
            ],
        ],
    ]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    Livewire::test(ViewForm::class, [
        'parentRecord' => $project,
        'record' => $form->getRouteKey(),
    ])
        ->assertOk()
        ->assertSee('720px')
        ->assertSee('480');
});

// --- Public form embed variant ---

test('public form responds ok with embed query param', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
    ]);

    $response = $this->get(route('forms.show', [
        'team' => $team,
        'formSlug' => $form->slug,
    ]).'?embed=1');

    $response->assertOk();
});

test('public form sends deny frame options when embedding disabled', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'settings' => [
            'embed' => [
                'allow_embedding' => false,
                'width' => '100%',
                'height' => 600,
            ],
        ],
    ]);

    $response = $this->get(route('forms.show', [
        'team' => $team,
        'formSlug' => $form->slug,
    ]));

    $response->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY');
});

test('public form allows framing by default', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
    ]);

    $response = $this->get(route('forms.show', [
        'team' => $team,
        'formSlug' => $form->slug,
    ]));

    $response->assertOk();
    expect($response->headers->get('X-Frame-Options'))->toBeNull();
});

test('unpublished form returns 404 even with embed query param', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => false,
    ]);

    $response = $this->get(route('forms.show', [
        'team' => $team,
        'formSlug' => $form->slug,
    ]).'?embed=1');

    $response->assertNotFound();
});
