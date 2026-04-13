<?php

namespace App\ValueObjects\Settings;

use Livewire\Wireable;

class BehaviourSettings implements Wireable
{
    public function __construct(
        public ?int $submissionLimit = null,
        public ?string $closeDate = null,
        public bool $allowMultipleSubmissions = false,
        public bool $saveAndResume = false,
        public bool $captchaEnabled = false,
    ) {}

    public static function defaults(): static
    {
        return new static;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            submissionLimit: $data['submission_limit'] ?? null,
            closeDate: $data['close_date'] ?? null,
            allowMultipleSubmissions: $data['allow_multiple_submissions'] ?? false,
            saveAndResume: $data['save_and_resume'] ?? false,
            captchaEnabled: $data['captcha_enabled'] ?? false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'submission_limit' => $this->submissionLimit,
            'close_date' => $this->closeDate,
            'allow_multiple_submissions' => $this->allowMultipleSubmissions,
            'save_and_resume' => $this->saveAndResume,
            'captcha_enabled' => $this->captchaEnabled,
        ];
    }

    public function toLivewire(): array
    {
        return $this->toArray();
    }

    public static function fromLivewire($value): static
    {
        return static::fromArray($value);
    }
}
