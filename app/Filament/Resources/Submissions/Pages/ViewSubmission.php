<?php

namespace App\Filament\Resources\Submissions\Pages;

use App\Filament\Resources\Submissions\SubmissionResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('form.name')
                    ->label('Form'),
                TextEntry::make('created_at')
                    ->label('Submitted')
                    ->dateTime(),
                KeyValueEntry::make('data')
                    ->label('Response Data')
                    ->columnSpanFull(),
            ]);
    }
}
