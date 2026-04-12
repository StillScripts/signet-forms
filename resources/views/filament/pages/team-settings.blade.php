<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Team Members">
            {{ $this->table }}
        </x-filament::section>

        @if(auth()->user()->hasTeamPermission(\Filament\Facades\Filament::getTenant(), \App\Enums\TeamPermission::CancelInvitation))
            @php
                $invitations = $this->getInvitations();
            @endphp

            @if($invitations->isNotEmpty())
                <x-filament::section heading="Pending Invitations">
                    <div class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach($invitations as $invitation)
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $invitation->email }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $invitation->role->label() }} &middot; Invited {{ $invitation->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <x-filament::button
                                    color="danger"
                                    size="sm"
                                    wire:click="cancelInvitation('{{ $invitation->code }}')"
                                    wire:confirm="Are you sure you want to cancel this invitation?"
                                >
                                    Cancel
                                </x-filament::button>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
