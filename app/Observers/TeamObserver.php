<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Team;
use App\Services\AuditService;

class TeamObserver
{
    public function updated(Team $team): void
    {
        $changed = array_keys($team->getChanges());
        $changed = array_values(array_diff($changed, ['updated_at']));

        if ($changed === []) {
            return;
        }

        AuditService::log(
            action: AuditAction::TeamUpdated,
            resource: $team,
            details: ['changed' => $changed],
            team: $team,
        );
    }

    public function deleted(Team $team): void
    {
        AuditService::log(
            action: AuditAction::TeamDeleted,
            resource: $team,
            details: ['name' => $team->name],
            team: $team,
        );
    }
}
