<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use App\Models\Form;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New form')
                ->schema([
                    Select::make('project_id')
                        ->label('Project')
                        ->options(fn () => Project::where('team_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->maxLength(1000)
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $form = Form::create([
                        'project_id' => $data['project_id'],
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                    ]);

                    $this->redirect(route('filament.admin.resources.projects.forms.view', [
                        'tenant' => Filament::getTenant(),
                        'project' => $form->project,
                        'record' => $form,
                    ]));
                }),
        ];
    }
}
