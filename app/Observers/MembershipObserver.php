<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Membership;
use App\Services\AuditService;

class MembershipObserver
{
    public function created(Membership $membership): void
    {
        AuditService::log(
            action: AuditAction::MemberInvited,
            resource: $membership,
            details: [
                'member_user_id' => $membership->user_id,
                'role' => $membership->role?->value,
            ],
        );
    }

    public function updated(Membership $membership): void
    {
        if (! $membership->wasChanged('role')) {
            return;
        }

        AuditService::log(
            action: AuditAction::MemberRoleChanged,
            resource: $membership,
            details: [
                'member_user_id' => $membership->user_id,
                'old_role' => $membership->getOriginal('role'),
                'new_role' => $membership->role?->value,
            ],
        );
    }

    public function deleted(Membership $membership): void
    {
        AuditService::log(
            action: AuditAction::MemberRemoved,
            resource: $membership,
            details: [
                'member_user_id' => $membership->user_id,
                'role' => $membership->role?->value,
            ],
        );
    }
}
