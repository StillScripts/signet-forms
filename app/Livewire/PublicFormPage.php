<?php

namespace App\Livewire;

use App\Enums\FormFieldType;
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
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Image as ImageSchemaComponent;
use Filament\Schemas\Components\Text;
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
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer'),
            ],
            'form_version' => $this->formRecord->latestVersion()?->version,
            'respondent_email' => $this->extractRespondentEmail($formData),
            'respondent_name' => $this->extractRespondentName($formData),
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
        $type = FormFieldType::tryFrom($field['type'] ?? '');

        if ($type === null) {
            return null;
        }

        if ($type->isLayout()) {
            return $this->buildLayoutComponent($type, $field);
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
            FormFieldType::FileUpload => null,
            FormFieldType::RichEditor => Textarea::make($key)->rows(4),
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

    /**
     * @param  array<string, mixed>  $field
     */
    protected function buildLayoutComponent(FormFieldType $type, array $field): ?SchemaComponent
    {
        $data = $field['data'] ?? [];

        $component = match ($type) {
            FormFieldType::SectionHeader => $this->buildSectionHeaderComponent($data),
            FormFieldType::Divider => Html::make('<hr class="my-2 border-gray-200 dark:border-white/10" />'),
            FormFieldType::InstructionalText => Text::make((string) ($data['content'] ?? '')),
            FormFieldType::Image => ! empty($data['url'])
                ? ImageSchemaComponent::make($data['url'], (string) ($data['alt'] ?? ''))
                : null,
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
    protected function buildSectionHeaderComponent(array $data): SchemaComponent
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

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractRespondentEmail(array $data): ?string
    {
        return $data['email'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractRespondentName(array $data): ?string
    {
        if (! empty($data['name'])) {
            return $data['name'];
        }

        $parts = array_filter([
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
        ]);

        return $parts ? implode(' ', $parts) : null;
    }
}
