<?php

namespace App\ValueObjects\Settings;

use Livewire\Wireable;

class BrandingSettings implements Wireable
{
    public function __construct(
        public ?string $logoUrl = null,
        public ?string $primaryColour = null,
        public ?string $fontFamily = null,
        public ?string $customCss = null,
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
            logoUrl: $data['logo_url'] ?? null,
            primaryColour: $data['primary_colour'] ?? null,
            fontFamily: $data['font_family'] ?? null,
            customCss: $data['custom_css'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'logo_url' => $this->logoUrl,
            'primary_colour' => $this->primaryColour,
            'font_family' => $this->fontFamily,
            'custom_css' => $this->customCss,
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
