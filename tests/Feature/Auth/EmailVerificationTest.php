<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

// Note: The User model must implement MustVerifyEmail for these tests to work.
// If the email verification prompt test fails with a TypeError, ensure
// `implements \Illuminate\Contracts\Auth\MustVerifyEmail` is on the User model.

test('email verification screen can be rendered for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('filament.admin.auth.email-verification.prompt'));

    $response->assertOk();
})->skip(
    ! in_array(MustVerifyEmail::class, class_implements(User::class)),
    'User model does not implement MustVerifyEmail'
);

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'filament.admin.auth.email-verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
})->skip(
    ! in_array(MustVerifyEmail::class, class_implements(User::class)),
    'User model does not implement MustVerifyEmail'
);

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'filament.admin.auth.email-verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
})->skip(
    ! in_array(MustVerifyEmail::class, class_implements(User::class)),
    'User model does not implement MustVerifyEmail'
);
