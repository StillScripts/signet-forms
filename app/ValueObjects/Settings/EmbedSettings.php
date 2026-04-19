<?php

namespace App\ValueObjects\Settings;

use Livewire\Wireable;

class EmbedSettings implements Wireable
{
    public const DEFAULT_WIDTH = '100%';

    public const DEFAULT_HEIGHT = 600;

    public function __construct(
        public bool $allowEmbedding = true,
        public ?string $width = null,
        public ?int $height = null,
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
            allowEmbedding: $data['allow_embedding'] ?? true,
            width: $data['width'] ?? null,
            height: isset($data['height']) ? (int) $data['height'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'allow_embedding' => $this->allowEmbedding,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function resolvedWidth(): string
    {
        return $this->width !== null && $this->width !== '' ? $this->width : self::DEFAULT_WIDTH;
    }

    public function resolvedHeight(): int
    {
        return $this->height ?? self::DEFAULT_HEIGHT;
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
