<?php

namespace App\ValueObjects;

use App\ValueObjects\Settings\BehaviourSettings;
use App\ValueObjects\Settings\BrandingSettings;
use App\ValueObjects\Settings\ComplianceSettings;
use App\ValueObjects\Settings\ConfirmationSettings;
use App\ValueObjects\Settings\EmbedSettings;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Livewire\Wireable;

class FormSettings implements Castable, Wireable
{
    public function __construct(
        public BehaviourSettings $behaviour = new BehaviourSettings,
        public ConfirmationSettings $confirmation = new ConfirmationSettings,
        public ComplianceSettings $compliance = new ComplianceSettings,
        public BrandingSettings $branding = new BrandingSettings,
        public EmbedSettings $embed = new EmbedSettings,
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
            behaviour: isset($data['behaviour']) ? BehaviourSettings::fromArray($data['behaviour']) : new BehaviourSettings,
            confirmation: isset($data['confirmation']) ? ConfirmationSettings::fromArray($data['confirmation']) : new ConfirmationSettings,
            compliance: isset($data['compliance']) ? ComplianceSettings::fromArray($data['compliance']) : new ComplianceSettings,
            branding: isset($data['branding']) ? BrandingSettings::fromArray($data['branding']) : new BrandingSettings,
            embed: isset($data['embed']) ? EmbedSettings::fromArray($data['embed']) : new EmbedSettings,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'behaviour' => $this->behaviour->toArray(),
            'confirmation' => $this->confirmation->toArray(),
            'compliance' => $this->compliance->toArray(),
            'branding' => $this->branding->toArray(),
            'embed' => $this->embed->toArray(),
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

    /**
     * @return class-string<CastsAttributes<static, array<string, mixed>>>
     */
    public static function castUsing(array $arguments): string
    {
        return FormSettingsCast::class;
    }
}

/**
 * @implements CastsAttributes<FormSettings, array<string, mixed>>
 */
class FormSettingsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): FormSettings
    {
        if ($value === null) {
            return FormSettings::defaults();
        }

        $data = json_decode($value, true);

        return FormSettings::fromArray($data);
    }

    /**
     * @param  FormSettings|array<string, mixed>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof FormSettings) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode(FormSettings::fromArray($value)->toArray());
        }

        return null;
    }
}
