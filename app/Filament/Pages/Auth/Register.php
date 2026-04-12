<?php

namespace App\Filament\Pages\Auth;

use App\Actions\Teams\CreateTeam;
use Filament\Auth\Pages\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = $this->getUserModel()::create($data);

            app(CreateTeam::class)->handle($user, $user->name."'s Team", isPersonal: true);

            return $user;
        });
    }
}
