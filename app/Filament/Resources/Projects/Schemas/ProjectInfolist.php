<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('description')
                    ->placeholder('No description'),
                TextEntry::make('url')
                    ->label('URL')
                    ->url(fn (Project $record): ?string => $record->url && (str_starts_with($record->url, 'http://') || str_starts_with($record->url, 'https://')) ? $record->url : null)
                    ->openUrlInNewTab()
                    ->placeholder('No URL'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
