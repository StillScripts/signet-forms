<?php

namespace App\Services;

use App\Enums\FieldComponentRenderTarget;
use App\Enums\FormFieldType;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language as CodeLanguage;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Image as ImageSchemaComponent;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

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

        $component = $this->buildDataComponent($type, $key, $data, $target);

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

        if (! empty($data['default_value']) && method_exists($component, 'default')) {
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
     * @param  array<string, mixed>  $data
     */
    protected function buildDataComponent(FormFieldType $type, string $key, array $data, FieldComponentRenderTarget $target): ?Component
    {
        return match ($type) {
            FormFieldType::TextInput => TextInput::make($key),
            FormFieldType::Textarea => Textarea::make($key)->rows(3),
            FormFieldType::Number => TextInput::make($key)->numeric(),
            FormFieldType::Email => TextInput::make($key)->email(),
            FormFieldType::Phone => TextInput::make($key)->tel(),
            FormFieldType::RichEditor => $target === FieldComponentRenderTarget::BuilderPreview
                ? RichEditor::make($key)
                : Textarea::make($key)->rows(4),
            FormFieldType::MarkdownEditor => $target === FieldComponentRenderTarget::BuilderPreview
                ? MarkdownEditor::make($key)
                : Textarea::make($key)->rows(4),
            FormFieldType::Select => Select::make($key)
                ->options($this->optionsMap($data)),
            FormFieldType::MultiSelect => Select::make($key)
                ->multiple()
                ->options($this->optionsMap($data)),
            FormFieldType::RadioGroup => Radio::make($key)
                ->options($this->optionsMap($data)),
            FormFieldType::CheckboxList => CheckboxList::make($key)
                ->options($this->optionsMap($data)),
            FormFieldType::Checkbox => Checkbox::make($key),
            FormFieldType::Toggle => Toggle::make($key),
            FormFieldType::ToggleButtons => ToggleButtons::make($key)
                ->options($this->optionsMap($data))
                ->inline(),
            FormFieldType::YesNo => ToggleButtons::make($key)
                ->options(['yes' => 'Yes', 'no' => 'No'])
                ->inline(),
            FormFieldType::Rating => $this->buildRatingComponent($key, $data),
            FormFieldType::Ranking => $this->buildRankingComponent($key, $data),
            FormFieldType::DatePicker => DatePicker::make($key),
            FormFieldType::DateTimePicker => DateTimePicker::make($key),
            FormFieldType::Time => TimePicker::make($key),
            FormFieldType::DateRange => $this->buildDateRangeComponent($key),
            FormFieldType::FileUpload => $target === FieldComponentRenderTarget::BuilderPreview
                ? FileUpload::make($key)
                : null,
            FormFieldType::Signature => SignaturePad::make($key),
            FormFieldType::Address => $this->buildAddressComponent($key),
            FormFieldType::Slider => $this->buildSliderComponent($key, $data),
            FormFieldType::ColorPicker => $this->buildColorPickerComponent($key, $data),
            FormFieldType::TagsInput => TagsInput::make($key)
                ->suggestions(collect($data['suggestions'] ?? [])->pluck('value')->filter()->values()->all()),
            FormFieldType::KeyValue => KeyValue::make($key)
                ->keyLabel($data['key_label'] ?? 'Key')
                ->valueLabel($data['value_label'] ?? 'Value'),
            FormFieldType::CodeEditor => $this->buildCodeEditorComponent($key, $data),
            FormFieldType::Repeater => $this->buildRepeaterComponent($key, $data),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected function optionsMap(array $data): array
    {
        return collect($data['options'] ?? [])->pluck('label', 'value')->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildRatingComponent(string $key, array $data): ToggleButtons
    {
        $max = max(2, min(10, (int) ($data['max'] ?? 5)));
        $iconKey = $data['icon'] ?? 'star';
        $heroicon = match ($iconKey) {
            'heart' => Heroicon::Heart,
            'thumbs-up' => Heroicon::HandThumbUp,
            default => Heroicon::Star,
        };

        $options = [];
        $icons = [];
        $colors = [];

        foreach (range(1, $max) as $value) {
            $options[$value] = (string) $value;
            $icons[$value] = $heroicon;
            $colors[$value] = 'warning';
        }

        return ToggleButtons::make($key)
            ->options($options)
            ->icons($icons)
            ->colors($colors)
            ->inline();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildRankingComponent(string $key, array $data): Repeater
    {
        $options = collect($data['options'] ?? [])
            ->map(fn (array $option): array => [
                'label' => (string) ($option['label'] ?? ''),
                'value' => (string) ($option['value'] ?? ''),
            ])
            ->values()
            ->all();

        return Repeater::make($key)
            ->schema([
                TextInput::make('label')->readOnly(),
                Hidden::make('value'),
            ])
            ->default($options)
            ->addable(false)
            ->deletable(false)
            ->reorderableWithButtons()
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null);
    }

    protected function buildDateRangeComponent(string $key): Fieldset
    {
        return Fieldset::make($key)
            ->schema([
                DatePicker::make("{$key}.start")->label('Start'),
                DatePicker::make("{$key}.end")->label('End'),
            ])
            ->columns(2);
    }

    protected function buildAddressComponent(string $key): Fieldset
    {
        // future enhancement: Google Places autocomplete could replace these inputs
        // on workspaces that opt in.
        return Fieldset::make($key)
            ->schema([
                TextInput::make("{$key}.street")->label('Street')->columnSpanFull(),
                TextInput::make("{$key}.suburb")->label('Suburb'),
                TextInput::make("{$key}.state")->label('State'),
                TextInput::make("{$key}.postcode")->label('Postcode'),
                TextInput::make("{$key}.country")->label('Country'),
            ])
            ->columns(2);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildSliderComponent(string $key, array $data): Slider
    {
        return Slider::make($key)
            ->minValue((int) ($data['min'] ?? 0))
            ->maxValue((int) ($data['max'] ?? 100))
            ->step((int) ($data['step'] ?? 1));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildColorPickerComponent(string $key, array $data): ColorPicker
    {
        $picker = ColorPicker::make($key);

        return match ($data['format'] ?? 'hex') {
            'rgb' => $picker->rgb(),
            'rgba' => $picker->rgba(),
            'hsl' => $picker->hsl(),
            default => $picker,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildCodeEditorComponent(string $key, array $data): CodeEditor
    {
        $editor = CodeEditor::make($key);
        $language = CodeLanguage::tryFrom($data['language'] ?? 'php');

        if ($language !== null) {
            $editor->language($language);
        }

        return $editor;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildRepeaterComponent(string $key, array $data): Repeater
    {
        $itemLabel = (string) ($data['item_label'] ?? 'Item');

        $repeater = Repeater::make($key)
            ->schema([
                TextInput::make('value')->label($itemLabel),
            ]);

        if (! empty($data['min'])) {
            $repeater->minItems((int) $data['min']);
        }

        if (! empty($data['max'])) {
            $repeater->maxItems((int) $data['max']);
        }

        return $repeater;
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
