<?php

use App\ValueObjects\FormSettings;
use App\ValueObjects\Settings\BehaviourSettings;
use App\ValueObjects\Settings\ConfirmationSettings;

test('defaults returns sensible values', function () {
    $settings = FormSettings::defaults();

    expect($settings->confirmation->heading)->toBe('Thank you!')
        ->and($settings->confirmation->message)->toBe('Your response has been recorded.')
        ->and($settings->confirmation->redirectUrl)->toBeNull()
        ->and($settings->behaviour->submissionLimit)->toBeNull()
        ->and($settings->behaviour->closeDate)->toBeNull()
        ->and($settings->behaviour->allowMultipleSubmissions)->toBeFalse()
        ->and($settings->compliance->standards)->toBe([])
        ->and($settings->compliance->retentionDays)->toBeNull()
        ->and($settings->branding->primaryColour)->toBeNull()
        ->and($settings->branding->fontFamily)->toBeNull();
});

test('fromArray creates settings from full array', function () {
    $data = [
        'behaviour' => [
            'submission_limit' => 100,
            'close_date' => '2026-12-31 23:59:59',
            'allow_multiple_submissions' => true,
            'save_and_resume' => false,
            'captcha_enabled' => true,
        ],
        'confirmation' => [
            'heading' => 'Thanks!',
            'message' => 'All done.',
            'redirect_url' => 'https://example.com/done',
        ],
        'compliance' => [
            'standards' => ['GDPR'],
            'retention_days' => 365,
            'data_residency' => 'AU',
            'consent_text' => 'I agree.',
            'encrypt_submissions' => true,
        ],
        'branding' => [
            'logo_url' => 'https://example.com/logo.png',
            'primary_colour' => '#3B82F6',
            'font_family' => 'Inter',
            'custom_css' => 'body { color: red; }',
        ],
    ];

    $settings = FormSettings::fromArray($data);

    expect($settings->behaviour->submissionLimit)->toBe(100)
        ->and($settings->behaviour->closeDate)->toBe('2026-12-31 23:59:59')
        ->and($settings->behaviour->allowMultipleSubmissions)->toBeTrue()
        ->and($settings->behaviour->captchaEnabled)->toBeTrue()
        ->and($settings->confirmation->heading)->toBe('Thanks!')
        ->and($settings->confirmation->message)->toBe('All done.')
        ->and($settings->confirmation->redirectUrl)->toBe('https://example.com/done')
        ->and($settings->compliance->standards)->toBe(['GDPR'])
        ->and($settings->compliance->retentionDays)->toBe(365)
        ->and($settings->compliance->dataResidency)->toBe('AU')
        ->and($settings->compliance->encryptSubmissions)->toBeTrue()
        ->and($settings->branding->primaryColour)->toBe('#3B82F6')
        ->and($settings->branding->fontFamily)->toBe('Inter')
        ->and($settings->branding->customCss)->toBe('body { color: red; }');
});

test('fromArray handles partial data with defaults', function () {
    $data = [
        'confirmation' => [
            'heading' => 'Custom heading',
        ],
    ];

    $settings = FormSettings::fromArray($data);

    expect($settings->confirmation->heading)->toBe('Custom heading')
        ->and($settings->confirmation->message)->toBe('Your response has been recorded.')
        ->and($settings->behaviour->submissionLimit)->toBeNull()
        ->and($settings->compliance->standards)->toBe([])
        ->and($settings->branding->primaryColour)->toBeNull();
});

test('fromArray handles empty array', function () {
    $settings = FormSettings::fromArray([]);

    expect($settings->confirmation->heading)->toBe('Thank you!')
        ->and($settings->confirmation->message)->toBe('Your response has been recorded.');
});

test('toArray round-trips correctly', function () {
    $settings = new FormSettings(
        confirmation: new ConfirmationSettings(
            heading: 'Thanks!',
            message: 'Done.',
            redirectUrl: 'https://example.com',
        ),
        behaviour: new BehaviourSettings(
            submissionLimit: 50,
        ),
    );

    $array = $settings->toArray();
    $restored = FormSettings::fromArray($array);

    expect($restored->confirmation->heading)->toBe('Thanks!')
        ->and($restored->confirmation->message)->toBe('Done.')
        ->and($restored->confirmation->redirectUrl)->toBe('https://example.com')
        ->and($restored->behaviour->submissionLimit)->toBe(50);
});
