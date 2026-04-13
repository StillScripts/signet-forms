<?php

namespace App\Filament\Resources\Projects\Resources\Forms\Pages;

use App\Enums\FormFieldType;
use App\Filament\Resources\Projects\Resources\Forms\FormResource;
use App\Models\FormVersion;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * @property-read array<string, list<FormFieldType>> $groupedFieldTypes
 */
class FormBuilderPage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = FormResource::class;

    protected static ?string $title = 'Form Builder';

    protected static ?string $breadcrumb = 'Builder';

    protected string $view = 'filament.resources.forms.pages.form-builder';

    /** @var list<array<string, mixed>> */
    public array $pages = [];

    /** @var list<array<string, mixed>> */
    public array $fields = [];

    public ?string $activePageId = null;

    public ?string $selectedFieldKey = null;

    public int $columns = 2;

    #[Locked]
    public int $currentVersion = 0;

    #[Locked]
    public int $latestVersion = 0;

    #[Locked]
    public bool $hasUnsavedChanges = false;

    public string $activeTab = 'builder';

    /** @var array<string, mixed> */
    public ?array $previewData = [];

    /** @var array<string, mixed> */
    public ?array $fieldSettingsData = [];

    /** @var array<string, mixed> */
    public ?array $pageSettingsData = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->loadFieldsFromRecord();
    }

    protected function loadFieldsFromRecord(): void
    {
        $record = $this->getRecord();
        $version = $record->latestVersion();

        if ($version) {
            $schema = is_array($version->schema) ? $version->schema : [];
            $this->currentVersion = $version->version;
            $this->latestVersion = $version->version;
        } else {
            $schema = is_array($record->schema) ? $record->schema : [];
            $this->currentVersion = 0;
            $this->latestVersion = 0;
        }

        $this->loadSchema($schema);
        $this->selectedFieldKey = null;
        $this->hasUnsavedChanges = false;
    }

    protected function loadSchema(array $schema): void
    {
        $this->pages = $schema['pages'] ?? [];

        if (empty($this->pages)) {
            $this->pages = [$this->makeEmptyPage()];
        }

        $this->activePageId = $this->pages[0]['id'];
        $this->fields = $this->pages[0]['fields'] ?? [];
        $this->selectedFieldKey = null;
    }

    protected function makeEmptyPage(?string $title = null): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'title' => $title,
            'heading' => null,
            'subheading' => null,
            'submit_button_text' => null,
            'fields' => [],
        ];
    }

    // --- Page Management ---

    public function switchPage(string $pageId): void
    {
        if ($pageId === $this->activePageId) {
            return;
        }

        $this->syncFieldsToActivePage();

        $this->activePageId = $pageId;

        foreach ($this->pages as $page) {
            if ($page['id'] === $pageId) {
                $this->fields = $page['fields'] ?? [];
                $this->selectedFieldKey = null;

                return;
            }
        }
    }

    public function addPage(): void
    {
        $this->syncFieldsToActivePage();

        $page = $this->makeEmptyPage();
        $this->pages[] = $page;
        $this->activePageId = $page['id'];
        $this->fields = [];
        $this->selectedFieldKey = null;
        $this->hasUnsavedChanges = true;
    }

    public function removePage(string $pageId): void
    {
        if (count($this->pages) <= 1) {
            return;
        }

        $this->syncFieldsToActivePage();

        $this->pages = array_values(
            array_filter($this->pages, fn (array $page): bool => $page['id'] !== $pageId)
        );

        if ($this->activePageId === $pageId) {
            $this->activePageId = $this->pages[0]['id'];
            $this->fields = $this->pages[0]['fields'] ?? [];
            $this->selectedFieldKey = null;
        }

        $this->hasUnsavedChanges = true;
    }

    public function updatePageData(string $pageId, string $property, ?string $value): void
    {
        foreach ($this->pages as $index => $page) {
            if ($page['id'] === $pageId) {
                $this->pages[$index][$property] = $value;

                break;
            }
        }

        $this->hasUnsavedChanges = true;
    }

    public function movePage(string $pageId, string $direction): void
    {
        $this->syncFieldsToActivePage();

        $currentIndex = null;

        foreach ($this->pages as $index => $page) {
            if ($page['id'] === $pageId) {
                $currentIndex = $index;

                break;
            }
        }

        if ($currentIndex === null) {
            return;
        }

        $newIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($newIndex < 0 || $newIndex >= count($this->pages)) {
            return;
        }

        $page = $this->pages[$currentIndex];
        $this->pages[$currentIndex] = $this->pages[$newIndex];
        $this->pages[$newIndex] = $page;

        $this->hasUnsavedChanges = true;
    }

    public function getActivePageIndex(): int
    {
        foreach ($this->pages as $index => $page) {
            if ($page['id'] === $this->activePageId) {
                return $index;
            }
        }

        return 0;
    }

    public function getPageLabel(array $page, int $index): string
    {
        return $page['title'] ?? 'Page '.($index + 1);
    }

    public function getSubmitButtonText(array $page, int $index): string
    {
        if (! empty($page['submit_button_text'])) {
            return $page['submit_button_text'];
        }

        if (count($this->pages) === 1 || $index === count($this->pages) - 1) {
            return 'Submit';
        }

        return 'Continue';
    }

    public function isMultiPage(): bool
    {
        return count($this->pages) > 1;
    }

    protected function syncFieldsToActivePage(): void
    {
        foreach ($this->pages as $index => $page) {
            if ($page['id'] === $this->activePageId) {
                $this->pages[$index]['fields'] = $this->fields;

                break;
            }
        }
    }

    // --- Field Management ---

    public function addField(string $type): void
    {
        $fieldType = FormFieldType::from($type);

        $this->fields[] = [
            'type' => $type,
            'key' => $this->generateUniqueFieldKey($fieldType->label()),
            'sort' => count($this->fields),
            'data' => $fieldType->defaultData(),
        ];

        $this->selectedFieldKey = $this->fields[array_key_last($this->fields)]['key'];
        $this->syncSettingsFromField();
        $this->hasUnsavedChanges = true;
    }

    public function selectField(?string $key): void
    {
        $this->selectedFieldKey = $key;
        $this->syncSettingsFromField();
    }

    public function removeField(string $key): void
    {
        $this->fields = array_values(
            array_filter($this->fields, fn (array $field): bool => $field['key'] !== $key)
        );

        if ($this->selectedFieldKey === $key) {
            $this->selectedFieldKey = null;
        }

        $this->reindexSort();
        $this->hasUnsavedChanges = true;
    }

    public function updateFieldData(string $key, string $property, mixed $value): void
    {
        foreach ($this->fields as $index => $field) {
            if ($field['key'] === $key) {
                $this->fields[$index]['data'][$property] = $value;

                if ($property === 'label' && ! $this->fieldKeyWasManuallySet($index)) {
                    $this->fields[$index]['key'] = $this->generateUniqueFieldKey($value, $key);
                }

                break;
            }
        }

        $this->hasUnsavedChanges = true;
    }

    public function updateFieldKey(string $oldKey, string $newKey): void
    {
        $newKey = Str::slug($newKey, '_');

        if ($newKey === $oldKey || $newKey === '') {
            return;
        }

        if ($this->fieldKeyExists($newKey, $oldKey)) {
            $newKey = $this->makeKeyUnique($newKey, $oldKey);
        }

        foreach ($this->fields as $index => $field) {
            if ($field['key'] === $oldKey) {
                $this->fields[$index]['key'] = $newKey;
                $this->fields[$index]['_manual_key'] = true;

                if ($this->selectedFieldKey === $oldKey) {
                    $this->selectedFieldKey = $newKey;
                }

                break;
            }
        }

        $this->hasUnsavedChanges = true;
    }

    public function updateOption(string $fieldKey, int $optionIndex, string $property, string $value): void
    {
        foreach ($this->fields as $index => $field) {
            if ($field['key'] === $fieldKey) {
                $this->fields[$index]['data']['options'][$optionIndex][$property] = $value;

                if ($property === 'label') {
                    $this->fields[$index]['data']['options'][$optionIndex]['value'] = Str::slug($value);
                }

                break;
            }
        }

        $this->hasUnsavedChanges = true;
    }

    public function addOption(string $fieldKey): void
    {
        foreach ($this->fields as $index => $field) {
            if ($field['key'] === $fieldKey) {
                $nextNum = count($this->fields[$index]['data']['options'] ?? []) + 1;
                $this->fields[$index]['data']['options'][] = [
                    'label' => "Option {$nextNum}",
                    'value' => "option-{$nextNum}",
                ];

                break;
            }
        }

        $this->hasUnsavedChanges = true;
    }

    public function removeOption(string $fieldKey, int $optionIndex): void
    {
        foreach ($this->fields as $index => $field) {
            if ($field['key'] === $fieldKey) {
                unset($this->fields[$index]['data']['options'][$optionIndex]);
                $this->fields[$index]['data']['options'] = array_values($this->fields[$index]['data']['options']);

                break;
            }
        }

        $this->hasUnsavedChanges = true;
    }

    public function handleSort(string $key, int $position): void
    {
        $movingField = null;
        $remaining = [];

        foreach ($this->fields as $field) {
            if ($field['key'] === $key) {
                $movingField = $field;
            } else {
                $remaining[] = $field;
            }
        }

        if ($movingField === null) {
            return;
        }

        array_splice($remaining, $position, 0, [$movingField]);
        $this->fields = $remaining;
        $this->reindexSort();
        $this->hasUnsavedChanges = true;
    }

    // --- Save / Load / Undo / Redo ---

    public function save(): void
    {
        $this->syncFieldsToActivePage();

        $schema = $this->buildSchemaForSave();

        $record = $this->getRecord();
        $nextVersion = ($record->versions()->max('version') ?? 0) + 1;

        FormVersion::create([
            'form_id' => $record->id,
            'version' => $nextVersion,
            'schema' => $schema,
        ]);

        $record->update([
            'schema' => $schema,
        ]);

        $this->currentVersion = $nextVersion;
        $this->latestVersion = $nextVersion;
        $this->hasUnsavedChanges = false;

        Notification::make()
            ->success()
            ->title("Saved as v{$nextVersion}")
            ->send();
    }

    protected function buildSchemaForSave(): array
    {
        $pages = array_map(function (array $page): array {
            $page['fields'] = array_map(function (array $field): array {
                unset($field['_manual_key']);

                return $field;
            }, $page['fields'] ?? []);

            return $page;
        }, $this->pages);

        return ['pages' => $pages];
    }

    public function updateColumns(int $columns): void
    {
        $this->columns = max(1, min(4, $columns));
    }

    public function undo(): void
    {
        if ($this->currentVersion <= 1) {
            return;
        }

        $this->loadVersion($this->currentVersion - 1);
    }

    public function redo(): void
    {
        if ($this->currentVersion >= $this->latestVersion) {
            return;
        }

        $this->loadVersion($this->currentVersion + 1);
    }

    public function resetForm(): void
    {
        if ($this->currentVersion > 0) {
            $this->loadVersion($this->currentVersion);
        } else {
            $this->loadFieldsFromRecord();
        }
    }

    protected function loadVersion(int $version): void
    {
        $formVersion = $this->getRecord()->versions()->where('version', $version)->first();

        if (! $formVersion) {
            return;
        }

        $schema = is_array($formVersion->schema) ? $formVersion->schema : [];
        $this->loadSchema($schema);
        $this->currentVersion = $formVersion->version;
        $this->hasUnsavedChanges = false;
    }

    public function getCurrentVersionLabel(): ?string
    {
        if ($this->currentVersion === 0) {
            return null;
        }

        $version = $this->getRecord()->versions()->where('version', $this->currentVersion)->first();

        if (! $version) {
            return "v{$this->currentVersion}";
        }

        return "v{$this->currentVersion} ({$version->created_at->diffForHumans()})";
    }

    // --- Tabs ---

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // --- Field Types ---

    #[Computed]
    public function groupedFieldTypes(): array
    {
        return FormFieldType::grouped();
    }

    public function getSelectedField(): ?array
    {
        if ($this->selectedFieldKey === null) {
            return null;
        }

        foreach ($this->fields as $field) {
            if ($field['key'] === $this->selectedFieldKey) {
                return $field;
            }
        }

        return null;
    }

    public function getSelectedFieldType(): ?FormFieldType
    {
        $field = $this->getSelectedField();

        return $field ? FormFieldType::from($field['type']) : null;
    }

    // --- Preview ---

    public function previewSchema(Schema $schema): Schema
    {
        $this->syncFieldsToActivePage();

        if (count($this->pages) <= 1) {
            return $this->buildSinglePagePreview($schema);
        }

        return $this->buildMultiPagePreview($schema);
    }

    protected function buildSinglePagePreview(Schema $schema): Schema
    {
        $components = [];

        foreach ($this->fields as $field) {
            $component = $this->buildFilamentComponent($field);

            if ($component) {
                $components[] = $component;
            }
        }

        return $schema
            ->components([
                Form::make($components)
                    ->columns($this->columns),
            ])
            ->statePath('previewData');
    }

    protected function buildMultiPagePreview(Schema $schema): Schema
    {
        $steps = [];

        foreach ($this->pages as $index => $page) {
            $fields = $page['fields'] ?? [];
            $components = [];

            foreach ($fields as $field) {
                $component = $this->buildFilamentComponent($field);

                if ($component) {
                    $components[] = $component;
                }
            }

            $step = Step::make($this->getPageLabel($page, $index))
                ->schema($components)
                ->columns($this->columns);

            if (! empty($page['subheading'])) {
                $step->description($page['subheading']);
            }

            $steps[] = $step;
        }

        return $schema
            ->components([
                Wizard::make($steps)
                    ->skippable(),
            ])
            ->statePath('previewData');
    }

    protected function buildFilamentComponent(array $field): ?Component
    {
        $data = $field['data'] ?? [];
        $key = $field['key'];

        $component = match ($field['type']) {
            'text-input' => TextInput::make($key),
            'textarea' => Textarea::make($key)->rows(3),
            'number' => TextInput::make($key)->numeric(),
            'select' => Select::make($key)
                ->options(collect($data['options'] ?? [])->pluck('label', 'value')->all()),
            'checkbox' => Checkbox::make($key),
            'radio-group' => Radio::make($key)
                ->options(collect($data['options'] ?? [])->pluck('label', 'value')->all()),
            'toggle' => Toggle::make($key),
            'date-picker' => DatePicker::make($key),
            'file-upload' => FileUpload::make($key),
            'rich-editor' => RichEditor::make($key),
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

    // --- Field Settings Schema ---

    public function fieldSettingsSchema(Schema $schema): Schema
    {
        $selected = $this->getSelectedField();
        $selectedType = $this->getSelectedFieldType();

        if (! $selected || ! $selectedType) {
            return $schema->components([]);
        }

        $components = [
            TextInput::make('key')
                ->label('Field Name')
                ->helperText('Used in database and code')
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncSettingsToField()),
            TextInput::make('label')
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncSettingsToField()),
        ];

        if ($selectedType->hasPlaceholder()) {
            $components[] = TextInput::make('placeholder')
                ->placeholder('Enter placeholder text...')
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncSettingsToField());
        }

        $components[] = TextInput::make('helper_text')
            ->label('Helper Text')
            ->placeholder('Additional guidance for users...')
            ->live(onBlur: true)
            ->afterStateUpdated(fn () => $this->syncSettingsToField());

        if (! in_array($selected['type'], ['file-upload'])) {
            $components[] = TextInput::make('default_value')
                ->label('Default Value')
                ->placeholder('Default value...')
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncSettingsToField());
        }

        $components[] = Select::make('column_span')
            ->label('Column Span')
            ->options(collect(range(1, $this->columns))->mapWithKeys(
                fn (int $i): array => [$i => $i.' '.Str::plural('Column', $i)]
            )->all())
            ->live()
            ->afterStateUpdated(fn () => $this->syncSettingsToField());

        $components[] = Toggle::make('is_required')
            ->label('Required')
            ->live()
            ->afterStateUpdated(fn () => $this->syncSettingsToField());

        if ($selectedType->hasMinMax()) {
            $components[] = TextInput::make('min')
                ->label($selected['type'] === 'number' ? 'Min Value' : 'Min Length')
                ->numeric()
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncSettingsToField());
            $components[] = TextInput::make('max')
                ->label($selected['type'] === 'number' ? 'Max Value' : 'Max Length')
                ->numeric()
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncSettingsToField());
        }

        if ($selectedType->hasOptions()) {
            $components[] = Repeater::make('options')
                ->schema([
                    TextInput::make('label')
                        ->required(),
                    TextInput::make('value')
                        ->required(),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->reorderable(false)
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncSettingsToField());
        }

        return $schema
            ->components([
                Form::make($components)
                    ->columns(1),
            ])
            ->statePath('fieldSettingsData');
    }

    // --- Page Settings Schema ---

    public function pageSettingsSchema(Schema $schema): Schema
    {
        if ($this->selectedFieldKey !== null) {
            return $schema->components([]);
        }

        $activePage = $this->getActivePage();

        if (! $activePage) {
            return $schema->components([]);
        }

        $pageIndex = $this->getActivePageIndex();

        $components = [
            TextInput::make('title')
                ->label('Page Title')
                ->placeholder('Page '.($pageIndex + 1))
                ->helperText('Used in page tabs and wizard step labels')
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncPageSettingsToPage()),
            TextInput::make('heading')
                ->label('Heading')
                ->placeholder('Page heading...')
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncPageSettingsToPage()),
            TextInput::make('subheading')
                ->label('Subheading')
                ->placeholder('Page subheading...')
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncPageSettingsToPage()),
            TextInput::make('submit_button_text')
                ->label('Submit Button Text')
                ->placeholder($this->getSubmitButtonText($activePage, $pageIndex))
                ->helperText('Leave blank for default: "'.
                    $this->getSubmitButtonText($activePage, $pageIndex).'"')
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->syncPageSettingsToPage()),
        ];

        return $schema
            ->components([
                Form::make($components)
                    ->columns(1),
            ])
            ->statePath('pageSettingsData');
    }

    public function getActivePage(): ?array
    {
        foreach ($this->pages as $page) {
            if ($page['id'] === $this->activePageId) {
                return $page;
            }
        }

        return null;
    }

    public function syncPageSettingsFromPage(): void
    {
        $page = $this->getActivePage();

        if (! $page) {
            $this->pageSettingsData = [];

            return;
        }

        $this->pageSettingsData = [
            'title' => $page['title'] ?? null,
            'heading' => $page['heading'] ?? null,
            'subheading' => $page['subheading'] ?? null,
            'submit_button_text' => $page['submit_button_text'] ?? null,
        ];
    }

    public function syncPageSettingsToPage(): void
    {
        if (! $this->activePageId || empty($this->pageSettingsData)) {
            return;
        }

        foreach ($this->pages as $index => $page) {
            if ($page['id'] === $this->activePageId) {
                $this->pages[$index]['title'] = $this->pageSettingsData['title'] ?? null;
                $this->pages[$index]['heading'] = $this->pageSettingsData['heading'] ?? null;
                $this->pages[$index]['subheading'] = $this->pageSettingsData['subheading'] ?? null;
                $this->pages[$index]['submit_button_text'] = $this->pageSettingsData['submit_button_text'] ?? null;

                break;
            }
        }

        $this->hasUnsavedChanges = true;
    }

    // --- Field Settings Sync ---

    protected function syncSettingsFromField(): void
    {
        $selected = $this->getSelectedField();

        if (! $selected) {
            $this->fieldSettingsData = [];

            return;
        }

        $this->fieldSettingsData = [
            'key' => $selected['key'],
            ...($selected['data'] ?? []),
        ];
    }

    public function syncSettingsToField(): void
    {
        if (! $this->selectedFieldKey || empty($this->fieldSettingsData)) {
            return;
        }

        $settingsData = $this->fieldSettingsData;
        $newKey = Str::slug($settingsData['key'] ?? '', '_');
        unset($settingsData['key']);

        foreach ($this->fields as $index => $field) {
            if ($field['key'] === $this->selectedFieldKey) {
                $this->fields[$index]['data'] = array_merge($this->fields[$index]['data'], $settingsData);

                if ($newKey && $newKey !== $this->selectedFieldKey) {
                    if ($this->fieldKeyExists($newKey, $this->selectedFieldKey)) {
                        $newKey = $this->makeKeyUnique($newKey, $this->selectedFieldKey);
                    }
                    $this->fields[$index]['key'] = $newKey;
                    $this->fields[$index]['_manual_key'] = true;
                    $this->selectedFieldKey = $newKey;
                }

                break;
            }
        }

        $this->hasUnsavedChanges = true;
    }

    public function getBackUrl(): string
    {
        return $this->getResourceUrl('view');
    }

    // --- Key Helpers ---

    protected function generateUniqueFieldKey(string $label, ?string $excludeKey = null): string
    {
        $base = Str::slug($label, '_');

        if ($base === '') {
            $base = 'field';
        }

        if (! $this->fieldKeyExists($base, $excludeKey)) {
            return $base;
        }

        return $this->makeKeyUnique($base, $excludeKey);
    }

    protected function makeKeyUnique(string $base, ?string $excludeKey = null): string
    {
        $counter = 1;

        while ($this->fieldKeyExists("{$base}_{$counter}", $excludeKey)) {
            $counter++;
        }

        return "{$base}_{$counter}";
    }

    protected function fieldKeyExists(string $key, ?string $excludeKey = null): bool
    {
        foreach ($this->fields as $field) {
            if ($field['key'] === $key && $field['key'] !== $excludeKey) {
                return true;
            }
        }

        return false;
    }

    protected function fieldKeyWasManuallySet(int $index): bool
    {
        return ! empty($this->fields[$index]['_manual_key']);
    }

    protected function reindexSort(): void
    {
        foreach ($this->fields as $index => &$field) {
            $field['sort'] = $index;
        }
    }
}
