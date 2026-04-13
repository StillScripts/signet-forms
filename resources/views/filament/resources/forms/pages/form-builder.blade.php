<x-filament-panels::page>
    {{-- Header Bar --}}
    <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-center gap-3">
            <a href="{{ $this->getBackUrl() }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4" />
                Back
            </a>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                {{ $this->getRecord()->name }}
            </h2>
            @if($this->getRecord()->is_published)
                <span class="inline-flex items-center rounded-md bg-success-50 px-2 py-1 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20">
                    Published
                </span>
            @endif
            @if($this->getCurrentVersionLabel())
                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                    {{ $this->getCurrentVersionLabel() }}
                </span>
            @endif
            @if($hasUnsavedChanges)
                <span class="inline-flex items-center rounded-md bg-warning-50 px-2 py-1 text-xs font-medium text-warning-700 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20">
                    Unsaved changes
                </span>
            @endif
            <span class="inline-flex items-center rounded-md {{ count($pages) > 1 ? 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20' : 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20' }} px-2 py-1 text-xs font-medium ring-1 ring-inset">
                {{ count($pages) > 1 ? count($pages) . ' pages' : 'Single page' }}
            </span>
        </div>
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                Columns:
                <select wire:change="updateColumns($event.target.value)" class="rounded-lg border-gray-300 bg-white py-1 pe-8 ps-3 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    @for($i = 1; $i <= 4; $i++)
                        <option value="{{ $i }}" @selected($columns === $i)>{{ $i }}</option>
                    @endfor
                </select>
            </label>
            <div class="flex items-center gap-1">
                <button type="button" wire:click="undo" @disabled($currentVersion <= 1) class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-500 disabled:opacity-50 disabled:cursor-not-allowed dark:hover:bg-gray-800">
                    <x-filament::icon icon="heroicon-m-arrow-uturn-left" class="h-4 w-4" />
                </button>
                <button type="button" wire:click="redo" @disabled($currentVersion >= $latestVersion) class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-500 disabled:opacity-50 disabled:cursor-not-allowed dark:hover:bg-gray-800">
                    <x-filament::icon icon="heroicon-m-arrow-uturn-right" class="h-4 w-4" />
                </button>
            </div>
            <button type="button" wire:click="resetForm" wire:confirm="Reset all changes to last saved state?" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                Reset
            </button>
            <x-filament::button wire:click="save" icon="heroicon-m-check" color="success">
                Save
            </x-filament::button>
        </div>
    </div>

    {{-- Main Layout --}}
    <div class="flex gap-4" style="min-height: 70vh;">
        {{-- Left Panel: Component Palette --}}
        <div class="w-56 shrink-0 overflow-y-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-4">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Components</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Click to add to form</p>
            </div>
            @foreach($this->groupedFieldTypes as $category => $types)
                <div class="px-4 pb-3">
                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ $category }}</h4>
                    <div class="space-y-1">
                        @foreach($types as $type)
                            <button
                                type="button"
                                wire:click="addField('{{ $type->value }}')"
                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left transition hover:bg-gray-50 dark:hover:bg-white/5"
                            >
                                <x-filament::icon :icon="$type->icon()" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                <div>
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $type->label() }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $type->description() }}</div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Center Panel: Canvas --}}
        <div class="min-w-0 flex-1 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{-- Tabs --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-4 dark:border-white/10">
                <div class="flex gap-1">
                    @foreach(['builder' => 'Builder', 'preview' => 'Preview'] as $tab => $label)
                        <button
                            type="button"
                            wire:click="setActiveTab('{{ $tab }}')"
                            @class([
                                'px-4 py-3 text-sm font-medium border-b-2 transition',
                                'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === $tab,
                                'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $activeTab !== $tab,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ count($fields) }} {{ Str::plural('field', count($fields)) }}</span>
            </div>

            {{-- Page Tabs --}}
            @if($activeTab === 'builder')
                <div class="flex items-center gap-1 border-b border-gray-200 px-4 py-2 dark:border-white/10">
                    @foreach($pages as $pageIndex => $page)
                        <button
                            type="button"
                            wire:click="switchPage('{{ $page['id'] }}')"
                            @class([
                                'group relative flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                'bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-950/30 dark:text-primary-400 dark:ring-primary-800' => $activePageId === $page['id'],
                                'text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200' => $activePageId !== $page['id'],
                            ])
                        >
                            {{ $this->getPageLabel($page, $pageIndex) }}
                            @if(count($pages) > 1)
                                <span class="text-xs text-gray-400 dark:text-gray-500">({{ count($page['fields'] ?? []) }})</span>
                            @endif
                        </button>
                    @endforeach
                    <button
                        type="button"
                        wire:click="addPage"
                        class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-sm text-gray-400 transition hover:bg-gray-50 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    >
                        <x-filament::icon icon="heroicon-m-plus" class="h-3.5 w-3.5" />
                        Add Page
                    </button>
                    @if(count($pages) > 1)
                        <div class="ml-auto flex items-center gap-0.5">
                            <button
                                type="button"
                                wire:click="movePage('{{ $activePageId }}', 'up')"
                                @disabled($this->getActivePageIndex() === 0)
                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 disabled:opacity-30 disabled:cursor-not-allowed dark:hover:bg-gray-800"
                                title="Move page left"
                            >
                                <x-filament::icon icon="heroicon-m-chevron-left" class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                wire:click="movePage('{{ $activePageId }}', 'down')"
                                @disabled($this->getActivePageIndex() === count($pages) - 1)
                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 disabled:opacity-30 disabled:cursor-not-allowed dark:hover:bg-gray-800"
                                title="Move page right"
                            >
                                <x-filament::icon icon="heroicon-m-chevron-right" class="h-3.5 w-3.5" />
                            </button>
                            <button
                                type="button"
                                wire:click="removePage('{{ $activePageId }}')"
                                wire:confirm="Remove this page and all its fields?"
                                class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-950/50 dark:hover:text-red-400"
                                title="Remove page"
                            >
                                <x-filament::icon icon="heroicon-m-trash" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Builder Tab --}}
            @if($activeTab === 'builder')
                <div class="p-4">
                    @if(empty($fields))
                        {{-- Empty State --}}
                        <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 py-16 dark:border-gray-700">
                            <div class="mb-4 rounded-full bg-gray-100 p-4 dark:bg-gray-800">
                                <x-filament::icon icon="heroicon-o-plus" class="h-8 w-8 text-gray-400" />
                            </div>
                            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Start building your form</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Click any field type from the panel on the left to add it here.</p>
                        </div>
                    @else
                        {{-- Field Cards --}}
                        <div
                            wire:sort="handleSort"
                            class="grid gap-3"
                            style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));"
                        >
                            @foreach($fields as $field)
                                <div
                                    wire:key="{{ $field['key'] }}"
                                    wire:sort:item="{{ $field['key'] }}"
                                    wire:click="selectField('{{ $field['key'] }}')"
                                    style="grid-column: span {{ min($field['data']['column_span'] ?? 1, $columns) }} / span {{ min($field['data']['column_span'] ?? 1, $columns) }};"
                                    @class([
                                        'group relative cursor-pointer rounded-lg border-2 p-3 transition',
                                        'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:border-primary-400 dark:bg-primary-950/20' => $selectedFieldKey === $field['key'],
                                        'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600' => $selectedFieldKey !== $field['key'],
                                    ])
                                >
                                    {{-- Drag Handle --}}
                                    <div wire:sort:handle class="absolute left-1 top-1/2 -translate-y-1/2 cursor-grab p-1 text-gray-300 opacity-0 transition group-hover:opacity-100 active:cursor-grabbing dark:text-gray-600">
                                        <x-filament::icon icon="heroicon-m-bars-2" class="h-4 w-4" />
                                    </div>

                                    {{-- Delete Button --}}
                                    <div wire:sort:ignore class="absolute right-1.5 top-1.5">
                                        <button
                                            type="button"
                                            wire:click.stop="removeField('{{ $field['key'] }}')"
                                            class="rounded p-1 text-gray-300 opacity-0 transition hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-gray-600 dark:hover:bg-red-950/50 dark:hover:text-red-400"
                                        >
                                            <x-filament::icon icon="heroicon-m-x-mark" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>

                                    {{-- Field Content --}}
                                    <div class="pl-5">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <x-filament::icon :icon="\App\Enums\FormFieldType::from($field['type'])->icon()" class="h-4 w-4 text-gray-400" />
                                                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $field['data']['label'] ?? $field['key'] }}</span>
                                            </div>
                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">{{ $field['key'] }}</span>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                            {{ \App\Enums\FormFieldType::from($field['type'])->label() }}
                                        </div>
                                        @if(!empty($field['data']['placeholder']))
                                            <div class="mt-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-400 dark:border-gray-700 dark:bg-gray-800/50">
                                                {{ $field['data']['placeholder'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Preview Tab --}}
            @if($activeTab === 'preview')
                <div class="p-6">
                    @if(empty($fields) && count($pages) <= 1)
                        <p class="text-center text-sm text-gray-500 dark:text-gray-400">Add fields to see a preview.</p>
                    @else
                        <div class="mx-auto max-w-2xl">
                            {{ $this->previewSchema }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Right Panel: Field Settings or Page Settings --}}
        @if($selectedFieldKey && $this->getSelectedField())
            <div class="w-80 shrink-0 overflow-y-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-white/10">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Field Settings</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $this->getSelectedFieldType()->label() }}</p>
                    </div>
                    <button type="button" wire:click="selectField(null)" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-800">
                        <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
                    </button>
                </div>
                <div class="p-4">
                    {{ $this->fieldSettingsSchema }}
                </div>
            </div>
        @elseif($activeTab === 'builder')
            <div class="w-80 shrink-0 overflow-y-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 p-4 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Page Settings</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $this->getPageLabel($this->getActivePage() ?? [], $this->getActivePageIndex()) }}</p>
                </div>
                <div class="p-4">
                    {{ $this->pageSettingsSchema }}
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
