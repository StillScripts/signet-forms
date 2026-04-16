<?php

namespace App\Filament\Resources\Submissions;

use App\Filament\Resources\Submissions\Pages\ListSubmissions;
use App\Filament\Resources\Submissions\Pages\ViewSubmission;
use App\Filament\Resources\Submissions\Tables\SubmissionsTable;
use App\Models\Submission;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $slug = 'submissions';

    protected static ?string $navigationLabel = 'Submissions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 2;

    protected static bool $isScopedToTenant = false;

    public static function table(Table $table): Table
    {
        return SubmissionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->completed()
            ->whereHas('form', fn (Builder $query) => $query->whereHas('project', fn (Builder $q) => $q->where('team_id', Filament::getTenant()?->id)));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubmissions::route('/'),
            'view' => ViewSubmission::route('/{record}'),
        ];
    }
}
