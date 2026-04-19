<div @class([
    'mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8' => ! $embedded,
    'w-full p-4' => $embedded,
])>
    @if ($submitted)
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20">
                <x-filament::icon
                    icon="heroicon-o-check-circle"
                    class="h-6 w-6 text-green-600 dark:text-green-400"
                />
            </div>
            <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                {{ $formRecord->settings->confirmation->heading }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ $formRecord->settings->confirmation->message }}
            </p>
        </div>
    @elseif ($alreadySubmitted)
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                This submission has already been completed.
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Your response was already received. You do not need to submit again.
            </p>
        </div>
    @else
        @unless ($embedded)
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
        @endunless

        @if ($this->isResumed())
            <div class="mb-6 rounded-lg bg-blue-50 p-4 text-sm text-blue-900 ring-1 ring-blue-200 dark:bg-blue-950/30 dark:text-blue-200 dark:ring-blue-800">
                Welcome back. Your previous progress has been loaded.
            </div>
        @endif

        <form wire:submit="submit">
            {{ $this->form }}

            <div class="mt-6 flex items-center justify-between gap-3">
                <div>
                    @if (! $showSaveForm && ! $saveEmailSent)
                        <button
                            type="button"
                            wire:click="openSaveForm"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                        >
                            Save and finish later
                        </button>
                    @endif

                    @if ($saveEmailSent)
                        <p class="text-sm text-green-700 dark:text-green-400">
                            Check your email for a link to resume this submission.
                        </p>
                    @endif
                </div>

                @unless ($this->isMultiPage())
                    <x-filament::button type="submit">
                        {{ $this->getSubmitLabel() }}
                    </x-filament::button>
                @endunless
            </div>

            @if ($showSaveForm && ! $saveEmailSent)
                <div class="mt-4 rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200 dark:bg-gray-900/50 dark:ring-white/10">
                    <label for="saveEmail" class="block text-sm font-medium text-gray-950 dark:text-white">
                        Email address
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        We will send you a link so you can return and finish this form later.
                    </p>
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <input
                            type="email"
                            id="saveEmail"
                            wire:model="saveEmail"
                            class="flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                            placeholder="you@example.com"
                        >
                        <x-filament::button type="button" wire:click="saveProgress">
                            Send me a link
                        </x-filament::button>
                        <x-filament::button type="button" color="gray" wire:click="cancelSave">
                            Cancel
                        </x-filament::button>
                    </div>
                    @error('saveEmail')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </form>
    @endif
</div>
