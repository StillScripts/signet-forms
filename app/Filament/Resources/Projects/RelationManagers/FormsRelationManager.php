<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Filament\Resources\Projects\Resources\Forms\FormResource;
use App\Filament\Resources\Projects\Resources\Forms\Tables\FormsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class FormsRelationManager extends RelationManager
{
    protected static string $relationship = 'forms';

    protected static ?string $relatedResource = FormResource::class;

    public function table(Table $table): Table
    {
        return FormsTable::configure($table);
    }
}
