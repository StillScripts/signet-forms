<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

enum FormFieldType: string
{
    case TextInput = 'text-input';
    case Textarea = 'textarea';
    case Number = 'number';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case RadioGroup = 'radio-group';
    case Toggle = 'toggle';
    case DatePicker = 'date-picker';
    case FileUpload = 'file-upload';
    case RichEditor = 'rich-editor';

    public function label(): string
    {
        return match ($this) {
            self::TextInput => 'Text Input',
            self::Textarea => 'Textarea',
            self::Number => 'Number',
            self::Select => 'Select',
            self::Checkbox => 'Checkbox',
            self::RadioGroup => 'Radio Group',
            self::Toggle => 'Toggle',
            self::DatePicker => 'Date Picker',
            self::FileUpload => 'File Upload',
            self::RichEditor => 'Rich Text Editor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TextInput => 'Single line text field',
            self::Textarea => 'Multi-line text area',
            self::Number => 'Numeric input',
            self::Select => 'Dropdown selection',
            self::Checkbox => 'Boolean checkbox',
            self::RadioGroup => 'Single selection from options',
            self::Toggle => 'Boolean switch',
            self::DatePicker => 'Date selection',
            self::FileUpload => 'File attachment',
            self::RichEditor => 'Rich text with formatting',
        };
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::TextInput => Heroicon::OutlinedBars3BottomLeft,
            self::Textarea => Heroicon::OutlinedBars4,
            self::Number => Heroicon::OutlinedHashtag,
            self::Select => Heroicon::OutlinedChevronUpDown,
            self::Checkbox => Heroicon::OutlinedCheckCircle,
            self::RadioGroup => Heroicon::OutlinedListBullet,
            self::Toggle => Heroicon::OutlinedEye,
            self::DatePicker => Heroicon::OutlinedCalendar,
            self::FileUpload => Heroicon::OutlinedArrowUpTray,
            self::RichEditor => Heroicon::OutlinedDocumentText,
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::TextInput, self::Textarea, self::Number, self::Select, self::Checkbox, self::RadioGroup, self::Toggle => 'Basic Fields',
            self::DatePicker => 'Date & Time',
            self::FileUpload, self::RichEditor => 'Advanced',
        };
    }

    public function hasPlaceholder(): bool
    {
        return match ($this) {
            self::Checkbox, self::Toggle, self::FileUpload, self::RadioGroup => false,
            default => true,
        };
    }

    public function hasOptions(): bool
    {
        return match ($this) {
            self::Select, self::RadioGroup => true,
            default => false,
        };
    }

    public function hasMinMax(): bool
    {
        return match ($this) {
            self::TextInput, self::Textarea, self::Number => true,
            default => false,
        };
    }

    /**
     * Get the default field data for a new instance of this type.
     *
     * @return array<string, mixed>
     */
    public function defaultData(): array
    {
        $data = [
            'label' => $this->label(),
            'placeholder' => $this->hasPlaceholder() ? '' : null,
            'helper_text' => '',
            'default_value' => '',
            'column_span' => 1,
            'is_required' => false,
        ];

        if ($this->hasOptions()) {
            $data['options'] = [
                ['label' => 'Option 1', 'value' => 'option-1'],
                ['label' => 'Option 2', 'value' => 'option-2'],
            ];
        }

        if ($this->hasMinMax()) {
            $data['min'] = null;
            $data['max'] = null;
        }

        return $data;
    }

    /**
     * Get field types grouped by category.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::cases() as $type) {
            $grouped[$type->category()][] = $type;
        }

        return $grouped;
    }
}
