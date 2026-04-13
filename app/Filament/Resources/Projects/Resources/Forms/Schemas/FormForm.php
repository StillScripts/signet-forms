<?php

namespace App\Filament\Resources\Projects\Resources\Forms\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                Section::make('Success Page')
                    ->description('Customise the message shown after a form is submitted.')
                    ->schema([
                        TextInput::make('success_heading')
                            ->label('Heading')
                            ->placeholder('Thank you!')
                            ->maxLength(255),
                        Textarea::make('success_message')
                            ->label('Message')
                            ->placeholder('Your response has been recorded.')
                            ->rows(2)
                            ->maxLength(1000),
                    ])
                    ->collapsed()
                    ->hiddenOn('create'),
            ]);
    }
}
