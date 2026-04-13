<?php

namespace App\Filament\Resources\Submissions\Tables;

use App\Enums\SubmissionStatus;
use App\Models\Form;
use App\Models\Submission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('form.name')
                    ->label('Form')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SubmissionStatus $state) => $state->color())
                    ->formatStateUsing(fn (SubmissionStatus $state) => $state->label())
                    ->sortable(),
                TextColumn::make('respondent_email')
                    ->label('Respondent')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('data')
                    ->label('Response')
                    ->formatStateUsing(function (Submission $record): string {
                        $preview = collect($record->data)
                            ->filter(fn ($value) => filled($value))
                            ->take(3)
                            ->map(fn ($value, $key) => Str::of($key)->replace('_', ' ')->title().": {$value}")
                            ->join(', ');

                        return $preview ?: 'Empty response';
                    })
                    ->wrap()
                    ->color(fn (Submission $record) => collect($record->data)->filter(fn ($v) => filled($v))->isEmpty() ? 'gray' : null),
                TextColumn::make('created_at')
                    ->label('Submitted at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('form_id')
                    ->label('Form')
                    ->options(fn () => Form::whereHas('project', fn ($q) => $q->where('team_id', Filament::getTenant()?->id))->pluck('name', 'id'))
                    ->searchable(),
                SelectFilter::make('status')
                    ->options(collect(SubmissionStatus::cases())->mapWithKeys(fn (SubmissionStatus $s) => [$s->value => $s->label()])->toArray()),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
