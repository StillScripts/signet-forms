<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Form;
use App\Services\AuditService;

class FormObserver
{
    public function created(Form $form): void
    {
        AuditService::log(
            action: AuditAction::FormCreated,
            resource: $form,
            details: ['name' => $form->name],
        );
    }

    public function updated(Form $form): void
    {
        if ($form->wasChanged('is_published')) {
            AuditService::log(
                action: $form->is_published ? AuditAction::FormPublished : AuditAction::FormUnpublished,
                resource: $form,
                details: ['name' => $form->name],
            );

            return;
        }

        $changed = array_keys($form->getChanges());
        $changed = array_values(array_diff($changed, ['updated_at']));

        if ($changed === []) {
            return;
        }

        AuditService::log(
            action: AuditAction::FormUpdated,
            resource: $form,
            details: ['name' => $form->name, 'changed' => $changed],
        );
    }

    public function deleted(Form $form): void
    {
        AuditService::log(
            action: AuditAction::FormDeleted,
            resource: $form,
            details: ['name' => $form->name],
        );
    }
}
