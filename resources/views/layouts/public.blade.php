<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @filamentStyles
    @vite('resources/css/filament/admin/theme.css')
</head>
<body class="min-h-screen bg-gray-50 antialiased dark:bg-gray-950">
    {{ $slot }}

    @filamentScripts
</body>
</html>
