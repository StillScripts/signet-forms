<?php

namespace App\ValueObjects\Settings;

use Livewire\Wireable;

class ConfirmationSettings implements Wireable
{
    public function __construct(
        public ?string $heading = 'Thank you!',
        public ?string $message = 'Your response has been recorded.',
        public ?string $redirectUrl = null,
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
            heading: $data['heading'] ?? 'Thank you!',
            message: $data['message'] ?? 'Your response has been recorded.',
            redirectUrl: $data['redirect_url'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'heading' => $this->heading,
            'message' => $this->message,
            'redirect_url' => $this->redirectUrl,
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
