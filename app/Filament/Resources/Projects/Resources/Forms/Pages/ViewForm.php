<?php

namespace App\Filament\Resources\Projects\Resources\Forms\Pages;

use App\Filament\Resources\Projects\Resources\Forms\FormResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewForm extends ViewRecord
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('builder')
                ->label('Open Builder')
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->url(fn () => $this->getResourceUrl('builder')),
            EditAction::make(),
        ];
    }
}
