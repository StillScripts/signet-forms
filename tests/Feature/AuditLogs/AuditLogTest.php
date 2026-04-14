<?php

use App\Enums\AuditAction;
use App\Enums\SubmissionStatus;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Filament\Pages\AuditLogs;
use App\Filament\Resources\Submissions\Pages\ViewSubmission;
use App\Jobs\WriteAuditLog;
use App\Models\AuditLog;
use App\Models\Form;
use App\Models\Project;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

// --- AuditAction enum ---

test('all audit actions have labels and colors', function () {
    foreach (AuditAction::cases() as $action) {
        expect($action->label())->toBeString()->not->toBeEmpty();
        expect($action->color())->toBeString()->not->toBeEmpty();
    }
});

// --- Model ---

test('audit log uses uuid primary key and no updated_at', function () {
    $log = AuditLog::factory()->create();

    expect($log->id)->toBeString();
    expect(strlen($log->id))->toBe(36);
    expect($log->updated_at ?? null)->toBeNull();
});

test('audit log casts action to enum and details to array', function () {
    $log = AuditLog::factory()->create([
        'action' => AuditAction::FormPublished->value,
        'details' => ['name' => 'Contact Us'],
    ]);

    expect($log->fresh()->action)->toBe(AuditAction::FormPublished);
    expect($log->fresh()->details)->toBe(['name' => 'Contact Us']);
});

// --- AuditService ---

test('audit service dispatches a write audit log job', function () {
    Queue::fake();

    AuditService::log(AuditAction::FormCreated);

    Queue::assertPushed(WriteAuditLog::class);
});

test('audit service resolves actor and team from context', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    AuditLog::query()->delete();
    AuditService::log(AuditAction::FormCreated);

    $log = AuditLog::where('action', AuditAction::FormCreated)->first();
    expect($log->user_id)->toBe($user->id);
    expect($log->team_id)->toBe($team->id);
});

test('audit service captures resource type and id', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $team = $user->currentTeam;
    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    AuditService::log(
        action: AuditAction::FormUpdated,
        resource: $form,
        details: ['changed' => ['name']],
    );

    $log = AuditLog::where('action', AuditAction::FormUpdated)->first();
    expect($log->resource_type)->toBe('form');
    expect($log->resource_id)->toBe($form->id);
    expect($log->details)->toBe(['changed' => ['name']]);
});

// --- Observers: Form ---

test('form created writes an audit log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);

    Form::factory()->create(['project_id' => $project->id, 'name' => 'My Form']);

    expect(AuditLog::where('action', AuditAction::FormCreated)->count())->toBe(1);
});

test('form publish writes a published audit log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id, 'is_published' => false]);

    AuditLog::query()->delete();

    $form->update(['is_published' => true]);

    expect(AuditLog::where('action', AuditAction::FormPublished)->count())->toBe(1);
    expect(AuditLog::where('action', AuditAction::FormUpdated)->count())->toBe(0);
});

test('form unpublish writes an unpublished audit log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id, 'is_published' => true]);

    AuditLog::query()->delete();

    $form->update(['is_published' => false]);

    expect(AuditLog::where('action', AuditAction::FormUnpublished)->count())->toBe(1);
});

test('form update with no changes does not write a log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    AuditLog::query()->delete();

    $form->update(['name' => $form->name]);

    expect(AuditLog::where('action', AuditAction::FormUpdated)->count())->toBe(0);
});

test('form deletion writes a deleted audit log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);

    AuditLog::query()->delete();

    $form->delete();

    expect(AuditLog::where('action', AuditAction::FormDeleted)->count())->toBe(1);
});

// --- Observers: Submission ---

test('submission deletion writes a deleted audit log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $project = Project::factory()->create(['team_id' => $user->currentTeam->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create(['form_id' => $form->id]);

    AuditLog::query()->delete();

    $submission->delete();

    $log = AuditLog::where('action', AuditAction::SubmissionDeleted)->first();
    expect($log)->not->toBeNull();
    expect($log->resource_type)->toBe('submission');
});

// --- Observers: Membership ---

test('member added writes an invited audit log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $team = Team::factory()->create();
    $member = User::factory()->create();

    AuditLog::query()->delete();

    $team->members()->attach($member, ['role' => TeamRole::Editor->value]);

    expect(AuditLog::where('action', AuditAction::MemberInvited)->count())->toBe(1);
});

test('member role changed writes a role_changed audit log', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Viewer->value]);

    AuditLog::query()->delete();

    $team->memberships()->where('user_id', $member->id)->update(['role' => TeamRole::Editor->value]);

    // Re-check: we need the model event to fire, so use model directly
    $membership = $team->memberships()->where('user_id', $member->id)->first();
    $membership->role = TeamRole::Admin;
    $membership->save();

    expect(AuditLog::where('action', AuditAction::MemberRoleChanged)->count())->toBe(1);
});

test('member removed writes a removed audit log', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Editor->value]);

    AuditLog::query()->delete();

    $team->memberships()->where('user_id', $member->id)->first()->delete();

    expect(AuditLog::where('action', AuditAction::MemberRemoved)->count())->toBe(1);
});

// --- Auth listeners ---

test('login event writes an audit log', function () {
    $user = User::factory()->create();

    Event::dispatch(new Login('web', $user, false));

    expect(AuditLog::where('action', AuditAction::AuthLogin)->count())->toBe(1);
    expect(AuditLog::where('action', AuditAction::AuthLogin)->first()->user_id)->toBe($user->id);
});

test('logout event writes an audit log', function () {
    $user = User::factory()->create();

    Event::dispatch(new Logout('web', $user));

    expect(AuditLog::where('action', AuditAction::AuthLogout)->count())->toBe(1);
});

test('failed login writes an audit log with email', function () {
    Event::dispatch(new Failed('web', null, ['email' => 'hacker@example.com']));

    $log = AuditLog::where('action', AuditAction::AuthLoginFailed)->first();
    expect($log)->not->toBeNull();
    expect($log->details)->toBe(['email' => 'hacker@example.com']);
    expect($log->user_id)->toBeNull();
});

// --- Submission view/status/reviewer instrumentation ---

test('viewing a submission writes a viewed audit log', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create(['form_id' => $form->id]);

    AuditLog::query()->delete();

    Livewire::test(ViewSubmission::class, ['record' => $submission->id]);

    expect(AuditLog::where('action', AuditAction::SubmissionViewed)->count())->toBe(1);
});

test('changing submission status writes a status_changed audit log', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $team->memberships()->where('user_id', $user->id)->update(['role' => TeamRole::Admin->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    $project = Project::factory()->create(['team_id' => $team->id]);
    $form = Form::factory()->create(['project_id' => $project->id]);
    $submission = Submission::factory()->create([
        'form_id' => $form->id,
        'status' => SubmissionStatus::Pending,
    ]);

    AuditLog::query()->delete();

    Livewire::test(ViewSubmission::class, ['record' => $submission->id])
        ->callAction('changeStatus', ['status' => SubmissionStatus::InReview->value]);

    $log = AuditLog::where('action', AuditAction::SubmissionStatusChanged)->first();
    expect($log)->not->toBeNull();
    expect($log->details)->toBe(['old' => 'pending', 'new' => 'in_review']);
});

// --- Page access ---

test('user without view audit permission cannot access audit logs page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $team->memberships()->where('user_id', $user->id)->update(['role' => TeamRole::Viewer->value]);

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    expect(AuditLogs::canAccess())->toBeFalse();
});

test('user with view audit permission can access audit logs page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    // Owner has ViewAudit permission by default
    expect($team->owner()->id)->toBe($user->id);
    expect($user->hasTeamPermission($team, TeamPermission::ViewAudit))->toBeTrue();
    expect(AuditLogs::canAccess())->toBeTrue();
});

test('audit logs page only shows entries for current tenant', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $this->actingAs($user);
    $this->setUpFilamentPanel($team);

    $otherTeam = Team::factory()->create();

    AuditLog::query()->delete();

    $mine = AuditLog::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'action' => AuditAction::FormCreated->value,
    ]);
    AuditLog::factory()->create([
        'team_id' => $otherTeam->id,
        'user_id' => $user->id,
        'action' => AuditAction::FormCreated->value,
    ]);

    Livewire::test(AuditLogs::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCountTableRecords(1);
});
