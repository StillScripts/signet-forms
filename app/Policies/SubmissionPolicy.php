<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Submission;
use App\Models\User;
use Filament\Facades\Filament;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        $team = Filament::getTenant();

        return $team && $user->hasTeamPermission($team, TeamPermission::ViewSubmission);
    }

    public function view(User $user, Submission $submission): bool
    {
        return $user->hasTeamPermission($submission->form->project->team, TeamPermission::ViewSubmission);
    }

    public function review(User $user, Submission $submission): bool
    {
        return $user->hasTeamPermission($submission->form->project->team, TeamPermission::ReviewSubmission);
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->hasTeamPermission($submission->form->project->team, TeamPermission::DeleteSubmission);
    }
}
