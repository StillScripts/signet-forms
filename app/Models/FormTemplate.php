<?php

namespace App\Models;

use App\Enums\FormTemplateCategory;
use Database\Factories\FormTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'category', 'icon', 'schema'])]
class FormTemplate extends Model
{
    /** @use HasFactory<FormTemplateFactory> */
    use HasFactory, HasUuids;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (FormTemplate $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'category' => FormTemplateCategory::class,
        ];
    }
}
