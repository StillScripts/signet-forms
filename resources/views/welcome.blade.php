@php
    $appName = config('app.name') === 'Laravel' ? 'Signet Forms' : config('app.name');
    $user = auth()->user();
    $dashboardUrl = null;

    if ($user) {
        $team = $user->currentTeam;
        $dashboardUrl = $team
            ? route('filament.admin.pages.dashboard', ['tenant' => $team->slug])
            : route('filament.admin.tenant.registration');
    }

    $features = [
        [
            'title' => 'Drag-and-drop builder',
            'description' => 'Compose forms across multiple pages with a visual canvas, 30+ field types, and live preview.',
        ],
        [
            'title' => 'Template library',
            'description' => 'Start from a curated template — patient intake, event registration, feedback, bug reports, and more.',
        ],
        [
            'title' => 'Submission review workflow',
            'description' => 'Manage incoming responses with status, reviewer assignment, internal notes, and full audit history.',
        ],
        [
            'title' => 'Multi-tenant teams',
            'description' => 'Every workspace is isolated with its own forms, projects, members, and role-based permissions.',
        ],
        [
            'title' => 'Share anywhere',
            'description' => 'Publish a public link, generate a QR code, or embed the form in any site via a ready-made iframe.',
        ],
        [
            'title' => 'Save and resume',
            'description' => 'Respondents can save their progress and pick up later from a secure, time-limited resume link.',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $appName }} — build, publish, and review forms for your team with a visual builder, templates, and submission workflows.">

    <title>{{ $appName }} — Form builder for teams</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite('resources/css/app.css')
</head>
<body class="min-h-full bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-semibold tracking-tight">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 5h16M4 12h16M4 19h10" />
                        </svg>
                    </span>
                    {{ $appName }}
                </a>
                <nav class="flex items-center gap-2 text-sm font-medium">
                    @auth
                        <a href="{{ $dashboardUrl }}" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Go to dashboard
                        </a>
                    @else
                        <a href="{{ route('filament.admin.auth.login') }}" class="rounded-lg px-4 py-2 text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-900">
                            Log in
                        </a>
                        <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Get started
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <section class="relative overflow-hidden">
                <div class="mx-auto w-full max-w-6xl px-6 py-20 lg:px-8 lg:py-28">
                    <div class="max-w-3xl">
                        <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                            Multi-tenant form platform
                        </span>
                        <h1 class="mt-6 text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl dark:text-white">
                            Build, publish, and review forms your team can actually trust.
                        </h1>
                        <p class="mt-6 text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">
                            {{ $appName }} is a drag-and-drop form builder with multi-page flows, templates, a public renderer, and a full review workflow — built for teams that outgrow the usual survey tools.
                        </p>
                        <div class="mt-10 flex flex-wrap items-center gap-3">
                            @auth
                                <a href="{{ $dashboardUrl }}" class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                                    Open your dashboard
                                </a>
                            @else
                                <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-6 py-3 text-base font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                                    Create your workspace
                                </a>
                                <a href="{{ route('filament.admin.auth.login') }}" class="inline-flex items-center justify-center rounded-lg border border-zinc-200 bg-white px-6 py-3 text-base font-medium text-zinc-900 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white dark:hover:border-zinc-700 dark:hover:bg-zinc-800">
                                    Log in
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/40">
                <div class="mx-auto w-full max-w-6xl px-6 py-20 lg:px-8">
                    <div class="max-w-2xl">
                        <h2 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                            Everything you need to run forms at scale
                        </h2>
                        <p class="mt-4 text-base text-zinc-600 dark:text-zinc-400">
                            From first draft to final review — every step lives in one workspace with proper permissions, versioning, and audit trails.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($features as $feature)
                            <div class="rounded-xl border border-zinc-200 bg-white p-6 transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    {{ $feature['title'] }}
                                </h3>
                                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                    {{ $feature['description'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            @guest
                <section class="border-t border-zinc-200 dark:border-zinc-800">
                    <div class="mx-auto w-full max-w-6xl px-6 py-20 lg:px-8">
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-900 px-8 py-14 text-white sm:px-14 dark:border-zinc-800">
                            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                                <div class="max-w-xl">
                                    <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                                        Ready to build your first form?
                                    </h2>
                                    <p class="mt-3 text-base text-zinc-300">
                                        Create a workspace in seconds. Invite your team, pick a template, and publish — no credit card required.
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-base font-medium text-zinc-900 transition hover:bg-zinc-200">
                                        Get started
                                    </a>
                                    <a href="{{ route('filament.admin.auth.login') }}" class="inline-flex items-center justify-center rounded-lg border border-zinc-700 px-6 py-3 text-base font-medium text-white transition hover:border-zinc-500 hover:bg-zinc-800">
                                        Log in
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @endguest
        </main>

        <footer class="border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-6 py-8 text-sm text-zinc-500 sm:flex-row sm:items-center sm:justify-between lg:px-8 dark:text-zinc-500">
                <p>&copy; {{ date('Y') }} {{ $appName }}</p>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ $dashboardUrl }}" class="hover:text-zinc-900 dark:hover:text-zinc-200">Dashboard</a>
                    @else
                        <a href="{{ route('filament.admin.auth.login') }}" class="hover:text-zinc-900 dark:hover:text-zinc-200">Log in</a>
                        <a href="{{ route('filament.admin.auth.register') }}" class="hover:text-zinc-900 dark:hover:text-zinc-200">Register</a>
                    @endauth
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
