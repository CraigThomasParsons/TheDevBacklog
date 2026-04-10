<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TheDevBacklog') }} - @yield('title', 'Sprints')</title>
    <link rel="icon" href="/favicons/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="manifest" href="/favicons/site.webmanifest">
    <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#16a34a">
    <meta name="msapplication-TileColor" content="#16a34a">
    <meta name="msapplication-config" content="/favicons/browserconfig.xml">
    <meta name="theme-color" content="#16a34a">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|inter:400,500,600|outfit:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet" />

    <!-- Theme Engine (must load before body renders to avoid flash) -->
    <link rel="stylesheet" href="/css/theme-overrides.css">
    <script src="/js/theme-engine.js"></script>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Sortable.js for drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://js.pusher.com/8.3.0/pusher.min.js"></script>
    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        .sortable-ghost { opacity: 0.5; background: color-mix(in srgb, var(--theme-primary) 20%, var(--theme-surface)); }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen">

        <!-- ═══════════ Top Utility Bar ═══════════ -->
        <div class="tdb-topbar" x-data="{
                darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
            }"
            x-init="$watch('darkMode', val => {
                localStorage.setItem('theme', val ? 'dark' : 'light');
                document.documentElement.classList.toggle('dark', val);
            });
            document.documentElement.classList.toggle('dark', darkMode);"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-9 text-xs">
                <!-- Left: App branding -->
                <div class="flex items-center gap-3">
                    <span class="font-semibold tracking-wide opacity-70">⚡ TheDevBacklog</span>
                    <span class="opacity-40">|</span>
                    <span class="opacity-50">{{ config('app.env', 'local') }}</span>
                </div>

                <!-- Right: Theme controls -->
                <div class="flex items-center gap-2">
                    <a href="{{ route('theme.customizer') }}" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium hover:bg-white/10 transition-colors" title="Theme Customizer">
                        🎨 <span class="hidden sm:inline">Themes</span>
                    </a>

                    <span class="opacity-30">|</span>

                    <!-- Dark mode toggle -->
                    <button @click="darkMode = !darkMode" type="button" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium hover:bg-white/10 transition-colors" title="Toggle dark mode">
                        <span x-show="darkMode">🌙</span>
                        <span x-show="!darkMode">☀️</span>
                        <span class="hidden sm:inline" x-text="darkMode ? 'Dark' : 'Light'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════ Main Navigation ═══════════ -->
        <nav class="tdb-nav border-b" style="border-color: var(--theme-nav-border);">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <a href="/" class="tdb-brand text-xl font-bold">
                                🛠️ TheDevBacklog
                            </a>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ route('projects.index') }}" 
                               class="tdb-nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out
                                      {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                                Projects
                            </a>
                            <a href="{{ route('epic-drafts.index') }}" 
                               class="tdb-nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out
                                      {{ request()->routeIs('epic-drafts.*') ? 'active' : '' }}">
                                Epic Drafts
                            </a>
                            <a href="{{ route('sprints.index') }}" 
                               class="tdb-nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out
                                      {{ request()->routeIs('sprints.*') && ! request()->routeIs('sprints.current') ? 'active' : '' }}">
                                Sprints
                            </a>
                            <a href="{{ route('sprints.current') }}"
                               class="tdb-nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out
                                      {{ request()->routeIs('sprints.current') ? 'active' : '' }}">
                                Current Sprint
                            </a>
                            <a href="{{ route('backlog.index') }}" 
                               class="tdb-nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out
                                      {{ request()->routeIs('backlog.*') ? 'active' : '' }}">
                                Backlog
                            </a>
                            <a href="{{ route('mason.state') }}"
                               class="tdb-nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out
                                      {{ request()->routeIs('mason.state') ? 'active' : '' }}">
                                Mason State
                            </a>
                            <a href="{{ route('mason.chat') }}"
                               class="tdb-nav-link inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out
                                      {{ request()->routeIs('mason.chat') ? 'active' : '' }}">
                                Mason Chat
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Header -->
        @hasSection('header')
        <header class="tdb-page-header shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                @yield('header')
            </div>
        </header>
        @endif

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="tdb-flash-success mb-4 px-4 py-3 rounded relative" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
