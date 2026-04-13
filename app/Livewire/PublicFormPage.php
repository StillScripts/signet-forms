<?php

namespace App\Livewire;

use App\Models\Form as FormModel;
use App\Models\Submission;
use App\Models\Team;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PublicFormPage extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public FormModel $formRecord;

    public Team $team;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public bool $submitted = false;

    public function mount(Team $team, string $formSlug): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->team = $team;

        $formRecord = FormModel::where('slug', $formSlug)
            ->whereHas('project', fn ($query) => $query->where('team_id', $team->id))
            ->firstOrFail();

        abort_unless($formRecord->is_published, 404);

        $this->formRecord = $formRecord;
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $pages = $this->formRecord->schema['pages'] ?? [];

        if (count($pages) <= 1) {
            return $this->buildSinglePageForm($schema, $pages[0] ?? []);
        }

        return $this->buildMultiPageForm($schema, $pages);
    }

    public function submit(): void
    {
        $formData = $this->form->getState();

        Submission::create([
            'form_id' => $this->formRecord->id,
            'data' => $formData,
        ]);

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.public-form-page')
            ->layout('layouts.public');
    }

    protected function buildSinglePageForm(Schema $schema, array $page): Schema
    {
        $fields = $page['fields'] ?? [];
        $components = $this->buildFieldComponents($fields);

        return $schema
            ->components($components)
            ->columns(2)
            ->statePath('data');
    }

    protected function buildMultiPageForm(Schema $schema, array $pages): Schema
    {
        $steps = [];

        foreach ($pages as $index => $page) {
            $fields = $page['fields'] ?? [];
            $components = $this->buildFieldComponents($fields);

            $label = $page['title'] ?: 'Step '.($index + 1);

            $step = Step::make($label)
                ->schema($components)
                ->columns(2);

            if (! empty($page['subheading'])) {
                $step->description($page['subheading']);
            }

            $steps[] = $step;
        }

        return $schema
            ->components([
                Wizard::make($steps)
                    ->submitAction(view('livewire.partials.submit-button', [
                        'label' => $this->getSubmitLabel(),
                    ])),
            ])
            ->statePath('data');
    }

    /**
     * @return array<SchemaComponent>
     */
    protected function buildFieldComponents(array $fields): array
    {
        return collect($fields)
            ->map(fn (array $field) => $this->buildFilamentComponent($field))
            ->filter()
            ->values()
            ->all();
    }

    protected function buildFilamentComponent(array $field): ?SchemaComponent
    {
        $data = $field['data'] ?? [];
        $key = $field['key'];
        $type = $field['type'];

        $component = match ($type) {
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
            'file-upload' => null,
            'rich-editor' => Textarea::make($key)->rows(4),
            default => null,
        };

        if ($component === null) {
            return null;
        }

        $component->label($data['label'] ?? $key);

        if (! empty($data['placeholder'])) {
            $component->placeholder($data['placeholder']);
        }

        if (! empty($data['helper_text'])) {
            $component->helperText($data['helper_text']);
        }

        if (! empty($data['default_value'])) {
            $component->default($data['default_value']);
        }

        if ($data['is_required'] ?? false) {
            $component->required();
        }

        $columnSpan = $data['column_span'] ?? 1;
        if ($columnSpan > 1) {
            $component->columnSpan($columnSpan);
        }

        return $component;
    }

    public function getSubmitLabel(): string
    {
        $pages = $this->formRecord->schema['pages'] ?? [];
        $lastPage = end($pages);

        if (! empty($lastPage['submit_button_text'])) {
            return $lastPage['submit_button_text'];
        }

        return 'Submit';
    }

    public function isMultiPage(): bool
    {
        return count($this->formRecord->schema['pages'] ?? []) > 1;
    }
}
