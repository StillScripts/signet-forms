<?php

namespace App\Filament\Pages;

use App\Enums\AuditAction;
use App\Enums\TeamPermission;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $title = 'Audit Log';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.audit-logs';

    public static function canAccess(): bool
    {
        $team = Filament::getTenant();
        $user = auth()->user();

        return $team && $user?->hasTeamPermission($team, TeamPermission::ViewAudit);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Actor')
                    ->placeholder('System')
                    ->searchable(),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (AuditAction $state) => $state->color())
                    ->formatStateUsing(fn (AuditAction $state) => $state->label())
                    ->sortable(),
                TextColumn::make('resource_type')
                    ->label('Resource')
                    ->placeholder('—')
                    ->formatStateUsing(function ($state, AuditLog $record) {
                        if (! $state) {
                            return '—';
                        }

                        return $state.($record->resource_id ? ' '.substr((string) $record->resource_id, 0, 8) : '');
                    }),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options(
                        collect(AuditAction::cases())
                            ->mapWithKeys(fn (AuditAction $a) => [$a->value => $a->label()])
                            ->toArray()
                    )
                    ->searchable(),
                SelectFilter::make('user_id')
                    ->label('Actor')
                    ->options(fn () => User::whereHas(
                        'teams',
                        fn (Builder $q) => $q->where('teams.id', Filament::getTenant()?->id)
                    )->pluck('name', 'id')->toArray())
                    ->searchable(),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getTableQuery(): Builder
    {
        $team = Filament::getTenant();

        return AuditLog::query()
            ->with('user:id,name')
            ->where('team_id', $team?->id);
    }
}
