<?php

namespace App\Filament\Resources\Submissions\Tables;

use App\Enums\SubmissionExportStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TeamPermission;
use App\Jobs\ProcessBulkExport;
use App\Models\Form;
use App\Models\Submission;
use App\Models\SubmissionExport;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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
            ->headerActions([
                static::exportAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function exportAction(): Action
    {
        return Action::make('exportSubmissions')
            ->label('Export Submissions')
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn () => auth()->user()?->hasTeamPermission(Filament::getTenant(), TeamPermission::ExportSubmission))
            ->modalHeading('Export submissions')
            ->modalDescription('We will email you a download link when the export is ready.')
            ->schema([
                Select::make('form_id')
                    ->label('Form')
                    ->required()
                    ->searchable()
                    ->options(fn () => Form::whereHas('project', fn ($q) => $q->where('team_id', Filament::getTenant()?->id))
                        ->pluck('name', 'id')
                        ->toArray()),
                Select::make('statuses')
                    ->label('Status (optional)')
                    ->multiple()
                    ->options(collect(SubmissionStatus::cases())
                        ->mapWithKeys(fn (SubmissionStatus $s) => [$s->value => $s->label()])
                        ->toArray()),
                DatePicker::make('from')
                    ->label('Submitted from'),
                DatePicker::make('until')
                    ->label('Submitted until'),
                Textarea::make('reason')
                    ->label('Reason for export')
                    ->required()
                    ->minLength(10)
                    ->maxLength(500)
                    ->rows(3)
                    ->helperText('Captured in the audit log.'),
            ])
            ->modalSubmitActionLabel('Queue Export')
            ->action(function (array $data): void {
                $export = SubmissionExport::create([
                    'team_id' => Filament::getTenant()?->id,
                    'form_id' => $data['form_id'],
                    'requested_by' => auth()->id(),
                    'status' => SubmissionExportStatus::Pending,
                    'format' => 'csv',
                    'filters' => array_filter([
                        'statuses' => $data['statuses'] ?? null,
                        'from' => $data['from'] ?? null,
                        'until' => $data['until'] ?? null,
                    ]),
                    'reason' => $data['reason'],
                ]);

                ProcessBulkExport::dispatch($export->id);

                Notification::make()
                    ->title('Export queued')
                    ->body("We'll email you when it's ready.")
                    ->success()
                    ->send();
            });
    }
}
