@php
    $record = $getRecord();
    $pngUrl = route('forms.qr-code', ['form' => $record->id, 'format' => 'png']);
    $svgUrl = route('forms.qr-code', ['form' => $record->id, 'format' => 'svg']);
@endphp

<div class="flex flex-col items-center gap-4">
    <div class="rounded-lg bg-white p-3">
        {!! $getState() !!}
    </div>

    <div class="flex gap-2">
        <x-filament::link
            :href="$pngUrl"
            tag="a"
            icon="heroicon-o-arrow-down-tray"
            size="sm"
        >
            Download PNG
        </x-filament::link>
        <x-filament::link
            :href="$svgUrl"
            tag="a"
            icon="heroicon-o-arrow-down-tray"
            size="sm"
        >
            Download SVG
        </x-filament::link>
    </div>
</div>
