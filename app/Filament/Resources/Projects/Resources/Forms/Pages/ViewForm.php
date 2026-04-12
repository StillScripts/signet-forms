<?php

namespace App\Filament\Resources\Projects\Resources\Forms\Pages;

use App\Filament\Resources\Projects\Resources\Forms\FormResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewForm extends ViewRecord
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
