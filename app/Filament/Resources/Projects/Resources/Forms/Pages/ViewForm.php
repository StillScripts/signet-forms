<?php

namespace App\Filament\Resources\Projects\Resources\Forms\Pages;

use App\Filament\Resources\Projects\Resources\Forms\FormResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;

class ViewForm extends ViewRecord
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        $publicUrl = $this->record->is_published
            ? route('forms.show', [
                'team' => $this->record->project->team,
                'formSlug' => $this->record->slug,
            ])
            : null;

        return [
            Action::make('share')
                ->label('Share')
                ->icon(Heroicon::OutlinedLink)
                ->color(Color::Emerald)
                ->url($publicUrl, shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->is_published),
            Action::make('builder')
                ->label('Open Builder')
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->url(fn () => $this->getResourceUrl('builder')),
            Action::make('submissions')
                ->label('Submissions')
                ->icon(Heroicon::OutlinedInboxStack)
                ->url(fn () => route('filament.admin.resources.submissions.index', [
                    'tenant' => Filament::getTenant(),
                    'tableFilters' => ['form_id' => ['value' => $this->record->id]],
                ]))
                ->badge(fn () => $this->record->submissions()->completed()->count() ?: null),
            EditAction::make(),
        ];
    }
}
