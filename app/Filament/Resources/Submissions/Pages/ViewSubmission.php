<?php

namespace App\Filament\Resources\Submissions\Pages;

use App\Enums\SubmissionStatus;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Models\Membership;
use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Submission')
                    ->schema([
                        TextEntry::make('form.name')
                            ->label('Form'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (SubmissionStatus $state) => $state->color())
                            ->formatStateUsing(fn (SubmissionStatus $state) => $state->label()),
                        TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime(),
                        TextEntry::make('form_version')
                            ->label('Form Version')
                            ->placeholder('—'),
                        TextEntry::make('assignedReviewer.name')
                            ->label('Assigned Reviewer')
                            ->placeholder('Unassigned'),
                    ])
                    ->columns(3),
                Section::make('Respondent')
                    ->schema([
                        TextEntry::make('respondent_name')
                            ->label('Name')
                            ->placeholder('—'),
                        TextEntry::make('respondent_email')
                            ->label('Email')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->visible(fn (Submission $record) => $record->respondent_name || $record->respondent_email),
                Section::make('Response Data')
                    ->schema([
                        KeyValueEntry::make('data')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                    ]),
                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('metadata.ip_address')
                            ->label('IP Address')
                            ->placeholder('—'),
                        TextEntry::make('metadata.user_agent')
                            ->label('User Agent')
                            ->placeholder('—'),
                        TextEntry::make('metadata.referer')
                            ->label('Referer')
                            ->placeholder('—'),
                    ])
                    ->columns(1)
                    ->collapsed()
                    ->visible(fn (Submission $record) => ! empty($record->metadata)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        /** @var Submission $record */
        $record = $this->record;
        $transitions = $record->status->transitions();

        return [
            Action::make('changeStatus')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => Gate::allows('review', $record) && count($transitions) > 0)
                ->schema([
                    Select::make('status')
                        ->label('New Status')
                        ->options(
                            collect($transitions)
                                ->mapWithKeys(fn (SubmissionStatus $s) => [$s->value => $s->label()])
                                ->toArray()
                        )
                        ->required(),
                ])
                ->action(function (array $data) use ($record): void {
                    $record->transitionTo(SubmissionStatus::from($data['status']));

                    Notification::make()
                        ->title('Status updated')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            Action::make('assignReviewer')
                ->label('Assign Reviewer')
                ->icon('heroicon-o-user')
                ->visible(fn () => Gate::allows('review', $record))
                ->schema([
                    Select::make('assigned_reviewer_id')
                        ->label('Reviewer')
                        ->options(fn () => Membership::where('team_id', Filament::getTenant()?->id)
                            ->with('user')
                            ->get()
                            ->pluck('user.name', 'user.id')
                            ->toArray())
                        ->searchable()
                        ->nullable()
                        ->placeholder('Unassigned'),
                ])
                ->fillForm(fn () => ['assigned_reviewer_id' => $record->assigned_reviewer_id])
                ->action(function (array $data) use ($record): void {
                    $record->update(['assigned_reviewer_id' => $data['assigned_reviewer_id']]);

                    Notification::make()
                        ->title('Reviewer updated')
                        ->success()
                        ->send();

                    $this->refreshFormData(['assigned_reviewer_id']);
                }),
        ];
    }
}
