<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Form;
use App\Models\User;
use Filament\Facades\Filament;

class FormPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $team = Filament::getTenant();

        return $team && $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Form $form): bool
    {
        return $user->belongsToTeam($form->project->team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $team = Filament::getTenant();

        return $team && $user->hasTeamPermission($team, TeamPermission::CreateForm);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Form $form): bool
    {
        return $user->hasTeamPermission($form->project->team, TeamPermission::UpdateForm);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Form $form): bool
    {
        return $user->hasTeamPermission($form->project->team, TeamPermission::DeleteForm);
    }

    /**
     * Determine whether the user can publish or unpublish the model.
     */
    public function publish(User $user, Form $form): bool
    {
        return $user->hasTeamPermission($form->project->team, TeamPermission::PublishForm);
    }
}
