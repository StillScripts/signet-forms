<?php

use App\Enums\AuditAction;
use App\Enums\SubmissionExportStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Jobs\ProcessBulkExport;
use App\Mail\SubmissionExportReady;
use App\Models\AuditLog;
use App\Models\Form;
use App\Models\Project;
use App\Models\Submission;
use App\Models\SubmissionExport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

// --- Enum ---

test('all export statuses have labels and colors', function () {
    foreach (SubmissionExportStatus::cases() as $status) {
        expect($status->label())->toBeString()->not->toBeEmpty();
        expect($status->color())->toBeString()->not->toBeEmpty();
    }
});

// --- Model ---

test('submission export belongs to team, form, and requester', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    $export = SubmissionExport::factory()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
    ]);

    expect($export->team->id)->toBe($user->currentTeam->id);
    expect($export->form->id)->toBe($form->id);
    expect($export->requester->id)->toBe($user->id);
});

test('export is downloadable when completed with file and non-expired', function () {
    $export = SubmissionExport::factory()->completed()->create();

    expect($export->isDownloadable())->toBeTrue();
});

test('export is not downloadable when expired', function () {
    $export = SubmissionExport::factory()->completed()->create([
        'expires_at' => now()->subDay(),
    ]);

    expect($export->isDownloadable())->toBeFalse();
});

test('export is not downloadable when pending', function () {
    $export = SubmissionExport::factory()->create([
        'status' => SubmissionExportStatus::Pending,
        'expires_at' => now()->addDay(),
    ]);

    expect($export->isDownloadable())->toBeFalse();
});

// --- ProcessBulkExport job ---

test('job writes a csv file with all submissions for the form', function () {
    Storage::fake('local');
    Mail::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'schema' => [
            'pages' => [[
                'id' => 'p1', 'title' => null, 'heading' => null, 'subheading' => null,
                'submit_button_text' => null,
                'fields' => [
                    ['type' => 'text-input', 'key' => 'name', 'sort' => 0, 'data' => []],
                    ['type' => 'text-input', 'key' => 'email', 'sort' => 1, 'data' => []],
                ],
            ]],
        ],
    ]);

    Submission::factory()->create([
        'form_id' => $form->id,
        'data' => ['name' => 'Alice', 'email' => 'alice@example.com'],
    ]);
    Submission::factory()->create([
        'form_id' => $form->id,
        'data' => ['name' => 'Bob', 'email' => 'bob@example.com'],
    ]);

    $export = SubmissionExport::factory()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
    ]);

    (new ProcessBulkExport($export->id))->handle();

    $export->refresh();

    expect($export->status)->toBe(SubmissionExportStatus::Completed);
    expect($export->row_count)->toBe(2);
    expect($export->file_path)->not->toBeNull();
    expect($export->expires_at)->not->toBeNull();

    $content = Storage::disk('local')->get($export->file_path);
    expect($content)->toContain('name')->toContain('email');
    expect($content)->toContain('Alice')->toContain('Bob');
});

test('job sanitises csv cells that could be interpreted as formulas', function () {
    Storage::fake('local');
    Mail::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create([
        'project_id' => $project->id,
        'schema' => [
            'pages' => [[
                'id' => 'p1', 'title' => null, 'heading' => null, 'subheading' => null,
                'submit_button_text' => null,
                'fields' => [['type' => 'text-input', 'key' => 'payload', 'sort' => 0, 'data' => []]],
            ]],
        ],
    ]);

    Submission::factory()->create([
        'form_id' => $form->id,
        'data' => ['payload' => '=cmd|calc'],
        'respondent_name' => '+lookup()',
    ]);

    $export = SubmissionExport::factory()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
    ]);

    (new ProcessBulkExport($export->id))->handle();

    $content = Storage::disk('local')->get($export->refresh()->file_path);
    expect($content)->toContain("'=cmd|calc");
    expect($content)->toContain("'+lookup()");
});

test('job filters submissions by status', function () {
    Storage::fake('local');
    Mail::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    Submission::factory()->create([
        'form_id' => $form->id,
        'status' => SubmissionStatus::Approved,
        'data' => ['x' => 'keep'],
    ]);
    Submission::factory()->create([
        'form_id' => $form->id,
        'status' => SubmissionStatus::Rejected,
        'data' => ['x' => 'drop'],
    ]);

    $export = SubmissionExport::factory()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
        'filters' => ['statuses' => [SubmissionStatus::Approved->value]],
    ]);

    (new ProcessBulkExport($export->id))->handle();

    $export->refresh();
    expect($export->row_count)->toBe(1);
});

test('job sends email to requester with signed download link', function () {
    Storage::fake('local');
    Mail::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    $export = SubmissionExport::factory()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
    ]);

    (new ProcessBulkExport($export->id))->handle();

    Mail::assertSent(SubmissionExportReady::class, fn (SubmissionExportReady $mail) => $mail->hasTo($user->email));
});

test('job writes an audit log entry for the export', function () {
    Storage::fake('local');
    Mail::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    $export = SubmissionExport::factory()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
        'reason' => 'Quarterly compliance review',
    ]);

    AuditLog::query()->delete();

    (new ProcessBulkExport($export->id))->handle();

    $log = AuditLog::where('action', AuditAction::SubmissionExported)->first();
    expect($log)->not->toBeNull();
    expect($log->details['reason'])->toBe('Quarterly compliance review');
    expect($log->details['form_id'])->toBe($form->id);
});

// --- Download route ---

test('download route returns the file for the requester', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    Storage::disk('local')->put('exports/test.csv', "id,name\n1,Alice\n");

    $export = SubmissionExport::factory()->completed()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
        'file_path' => 'exports/test.csv',
    ]);

    $url = URL::temporarySignedRoute('exports.download', now()->addDay(), ['export' => $export->id]);

    $this->actingAs($user);
    $this->get($url)->assertOk();
});

test('download route 403 when another user tries to download', function () {
    Storage::fake('local');

    $requester = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $requester->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    Storage::disk('local')->put('exports/secret.csv', 'data');

    $export = SubmissionExport::factory()->completed()->create([
        'team_id' => $requester->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $requester->id,
        'file_path' => 'exports/secret.csv',
    ]);

    $url = URL::temporarySignedRoute('exports.download', now()->addDay(), ['export' => $export->id]);

    $this->actingAs($other);
    $this->get($url)->assertForbidden();
});

test('download route 410 when export is expired', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    Storage::disk('local')->put('exports/expired.csv', 'data');

    $export = SubmissionExport::factory()->completed()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
        'file_path' => 'exports/expired.csv',
        'expires_at' => now()->subDay(),
    ]);

    $url = URL::temporarySignedRoute('exports.download', now()->addDay(), ['export' => $export->id]);

    $this->actingAs($user);
    $this->get($url)->assertStatus(410);
});

test('download route 403 when the signed URL is missing', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);
    $export = SubmissionExport::factory()->completed()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);
    $this->get(route('exports.download', ['export' => $export->id]))
        ->assertForbidden();
});

// --- Permission ---

test('owner has export permission', function () {
    $user = User::factory()->create();

    expect($user->hasTeamPermission($user->currentTeam, TeamPermission::ExportSubmission))->toBeTrue();
});

test('viewer does not have export permission', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Viewer->value]);

    expect($user->hasTeamPermission($team, TeamPermission::ExportSubmission))->toBeFalse();
});

// --- Cleanup command ---

test('cleanup command deletes expired exports and their files', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    Storage::disk('local')->put('exports/old.csv', 'old');
    Storage::disk('local')->put('exports/new.csv', 'new');

    SubmissionExport::factory()->completed()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
        'file_path' => 'exports/old.csv',
        'expires_at' => now()->subDay(),
    ]);
    SubmissionExport::factory()->completed()->create([
        'team_id' => $user->currentTeam->id,
        'form_id' => $form->id,
        'requested_by' => $user->id,
        'file_path' => 'exports/new.csv',
        'expires_at' => now()->addDay(),
    ]);

    $this->artisan('exports:clean')->assertSuccessful();

    expect(SubmissionExport::count())->toBe(1);
    expect(Storage::disk('local')->exists('exports/old.csv'))->toBeFalse();
    expect(Storage::disk('local')->exists('exports/new.csv'))->toBeTrue();
});
