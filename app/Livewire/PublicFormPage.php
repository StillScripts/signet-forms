<?php

namespace App\Livewire;

use App\Enums\FieldComponentRenderTarget;
use App\Models\Form as FormModel;
use App\Models\Submission;
use App\Models\Team;
use App\Services\FieldComponentBuilder;
use Filament\Facades\Filament;
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
        return app(FieldComponentBuilder::class)->buildFilamentComponent($field, FieldComponentRenderTarget::PublicForm);
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
