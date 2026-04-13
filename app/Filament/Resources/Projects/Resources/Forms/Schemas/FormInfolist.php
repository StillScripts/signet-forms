<?php

namespace App\Filament\Resources\Projects\Resources\Forms\Schemas;

use App\Models\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;

class FormInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('description')
                    ->placeholder('No description'),
                IconEntry::make('is_published')
                    ->label('Published')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('public_url')
                    ->label('Public URL')
                    ->state(fn (Form $record): string => route('forms.show', ['team' => $record->project->team, 'formSlug' => $record->slug]))
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->columnSpanFull()
                    ->visible(fn (Form $record): bool => $record->is_published),
            ]);
    }
}
