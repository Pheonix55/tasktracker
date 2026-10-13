<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ dark: localStorage.getItem('dark') === 'true' }" x-init="$watch('dark', value => localStorage.setItem('dark', value))" :class="{ dark: dark }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name', 'Task Tracker') }}</title>

        <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('images/logo-icon.png') }}">

        {{-- Apply the saved theme before first paint so dark mode never flashes light. --}}
        <script>
            if (localStorage.getItem('dark') === 'true') {
                document.documentElement.classList.add('dark');
            }
        </script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased selection:bg-indigo-500/20 dark:bg-zinc-950 dark:text-zinc-100">
        <div class="relative isolate flex min-h-screen">
            <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
                <div class="absolute -top-48 left-1/3 size-[40rem] rounded-full bg-indigo-400/20 blur-3xl dark:bg-indigo-600/10"></div>
                <div class="absolute -right-32 -bottom-48 size-[34rem] rounded-full bg-fuchsia-400/15 blur-3xl dark:bg-fuchsia-600/10"></div>
                <div class="absolute top-1/2 -left-40 size-[26rem] rounded-full bg-sky-300/15 blur-3xl dark:bg-sky-600/5"></div>
            </div>

            <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col border-r border-zinc-200/70 bg-white/70 p-4 backdrop-blur-xl lg:flex dark:border-white/5 dark:bg-zinc-900/40">
                <a href="{{ route('dashboard') }}" wire:navigate class="mb-8 px-2 py-2">
                    <x-ui.logo />
                </a>

                <p class="mb-2 px-3 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500">Workspace</p>

                <nav class="flex flex-col gap-1">
                    <x-layout.nav-link :route="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <x-heroicon-o-squares-2x2 class="size-5 transition-transform duration-200 group-hover:scale-110" />
                        Dashboard
                    </x-layout.nav-link>

                    <x-layout.nav-link :route="route('projects.index')" :active="request()->routeIs('projects.*')">
                        <x-heroicon-o-rectangle-stack class="size-5 transition-transform duration-200 group-hover:scale-110" />
                        Projects
                    </x-layout.nav-link>

                    <x-layout.nav-link :route="route('tags.index')" :active="request()->routeIs('tags.*')">
                        <x-heroicon-o-tag class="size-5 transition-transform duration-200 group-hover:scale-110" />
                        Tags
                    </x-layout.nav-link>

                    <x-layout.nav-link :route="route('trash')" :active="request()->routeIs('trash')">
                        <x-heroicon-o-trash class="size-5 transition-transform duration-200 group-hover:scale-110" />
                        Trash
                    </x-layout.nav-link>

                    <x-layout.nav-link :route="route('settings')" :active="request()->routeIs('settings')">
                        <x-heroicon-o-cog-6-tooth class="size-5 transition-transform duration-200 group-hover:scale-110" />
                        Settings
                    </x-layout.nav-link>
                </nav>

                <div class="mt-auto pt-4">
                    <button
                        type="button"
                        @click="dark = !dark"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/5 dark:hover:text-zinc-100"
                    >
                        <span class="relative size-5">
                            <x-heroicon-o-sun class="absolute inset-0 size-5 transition-all duration-500" x-bind:class="dark ? 'rotate-0 scale-100 opacity-100' : '-rotate-90 scale-0 opacity-0'" />
                            <x-heroicon-o-moon class="absolute inset-0 size-5 transition-all duration-500" x-bind:class="dark ? 'rotate-90 scale-0 opacity-0' : 'rotate-0 scale-100 opacity-100'" />
                        </span>
                        <span x-text="dark ? 'Light mode' : 'Dark mode'"></span>
                    </button>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-40 border-b border-zinc-200/70 bg-white/70 backdrop-blur-xl lg:hidden dark:border-white/5 dark:bg-zinc-950/70">
                    <div class="flex items-center justify-between px-4 py-3">
                        <a href="{{ route('dashboard') }}" wire:navigate>
                            <x-ui.logo size="sm" />
                        </a>
                        <button type="button" @click="dark = !dark" class="btn-icon" aria-label="Toggle dark mode">
                            <x-heroicon-o-sun class="size-5" x-show="dark" x-cloak />
                            <x-heroicon-o-moon class="size-5" x-show="!dark" x-cloak />
                        </button>
                    </div>

                    <nav class="flex items-center gap-1 overflow-x-auto px-3 pb-3 text-sm">
                        @foreach ([
                            ['dashboard', 'dashboard', 'Dashboard'],
                            ['projects.index', 'projects.*', 'Projects'],
                            ['tags.index', 'tags.*', 'Tags'],
                            ['trash', 'trash', 'Trash'],
                            ['settings', 'settings', 'Settings'],
                        ] as [$routeName, $pattern, $label])
                            <a
                                href="{{ route($routeName) }}"
                                wire:navigate
                                @class([
                                    'shrink-0 rounded-full px-3.5 py-1.5 font-medium transition',
                                    'bg-indigo-500 text-white shadow-md shadow-indigo-500/25' => request()->routeIs($pattern),
                                    'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-white/5' => ! request()->routeIs($pattern),
                                ])
                            >{{ $label }}</a>
                        @endforeach
                    </nav>
                </header>

                <main class="mx-auto w-full max-w-7xl min-w-0 flex-1 p-4 sm:p-6 lg:p-10">
                    <div class="animate-fade-in-up">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
