<?php

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Form;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;

test('submission belongs to a form', function () {
    $form = Form::factory()->create();
    $submission = Submission::factory()->create(['form_id' => $form->id]);

    expect($submission->form->id)->toBe($form->id);
});

test('form has many submissions', function () {
    $form = Form::factory()->create();
    Submission::factory()->count(3)->create(['form_id' => $form->id]);

    expect($form->submissions)->toHaveCount(3);
});

test('submission data is cast to array', function () {
    $data = ['name' => 'Jane', 'email' => 'jane@example.com'];
    $submission = Submission::factory()->create(['data' => $data]);

    expect($submission->fresh()->data)->toBe($data);
});

test('submissions are deleted when form is force deleted', function () {
    $form = Form::factory()->create();
    Submission::factory()->create(['form_id' => $form->id]);

    $form->forceDelete();

    expect(Submission::where('form_id', $form->id)->count())->toBe(0);
});

test('owners can view submissions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    expect($user->hasTeamPermission($team, TeamPermission::ViewSubmission))->toBeTrue();
});

test('admins can view submissions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    expect($user->hasTeamPermission($team, TeamPermission::ViewSubmission))->toBeTrue();
});

test('members can view submissions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->hasTeamPermission($team, TeamPermission::ViewSubmission))->toBeTrue();
});

test('owners can delete submissions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    expect($user->hasTeamPermission($team, TeamPermission::DeleteSubmission))->toBeTrue();
});

test('admins can delete submissions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    expect($user->hasTeamPermission($team, TeamPermission::DeleteSubmission))->toBeTrue();
});

test('members cannot delete submissions', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    expect($user->hasTeamPermission($team, TeamPermission::DeleteSubmission))->toBeFalse();
});

test('form has success heading and message fields', function () {
    $form = Form::factory()->create([
        'success_heading' => 'Thanks!',
        'success_message' => 'We received your response.',
    ]);

    $fresh = $form->fresh();
    expect($fresh->success_heading)->toBe('Thanks!')
        ->and($fresh->success_message)->toBe('We received your response.');
});

test('success fields default to null', function () {
    $form = Form::factory()->create();

    expect($form->fresh()->success_heading)->toBeNull()
        ->and($form->fresh()->success_message)->toBeNull();
});
