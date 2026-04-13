<?php

namespace App\Filament\Resources\Forms;

use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Forms\Tables\FormsTable;
use App\Models\Form;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static ?string $slug = 'all-forms';

    protected static ?string $navigationLabel = 'Forms';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 1;

    protected static bool $isScopedToTenant = false;

    public static function table(Table $table): Table
    {
        return FormsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('project', fn (Builder $query) => $query->where('team_id', Filament::getTenant()?->id));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
        ];
    }
}
