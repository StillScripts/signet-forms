<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    @if ($submitted)
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                <x-filament::icon
                    icon="heroicon-o-check-circle"
                    class="h-6 w-6 text-green-600 dark:text-green-400"
                />
            </div>
            <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                {{ $formRecord->success_heading ?? 'Thank you!' }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ $formRecord->success_message ?? 'Your response has been recorded.' }}
            </p>
        </div>
    @else
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                {{ $formRecord->name }}
            </h1>
            @if ($formRecord->description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $formRecord->description }}
                </p>
            @endif
        </div>

        <form wire:submit="submit">
            {{ $this->form }}

            @unless ($this->isMultiPage())
                <div class="mt-6 flex justify-end">
                    <x-filament::button type="submit">
                        {{ $this->getSubmitLabel() }}
                    </x-filament::button>
                </div>
            @endunless
        </form>
    @endif
</div>
