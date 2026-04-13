<?php

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

// --- Role Hierarchy ---

test('role hierarchy levels are correct', function () {
    expect(TeamRole::Owner->level())->toBe(5)
        ->and(TeamRole::Admin->level())->toBe(4)
        ->and(TeamRole::Editor->level())->toBe(3)
        ->and(TeamRole::Reviewer->level())->toBe(2)
        ->and(TeamRole::Viewer->level())->toBe(1);
});

test('isAtLeast correctly compares roles', function () {
    expect(TeamRole::Owner->isAtLeast(TeamRole::Admin))->toBeTrue()
        ->and(TeamRole::Admin->isAtLeast(TeamRole::Editor))->toBeTrue()
        ->and(TeamRole::Editor->isAtLeast(TeamRole::Reviewer))->toBeTrue()
        ->and(TeamRole::Reviewer->isAtLeast(TeamRole::Viewer))->toBeTrue()
        ->and(TeamRole::Viewer->isAtLeast(TeamRole::Owner))->toBeFalse()
        ->and(TeamRole::Editor->isAtLeast(TeamRole::Admin))->toBeFalse();
});

test('assignable roles exclude owner', function () {
    $assignable = TeamRole::assignable();
    $values = collect($assignable)->pluck('value')->toArray();

    expect($values)->toBe(['admin', 'editor', 'reviewer', 'viewer'])
        ->and($values)->not->toContain('owner');
});

// --- Role Labels & Descriptions ---

test('all roles have labels', function () {
    foreach (TeamRole::cases() as $role) {
        expect($role->label())->toBeString()->not->toBeEmpty();
    }
});

test('all roles have descriptions', function () {
    foreach (TeamRole::cases() as $role) {
        expect($role->description())->toBeString()->not->toBeEmpty();
    }
});

// --- Owner Permission Matrix ---

test('owner has all permissions', function () {
    expect(TeamRole::Owner->permissions())->toBe(TeamPermission::cases());
});

// --- Admin Permission Matrix ---

test('admin has team update but not delete', function () {
    expect(TeamRole::Admin->hasPermission(TeamPermission::UpdateTeam))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::DeleteTeam))->toBeFalse();
});

test('admin has member management permissions', function () {
    expect(TeamRole::Admin->hasPermission(TeamPermission::AddMember))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::UpdateMember))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::RemoveMember))->toBeTrue();
});

test('admin has invitation permissions', function () {
    expect(TeamRole::Admin->hasPermission(TeamPermission::CreateInvitation))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::CancelInvitation))->toBeTrue();
});

test('admin has full project permissions', function () {
    expect(TeamRole::Admin->hasPermission(TeamPermission::CreateProject))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::UpdateProject))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::DeleteProject))->toBeTrue();
});

test('admin has full form permissions', function () {
    expect(TeamRole::Admin->hasPermission(TeamPermission::CreateForm))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::UpdateForm))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::DeleteForm))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::PublishForm))->toBeTrue();
});

test('admin has full submission permissions', function () {
    expect(TeamRole::Admin->hasPermission(TeamPermission::ViewSubmission))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::DeleteSubmission))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::ExportSubmission))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::ReviewSubmission))->toBeTrue();
});

test('admin has audit and integration but not billing', function () {
    expect(TeamRole::Admin->hasPermission(TeamPermission::ViewAudit))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::ManageIntegration))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::ManageBilling))->toBeFalse();
});

// --- Editor Permission Matrix ---

test('editor cannot manage team or members', function () {
    expect(TeamRole::Editor->hasPermission(TeamPermission::UpdateTeam))->toBeFalse()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::DeleteTeam))->toBeFalse()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::AddMember))->toBeFalse()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::UpdateMember))->toBeFalse()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::RemoveMember))->toBeFalse()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::CreateInvitation))->toBeFalse()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::CancelInvitation))->toBeFalse();
});

test('editor can create and update projects but not delete', function () {
    expect(TeamRole::Editor->hasPermission(TeamPermission::CreateProject))->toBeTrue()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::UpdateProject))->toBeTrue()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::DeleteProject))->toBeFalse();
});

test('editor can create update and publish forms but not delete', function () {
    expect(TeamRole::Editor->hasPermission(TeamPermission::CreateForm))->toBeTrue()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::UpdateForm))->toBeTrue()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::PublishForm))->toBeTrue()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::DeleteForm))->toBeFalse();
});

test('editor can view and export submissions but not delete', function () {
    expect(TeamRole::Editor->hasPermission(TeamPermission::ViewSubmission))->toBeTrue()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::ExportSubmission))->toBeTrue()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::ReviewSubmission))->toBeTrue()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::DeleteSubmission))->toBeFalse();
});

test('editor can manage integrations but not audit or billing', function () {
    expect(TeamRole::Editor->hasPermission(TeamPermission::ManageIntegration))->toBeTrue()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::ViewAudit))->toBeFalse()
        ->and(TeamRole::Editor->hasPermission(TeamPermission::ManageBilling))->toBeFalse();
});

// --- Reviewer Permission Matrix ---

test('reviewer can only view and review submissions', function () {
    $allowed = [TeamPermission::ViewSubmission, TeamPermission::ReviewSubmission];

    foreach (TeamPermission::cases() as $permission) {
        $expected = in_array($permission, $allowed);
        expect(TeamRole::Reviewer->hasPermission($permission))
            ->toBe($expected, "Reviewer permission {$permission->value} expected ".($expected ? 'true' : 'false'));
    }
});

// --- Viewer Permission Matrix ---

test('viewer can only view submissions', function () {
    $allowed = [TeamPermission::ViewSubmission];

    foreach (TeamPermission::cases() as $permission) {
        $expected = in_array($permission, $allowed);
        expect(TeamRole::Viewer->hasPermission($permission))
            ->toBe($expected, "Viewer permission {$permission->value} expected ".($expected ? 'true' : 'false'));
    }
});

// --- Integration: Permission Checking via User Model ---

test('user permission check respects role assignment', function (TeamRole $role, TeamPermission $permission, bool $expected) {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);

    expect($user->hasTeamPermission($team, $permission))->toBe($expected);
})->with([
    'owner can delete team' => [TeamRole::Owner, TeamPermission::DeleteTeam, true],
    'admin cannot delete team' => [TeamRole::Admin, TeamPermission::DeleteTeam, false],
    'admin can add members' => [TeamRole::Admin, TeamPermission::AddMember, true],
    'editor cannot add members' => [TeamRole::Editor, TeamPermission::AddMember, false],
    'editor can create forms' => [TeamRole::Editor, TeamPermission::CreateForm, true],
    'reviewer cannot create forms' => [TeamRole::Reviewer, TeamPermission::CreateForm, false],
    'reviewer can review submissions' => [TeamRole::Reviewer, TeamPermission::ReviewSubmission, true],
    'viewer cannot review submissions' => [TeamRole::Viewer, TeamPermission::ReviewSubmission, false],
    'viewer can view submissions' => [TeamRole::Viewer, TeamPermission::ViewSubmission, true],
    'owner can manage billing' => [TeamRole::Owner, TeamPermission::ManageBilling, true],
    'admin cannot manage billing' => [TeamRole::Admin, TeamPermission::ManageBilling, false],
]);
