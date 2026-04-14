<?php

namespace App\Filament\Resources\Projects\Resources\Forms\Schemas;

use App\Models\Form;
use App\Services\QrCodeService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
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
                Section::make('QR Code')
                    ->description('Scan to open the public form URL')
                    ->icon('heroicon-o-qr-code')
                    ->schema([
                        ViewEntry::make('qr_code')
                            ->label('')
                            ->view('filament.infolists.components.qr-code')
                            ->state(function (Form $record): string {
                                return app(QrCodeService::class)->generateSvg(
                                    route('forms.show', [
                                        'team' => $record->project->team,
                                        'formSlug' => $record->slug,
                                    ]),
                                );
                            }),
                    ])
                    ->collapsible()
                    ->columnSpanFull()
                    ->visible(fn (Form $record): bool => $record->is_published),
            ]);
    }
}
