<?php

use App\Enums\TeamRole;
use App\Filament\Resources\Projects\Resources\Forms\Pages\ViewForm;
use App\Models\Form;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\QrCodeService;
use Livewire\Livewire;

// --- QrCodeService ---

test('qr code service generates valid svg', function () {
    $service = new QrCodeService;
    $svg = $service->generateSvg('https://example.com/test');

    expect($svg)->toBeString()
        ->and($svg)->toContain('<svg')
        ->and($svg)->toContain('</svg>');
});

test('qr code service generates png binary data', function () {
    $service = new QrCodeService;
    $png = $service->generatePng('https://example.com/test');

    expect($png)->toBeString()
        ->and(strlen($png))->toBeGreaterThan(0);
});

// --- QR Code Download Route ---

test('authenticated user can download qr code as png', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('forms.qr-code', ['form' => $form->id, 'format' => 'png']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

test('authenticated user can download qr code as svg', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('forms.qr-code', ['form' => $form->id, 'format' => 'svg']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

test('qr code download returns 404 for unpublished forms', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => false,
    ]);

    $response = $this->actingAs($user)
        ->get(route('forms.qr-code', ['form' => $form->id, 'format' => 'png']));

    $response->assertNotFound();
});

test('qr code download returns 404 for invalid format', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)
        ->get("/forms/{$form->id}/qr-code/webp");

    $response->assertNotFound();
});

test('unauthenticated user cannot download qr code', function () {
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
    ]);

    $response = $this->getJson(route('forms.qr-code', ['form' => $form->id, 'format' => 'png']));

    $response->assertUnauthorized();
});

test('non-team member cannot download qr code', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('forms.qr-code', ['form' => $form->id, 'format' => 'png']));

    $response->assertForbidden();
});

test('qr code download has correct content-disposition header', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'is_published' => true,
        'name' => 'Contact Form',
        'slug' => 'contact-form',
    ]);

    $response = $this->actingAs($user)
        ->get(route('forms.qr-code', ['form' => $form->id, 'format' => 'png']));

    $response->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="qr-code-contact-form.png"');
});

// --- Filament View Page ---

test('view page shows qr code section for published forms', function () {
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
        ->assertSee('QR Code');
});

test('view page hides qr code section for unpublished forms', function () {
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
        ->assertDontSee('QR Code');
});
