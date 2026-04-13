<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search templates..."
                    />
                </x-filament::input.wrapper>
            </div>
            <div class="sm:w-64">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="categoryFilter">
                        <option value="">All Categories</option>
                        @foreach($this->getCategories() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>

        {{-- Template Grid --}}
        @php $templates = $this->getTemplates(); @endphp

        @if($templates->isEmpty())
            <x-filament::section>
                <div class="text-center py-12">
                    <x-filament::icon
                        icon="heroicon-o-rocket-launch"
                        class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
                    />
                    <h3 class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">No templates found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($search || $categoryFilter)
                            Try adjusting your search or filter.
                        @else
                            No templates are available yet.
                        @endif
                    </p>
                </div>
            </x-filament::section>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($templates as $template)
                    <x-filament::section class="relative">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    @if($template->icon)
                                        <x-filament::icon
                                            :icon="$template->icon"
                                            class="h-8 w-8 text-gray-400 dark:text-gray-500"
                                        />
                                    @endif
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                                            {{ $template->name }}
                                        </h3>
                                        <x-filament::badge size="sm" :color="$template->category->color()">
                                            {{ $template->category->label() }}
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </div>

                            @if($template->description)
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $template->description }}
                                </p>
                            @endif

                            <div class="flex items-center justify-between pt-2">
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $this->getFieldCount($template) }} fields
                                </span>
                                <x-filament::button
                                    size="sm"
                                    wire:click="useTemplate('{{ $template->id }}')"
                                >
                                    Use Template
                                </x-filament::button>
                            </div>
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
