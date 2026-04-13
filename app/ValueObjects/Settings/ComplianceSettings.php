<?php

namespace App\ValueObjects\Settings;

use Livewire\Wireable;

class ComplianceSettings implements Wireable
{
    /**
     * @param  array<string>  $standards
     */
    public function __construct(
        public array $standards = [],
        public ?int $retentionDays = null,
        public ?string $dataResidency = null,
        public ?string $consentText = null,
        public bool $encryptSubmissions = false,
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
            standards: $data['standards'] ?? [],
            retentionDays: $data['retention_days'] ?? null,
            dataResidency: $data['data_residency'] ?? null,
            consentText: $data['consent_text'] ?? null,
            encryptSubmissions: $data['encrypt_submissions'] ?? false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'standards' => $this->standards,
            'retention_days' => $this->retentionDays,
            'data_residency' => $this->dataResidency,
            'consent_text' => $this->consentText,
            'encrypt_submissions' => $this->encryptSubmissions,
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
