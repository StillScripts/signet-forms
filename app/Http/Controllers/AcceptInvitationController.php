<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcceptInvitationController
{
    public function __invoke(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        if ($invitation->isAccepted()) {
            return redirect()->route('filament.admin.pages.dashboard', [
                'tenant' => $user->currentTeam?->slug,
            ])->with('error', 'This invitation has already been accepted.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('filament.admin.pages.dashboard', [
                'tenant' => $user->currentTeam?->slug,
            ])->with('error', 'This invitation has expired.');
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return redirect()->route('filament.admin.pages.dashboard', [
                'tenant' => $user->currentTeam?->slug,
            ])->with('error', 'This invitation was sent to a different email address.');
        }

        $team = $invitation->team;

        if (! $user->belongsToTeam($team)) {
            $team->members()->attach($user, [
                'role' => $invitation->role->value,
            ]);
        }

        $invitation->update(['accepted_at' => now()]);

        $user->switchTeam($team);

        return redirect()->route('filament.admin.pages.dashboard', [
            'tenant' => $team->slug,
        ]);
    }
}
