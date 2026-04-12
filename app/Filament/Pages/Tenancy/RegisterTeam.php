<?php

namespace App\Filament\Pages\Tenancy;

use App\Actions\Teams\CreateTeam;
use App\Models\Team;
use App\Rules\TeamName;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Create team';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Team name')
                    ->required()
                    ->maxLength(255)
                    ->rules([new TeamName]),
            ]);
    }

    protected function handleRegistration(array $data): Team
    {
        return app(CreateTeam::class)->handle(auth()->user(), $data['name']);
    }
}
