<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Submission;
use App\Services\AuditService;

class SubmissionObserver
{
    public function deleted(Submission $submission): void
    {
        AuditService::log(
            action: AuditAction::SubmissionDeleted,
            resource: $submission,
            details: ['form_id' => $submission->form_id],
        );
    }
}
