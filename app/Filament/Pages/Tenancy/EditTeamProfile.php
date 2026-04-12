<?php

namespace App\Filament\Pages\Tenancy;

use App\Rules\TeamName;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditTeamProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Team settings';
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
}
