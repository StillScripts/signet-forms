<?php

namespace App\Listeners;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuditAuthEvents
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        AuditService::log(
            action: AuditAction::AuthLogin,
            actor: $user,
        );
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        AuditService::log(
            action: AuditAction::AuthLogout,
            actor: $user,
        );
    }

    public function handleFailed(Failed $event): void
    {
        AuditService::log(
            action: AuditAction::AuthLoginFailed,
            details: ['email' => $event->credentials['email'] ?? null],
        );
    }
}
