<?php

namespace App\Filament\Resources\Projects\Resources\Forms\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(1000)
                    ->rows(3),
                Toggle::make('is_published')
                    ->label('Published')
                    ->helperText('Published forms can accept submissions.')
                    ->hiddenOn('create'),
            ]);
    }
}
