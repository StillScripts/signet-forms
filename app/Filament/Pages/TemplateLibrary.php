<?php

namespace App\Filament\Pages;

use App\Enums\FormTemplateCategory;
use App\Enums\TeamPermission;
use App\Models\Form;
use App\Models\FormTemplate;
use App\Models\FormVersion;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class TemplateLibrary extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'Templates';

    protected static ?string $title = 'Template Library';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.template-library';

    public ?string $categoryFilter = null;

    public ?string $search = null;

    public static function canAccess(): bool
    {
        $team = Filament::getTenant();

        return $team && auth()->user()->belongsToTeam($team);
    }

    /**
     * @return Collection<int, FormTemplate>
     */
    public function getTemplates(): Collection
    {
        $query = FormTemplate::query()->orderBy('name');

        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        return $query->get();
    }

    /**
     * @return array<string, string>
     */
    public function getCategories(): array
    {
        return collect(FormTemplateCategory::cases())
            ->mapWithKeys(fn (FormTemplateCategory $category) => [$category->value => $category->label()])
            ->toArray();
    }

    public function useTemplate(string $templateId): void
    {
        $this->mountAction('createFromTemplate', ['template_id' => $templateId]);
    }

    public function createFromTemplateAction(): Action
    {
        return Action::make('createFromTemplate')
            ->label('Create Form from Template')
            ->modalHeading(fn (array $arguments): string => 'Create Form from "'.FormTemplate::find($arguments['template_id'] ?? '')?->name.'"')
            ->fillForm(fn (array $arguments): array => [
                'name' => FormTemplate::find($arguments['template_id'] ?? '')?->name,
            ])
            ->schema([
                TextInput::make('name')
                    ->label('Form Name')
                    ->required()
                    ->maxLength(255),
                Select::make('project_id')
                    ->label('Project')
                    ->options(fn () => Project::query()
                        ->where('team_id', Filament::getTenant()?->id)
                        ->pluck('name', 'id')
                        ->toArray())
                    ->required()
                    ->searchable(),
            ])
            ->modalSubmitActionLabel('Create Form')
            ->visible(fn () => auth()->user()->hasTeamPermission(Filament::getTenant(), TeamPermission::CreateForm))
            ->action(function (array $data, array $arguments): void {
                $template = FormTemplate::findOrFail($arguments['template_id']);

                $form = Form::create([
                    'project_id' => $data['project_id'],
                    'name' => $data['name'],
                    'schema' => $template->schema,
                    'is_published' => false,
                ]);

                FormVersion::create([
                    'form_id' => $form->id,
                    'version' => 1,
                    'schema' => $template->schema,
                ]);

                Notification::make()
                    ->title('Form created from template')
                    ->success()
                    ->send();

                $project = Project::find($data['project_id']);

                $this->redirect(route('filament.admin.resources.projects.forms.builder', [
                    'tenant' => Filament::getTenant(),
                    'project' => $project,
                    'record' => $form,
                ]));
            });
    }

    public function getFieldCount(FormTemplate $template): int
    {
        $schema = $template->schema;

        if (! isset($schema['pages']) || ! is_array($schema['pages'])) {
            return 0;
        }

        return collect($schema['pages'])
            ->sum(fn (array $page) => count($page['fields'] ?? []));
    }
}
