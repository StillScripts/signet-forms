<?php

namespace App\Filament\Pages;

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Rules\UniqueTeamInvitation;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TeamSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Team Settings';

    protected static ?string $title = 'Team Settings';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.team-settings';

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && auth()->user()->belongsToTeam($tenant);
    }

    public function table(Table $table): Table
    {
        $team = Filament::getTenant();

        return $table
            ->query(Membership::query()->where('team_id', $team->id))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (TeamRole $state) => $state->label()),
            ])
            ->headerActions([
                Action::make('invite')
                    ->label('Invite member')
                    ->icon('heroicon-o-plus')
                    ->visible(fn () => Gate::allows('inviteMember', $team))
                    ->schema([
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->rules([new UniqueTeamInvitation($team)]),
                        Select::make('role')
                            ->label('Role')
                            ->options(
                                collect(TeamRole::assignable())
                                    ->mapWithKeys(fn (array $role) => [$role['value'] => $role['label']])
                                    ->toArray()
                            )
                            ->required()
                            ->default(TeamRole::Editor->value),
                    ])
                    ->action(function (array $data) use ($team): void {
                        $invitation = $team->invitations()->create([
                            'email' => $data['email'],
                            'role' => $data['role'],
                            'invited_by' => auth()->id(),
                            'expires_at' => now()->addDays(3),
                        ]);

                        \Illuminate\Support\Facades\Notification::route('mail', $data['email'])
                            ->notify(new TeamInvitationNotification($invitation));

                        Notification::make()
                            ->title('Invitation sent')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('changeRole')
                    ->label('Change role')
                    ->icon('heroicon-o-pencil')
                    ->visible(fn (Membership $record) => Gate::allows('updateMember', $team) && $record->role !== TeamRole::Owner)
                    ->schema([
                        Select::make('role')
                            ->label('Role')
                            ->options(
                                collect(TeamRole::assignable())
                                    ->mapWithKeys(fn (array $role) => [$role['value'] => $role['label']])
                                    ->toArray()
                            )
                            ->required(),
                    ])
                    ->fillForm(fn (Membership $record) => ['role' => $record->role->value])
                    ->action(function (Membership $record, array $data): void {
                        $record->update(['role' => $data['role']]);

                        Notification::make()
                            ->title('Role updated')
                            ->success()
                            ->send();
                    }),
                Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (Membership $record) => Gate::allows('removeMember', $team) && $record->role !== TeamRole::Owner)
                    ->requiresConfirmation()
                    ->modalHeading('Remove team member')
                    ->modalDescription(fn (Membership $record) => "Are you sure you want to remove {$record->user->name} from this team?")
                    ->action(function (Membership $record) use ($team): void {
                        $user = $record->user;
                        $record->delete();

                        if ($user->current_team_id === $team->id) {
                            $fallback = $user->fallbackTeam($team);

                            if ($fallback) {
                                $user->switchTeam($fallback);
                            }
                        }

                        Notification::make()
                            ->title('Member removed')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $team = Filament::getTenant();

        return [
            Action::make('deleteTeam')
                ->label('Delete team')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->visible(fn () => Gate::allows('delete', $team))
                ->requiresConfirmation()
                ->modalHeading('Delete team')
                ->modalDescription("Are you sure you want to delete \"{$team->name}\"? This action cannot be undone.")
                ->schema([
                    TextInput::make('confirmName')
                        ->label("Type \"{$team->name}\" to confirm")
                        ->required()
                        ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail) use ($team) {
                            if ($value !== $team->name) {
                                $fail('The team name does not match.');
                            }
                        }),
                ])
                ->action(function () use ($team): void {
                    DB::transaction(function () use ($team) {
                        $members = $team->members()->get();

                        foreach ($members as $member) {
                            if ($member->current_team_id === $team->id) {
                                $fallback = $member->fallbackTeam($team);

                                if ($fallback) {
                                    $member->switchTeam($fallback);
                                }
                            }
                        }

                        $team->invitations()->delete();
                        $team->memberships()->delete();
                        $team->delete();
                    });

                    $this->redirect(Filament::getUrl());
                }),
        ];
    }

    public function getInvitations(): Collection
    {
        $team = Filament::getTenant();

        return $team->invitations()
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();
    }

    public function cancelInvitation(string $code): void
    {
        $team = Filament::getTenant();
        Gate::authorize('cancelInvitation', $team);

        $invitation = $team->invitations()->where('code', $code)->firstOrFail();
        $invitation->delete();

        Notification::make()
            ->title('Invitation cancelled')
            ->success()
            ->send();
    }
}
