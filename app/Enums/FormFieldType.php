<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

enum FormFieldType: string
{
    // Basic
    case TextInput = 'text-input';
    case Textarea = 'textarea';
    case Number = 'number';
    case Email = 'email';
    case Phone = 'phone';
    case RichEditor = 'rich-editor';
    case MarkdownEditor = 'markdown-editor';

    // Choice
    case Select = 'select';
    case MultiSelect = 'multi-select';
    case RadioGroup = 'radio-group';
    case CheckboxList = 'checkbox-list';
    case Checkbox = 'checkbox';
    case Toggle = 'toggle';
    case ToggleButtons = 'toggle-buttons';
    case YesNo = 'yes-no';
    case Rating = 'rating';
    case Ranking = 'ranking';

    // Advanced
    case DatePicker = 'date-picker';
    case DateTimePicker = 'date-time-picker';
    case Time = 'time';
    case DateRange = 'date-range';
    case FileUpload = 'file-upload';
    case Signature = 'signature';
    case Address = 'address';
    case Slider = 'slider';
    case ColorPicker = 'color-picker';
    case TagsInput = 'tags-input';
    case KeyValue = 'key-value';
    case CodeEditor = 'code-editor';
    case Repeater = 'repeater';

    // Layout
    case SectionHeader = 'section-header';
    case Divider = 'divider';
    case InstructionalText = 'instructional-text';
    case Image = 'image';

    public function label(): string
    {
        return match ($this) {
            self::TextInput => 'Text Input',
            self::Textarea => 'Textarea',
            self::Number => 'Number',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::RichEditor => 'Rich Text Editor',
            self::MarkdownEditor => 'Markdown Editor',
            self::Select => 'Dropdown',
            self::MultiSelect => 'Multi Select',
            self::RadioGroup => 'Radio Group',
            self::CheckboxList => 'Checkbox List',
            self::Checkbox => 'Checkbox',
            self::Toggle => 'Toggle',
            self::ToggleButtons => 'Toggle Buttons',
            self::YesNo => 'Yes / No',
            self::Rating => 'Rating',
            self::Ranking => 'Ranking',
            self::DatePicker => 'Date Picker',
            self::DateTimePicker => 'Date & Time Picker',
            self::Time => 'Time Picker',
            self::DateRange => 'Date Range',
            self::FileUpload => 'File Upload',
            self::Signature => 'Signature',
            self::Address => 'Address',
            self::Slider => 'Slider',
            self::ColorPicker => 'Color Picker',
            self::TagsInput => 'Tags',
            self::KeyValue => 'Key-Value',
            self::CodeEditor => 'Code Editor',
            self::Repeater => 'Repeater',
            self::SectionHeader => 'Section Header',
            self::Divider => 'Divider',
            self::InstructionalText => 'Instructional Text',
            self::Image => 'Image',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TextInput => 'Single line text field',
            self::Textarea => 'Multi-line text area',
            self::Number => 'Numeric input',
            self::Email => 'Email address with validation',
            self::Phone => 'Telephone number input',
            self::RichEditor => 'Rich text with formatting',
            self::MarkdownEditor => 'Markdown editor with live preview',
            self::Select => 'Single selection dropdown',
            self::MultiSelect => 'Multi-selection dropdown',
            self::RadioGroup => 'Single selection from options',
            self::CheckboxList => 'Multiple selection from options',
            self::Checkbox => 'Boolean checkbox',
            self::Toggle => 'Boolean switch',
            self::ToggleButtons => 'Horizontal button group',
            self::YesNo => 'Binary yes or no choice',
            self::Rating => 'Star / heart / thumbs rating',
            self::Ranking => 'Drag to rank options',
            self::DatePicker => 'Date selection',
            self::DateTimePicker => 'Date and time selection',
            self::Time => 'Time selection',
            self::DateRange => 'Start and end date range',
            self::FileUpload => 'File attachment',
            self::Signature => 'Draw signature on canvas',
            self::Address => 'Street, suburb, state, postcode, country',
            self::Slider => 'Numeric slider between a min and max',
            self::ColorPicker => 'Pick a colour value',
            self::TagsInput => 'Free-form list of tags',
            self::KeyValue => 'Key and value pair list',
            self::CodeEditor => 'Syntax-highlighted code editor',
            self::Repeater => 'Repeatable list of items',
            self::SectionHeader => 'Heading and optional subheading',
            self::Divider => 'Horizontal rule between fields',
            self::InstructionalText => 'Paragraph of guidance for respondents',
            self::Image => 'Embedded image from a URL',
        };
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::TextInput => Heroicon::OutlinedBars3BottomLeft,
            self::Textarea => Heroicon::OutlinedBars4,
            self::Number => Heroicon::OutlinedHashtag,
            self::Email => Heroicon::OutlinedEnvelope,
            self::Phone => Heroicon::OutlinedPhone,
            self::RichEditor => Heroicon::OutlinedDocumentText,
            self::MarkdownEditor => Heroicon::OutlinedCodeBracketSquare,
            self::Select => Heroicon::OutlinedChevronUpDown,
            self::MultiSelect => Heroicon::OutlinedListBullet,
            self::RadioGroup => Heroicon::OutlinedListBullet,
            self::CheckboxList => Heroicon::OutlinedCheckCircle,
            self::Checkbox => Heroicon::OutlinedCheckCircle,
            self::Toggle => Heroicon::OutlinedEye,
            self::ToggleButtons => Heroicon::OutlinedRectangleGroup,
            self::YesNo => Heroicon::OutlinedCheckBadge,
            self::Rating => Heroicon::OutlinedStar,
            self::Ranking => Heroicon::OutlinedBars3,
            self::DatePicker => Heroicon::OutlinedCalendar,
            self::DateTimePicker => Heroicon::OutlinedCalendarDays,
            self::Time => Heroicon::OutlinedClock,
            self::DateRange => Heroicon::OutlinedCalendar,
            self::FileUpload => Heroicon::OutlinedArrowUpTray,
            self::Signature => Heroicon::OutlinedPencil,
            self::Address => Heroicon::OutlinedMapPin,
            self::Slider => Heroicon::OutlinedAdjustmentsHorizontal,
            self::ColorPicker => Heroicon::OutlinedSwatch,
            self::TagsInput => Heroicon::OutlinedTag,
            self::KeyValue => Heroicon::OutlinedTableCells,
            self::CodeEditor => Heroicon::OutlinedCodeBracket,
            self::Repeater => Heroicon::OutlinedQueueList,
            self::SectionHeader => Heroicon::OutlinedHashtag,
            self::Divider => Heroicon::OutlinedMinus,
            self::InstructionalText => Heroicon::OutlinedChatBubbleLeft,
            self::Image => Heroicon::OutlinedPhoto,
        };
    }

    public function category(): FieldCategory
    {
        return match ($this) {
            self::TextInput,
            self::Textarea,
            self::Number,
            self::Email,
            self::Phone,
            self::RichEditor,
            self::MarkdownEditor => FieldCategory::Basic,

            self::Select,
            self::MultiSelect,
            self::RadioGroup,
            self::CheckboxList,
            self::Checkbox,
            self::Toggle,
            self::ToggleButtons,
            self::YesNo,
            self::Rating,
            self::Ranking => FieldCategory::Choice,

            self::DatePicker,
            self::DateTimePicker,
            self::Time,
            self::DateRange,
            self::FileUpload,
            self::Signature,
            self::Address,
            self::Slider,
            self::ColorPicker,
            self::TagsInput,
            self::KeyValue,
            self::CodeEditor,
            self::Repeater => FieldCategory::Advanced,

            self::SectionHeader,
            self::Divider,
            self::InstructionalText,
            self::Image => FieldCategory::Layout,
        };
    }

    public function isLayout(): bool
    {
        return $this->category() === FieldCategory::Layout;
    }

    public function hasPlaceholder(): bool
    {
        if ($this->isLayout()) {
            return false;
        }

        return match ($this) {
            self::Checkbox,
            self::Toggle,
            self::ToggleButtons,
            self::YesNo,
            self::Rating,
            self::Ranking,
            self::RadioGroup,
            self::CheckboxList,
            self::FileUpload,
            self::Signature,
            self::Address,
            self::Slider,
            self::ColorPicker,
            self::KeyValue,
            self::Repeater,
            self::DateRange => false,
            default => true,
        };
    }

    public function hasOptions(): bool
    {
        return match ($this) {
            self::Select,
            self::MultiSelect,
            self::RadioGroup,
            self::CheckboxList,
            self::ToggleButtons,
            self::Ranking => true,
            default => false,
        };
    }

    public function hasMinMax(): bool
    {
        return match ($this) {
            self::TextInput,
            self::Textarea,
            self::Number,
            self::Slider,
            self::Repeater => true,
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
        if ($this->isLayout()) {
            return $this->layoutDefaultData();
        }

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

        return array_merge($data, $this->typeSpecificDefaults());
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeSpecificDefaults(): array
    {
        return match ($this) {
            self::Rating => [
                'max' => 5,
                'icon' => 'star',
            ],
            self::Slider => [
                'min' => 0,
                'max' => 100,
                'step' => 1,
            ],
            self::CodeEditor => [
                'language' => 'php',
            ],
            self::ColorPicker => [
                'format' => 'hex',
            ],
            self::KeyValue => [
                'key_label' => 'Key',
                'value_label' => 'Value',
            ],
            self::Repeater => [
                'item_label' => 'Item',
            ],
            self::TagsInput => [
                'suggestions' => [],
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function layoutDefaultData(): array
    {
        return match ($this) {
            self::SectionHeader => [
                'heading' => 'Section Heading',
                'subheading' => null,
                'column_span' => 1,
            ],
            self::Divider => [
                'column_span' => 1,
            ],
            self::InstructionalText => [
                'content' => 'Add instructional text here.',
                'column_span' => 1,
            ],
            self::Image => [
                'url' => '',
                'alt' => '',
                'column_span' => 1,
            ],
            default => [],
        };
    }

    /**
     * Get field types grouped by category.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (FieldCategory::cases() as $category) {
            $grouped[$category->label()] = [];
        }

        foreach (self::cases() as $type) {
            $grouped[$type->category()->label()][] = $type;
        }

        return $grouped;
    }
}
