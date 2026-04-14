<?php

namespace App\Services;

use App\Enums\FieldComponentRenderTarget;
use App\Enums\FormFieldType;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Image as ImageSchemaComponent;
use Filament\Schemas\Components\Text;

class FieldComponentBuilder
{
    /**
     * @param  array<string, mixed>  $field
     */
    public function buildFilamentComponent(array $field, FieldComponentRenderTarget $target): ?Component
    {
        $type = FormFieldType::tryFrom($field['type'] ?? '');

        if ($type === null) {
            return null;
        }

        if ($type->isLayout()) {
            return $this->buildLayoutComponent($type, $field, $target);
        }

        $data = $field['data'] ?? [];
        $key = $field['key'];

        $component = match ($type) {
            FormFieldType::TextInput => TextInput::make($key),
            FormFieldType::Textarea => Textarea::make($key)->rows(3),
            FormFieldType::Number => TextInput::make($key)->numeric(),
            FormFieldType::Select => Select::make($key)
                ->options(collect($data['options'] ?? [])->pluck('label', 'value')->all()),
            FormFieldType::Checkbox => Checkbox::make($key),
            FormFieldType::RadioGroup => Radio::make($key)
                ->options(collect($data['options'] ?? [])->pluck('label', 'value')->all()),
            FormFieldType::Toggle => Toggle::make($key),
            FormFieldType::DatePicker => DatePicker::make($key),
            FormFieldType::FileUpload => $target === FieldComponentRenderTarget::BuilderPreview
                ? FileUpload::make($key)
                : null,
            FormFieldType::RichEditor => $target === FieldComponentRenderTarget::BuilderPreview
                ? RichEditor::make($key)
                : Textarea::make($key)->rows(4),
            default => null,
        };

        if ($component === null) {
            return null;
        }

        $component->label($data['label'] ?? $key);

        if (! empty($data['placeholder']) && method_exists($component, 'placeholder')) {
            $component->placeholder($data['placeholder']);
        }

        if (! empty($data['helper_text'])) {
            $component->helperText($data['helper_text']);
        }

        if (! empty($data['default_value'])) {
            $component->default($data['default_value']);
        }

        if (($data['is_required'] ?? false) && method_exists($component, 'required')) {
            $component->required();
        }

        $columnSpan = $data['column_span'] ?? 1;
        if ($columnSpan > 1) {
            $component->columnSpan($columnSpan);
        }

        return $component;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function buildLayoutComponent(FormFieldType $type, array $field, FieldComponentRenderTarget $target): ?Component
    {
        $data = $field['data'] ?? [];

        $component = match ($type) {
            FormFieldType::SectionHeader => $this->buildSectionHeaderComponent($data),
            FormFieldType::Divider => Html::make('<hr class="my-2 border-gray-200 dark:border-white/10" />'),
            FormFieldType::InstructionalText => Text::make((string) ($data['content'] ?? '')),
            FormFieldType::Image => $this->buildImageLayoutComponent($data, $target),
            default => null,
        };

        if ($component === null) {
            return null;
        }

        $columnSpan = $data['column_span'] ?? 1;
        if ($columnSpan > 1) {
            $component->columnSpan($columnSpan);
        }

        return $component;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildImageLayoutComponent(array $data, FieldComponentRenderTarget $target): ?Component
    {
        if (! empty($data['url'])) {
            return ImageSchemaComponent::make($data['url'], (string) ($data['alt'] ?? ''));
        }

        if ($target === FieldComponentRenderTarget::BuilderPreview) {
            return Html::make('<div class="rounded-md border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-400 dark:border-gray-700">No image URL provided</div>');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildSectionHeaderComponent(array $data): Component
    {
        $heading = (string) ($data['heading'] ?? '');
        $subheading = (string) ($data['subheading'] ?? '');

        $subheadingHtml = $subheading !== ''
            ? '<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">'.e($subheading).'</p>'
            : '';

        return Html::make(
            '<div class="border-b border-gray-200 pb-2 dark:border-white/10">'.
            '<h3 class="text-base font-semibold text-gray-950 dark:text-white">'.e($heading).'</h3>'.
            $subheadingHtml.
            '</div>'
        );
    }
}
