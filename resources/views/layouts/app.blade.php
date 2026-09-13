<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ dark: localStorage.getItem('dark') === 'true' }" x-init="$watch('dark', value => localStorage.setItem('dark', value))" :class="{ dark: dark }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name', 'Task Tracker') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] antialiased">
        <div class="flex min-h-screen">
            <aside class="hidden lg:flex w-64 shrink-0 flex-col border-r border-[#e3e3e0] dark:border-[#3E3E3A] p-4">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 px-2 py-3 mb-4">
                    <x-heroicon-s-check-circle class="w-7 h-7 text-[#F53003] dark:text-[#FF4433]" />
                    <span class="font-semibold text-lg">Task Tracker</span>
                </a>

                <nav class="flex flex-col gap-1">
                    <x-layout.nav-link :route="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <x-heroicon-o-home class="w-5 h-5" />
                        Dashboard
                    </x-layout.nav-link>

                    <x-layout.nav-link :route="route('projects.index')" :active="request()->routeIs('projects.*')">
                        <x-heroicon-o-rectangle-stack class="w-5 h-5" />
                        Projects
                    </x-layout.nav-link>

                    <x-layout.nav-link :route="route('tags.index')" :active="request()->routeIs('tags.*')">
                        <x-heroicon-o-tag class="w-5 h-5" />
                        Tags
                    </x-layout.nav-link>

                    <x-layout.nav-link :route="route('trash')" :active="request()->routeIs('trash')">
                        <x-heroicon-o-trash class="w-5 h-5" />
                        Trash
                    </x-layout.nav-link>
                </nav>

                <div class="mt-auto pt-4">
                    <button
                        type="button"
                        @click="dark = !dark"
                        class="flex w-full items-center gap-2 px-3 py-2 rounded-md text-sm text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#f5f5f4] dark:hover:bg-[#161615] transition-colors"
                    >
                        <x-heroicon-o-sun class="w-5 h-5" x-show="dark" x-cloak />
                        <x-heroicon-o-moon class="w-5 h-5" x-show="!dark" x-cloak />
                        <span x-text="dark ? 'Light mode' : 'Dark mode'"></span>
                    </button>
                </div>
            </aside>

            <div class="flex-1 flex flex-col min-w-0">
                <header class="lg:hidden flex items-center justify-between border-b border-[#e3e3e0] dark:border-[#3E3E3A] p-4">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 font-semibold">
                        <x-heroicon-s-check-circle class="w-6 h-6 text-[#F53003] dark:text-[#FF4433]" />
                        Task Tracker
                    </a>
                    <button type="button" @click="dark = !dark" class="p-2">
                        <x-heroicon-o-sun class="w-5 h-5" x-show="dark" x-cloak />
                        <x-heroicon-o-moon class="w-5 h-5" x-show="!dark" x-cloak />
                    </button>
                </header>

                <nav class="lg:hidden flex items-center gap-4 overflow-x-auto border-b border-[#e3e3e0] dark:border-[#3E3E3A] px-4 py-2 text-sm">
                    <a href="{{ route('dashboard') }}" wire:navigate class="{{ request()->routeIs('dashboard') ? 'text-[#F53003] dark:text-[#FF4433] font-medium' : 'text-[#706f6c] dark:text-[#A1A09A]' }}">Dashboard</a>
                    <a href="{{ route('projects.index') }}" wire:navigate class="{{ request()->routeIs('projects.*') ? 'text-[#F53003] dark:text-[#FF4433] font-medium' : 'text-[#706f6c] dark:text-[#A1A09A]' }}">Projects</a>
                    <a href="{{ route('tags.index') }}" wire:navigate class="{{ request()->routeIs('tags.*') ? 'text-[#F53003] dark:text-[#FF4433] font-medium' : 'text-[#706f6c] dark:text-[#A1A09A]' }}">Tags</a>
                    <a href="{{ route('trash') }}" wire:navigate class="{{ request()->routeIs('trash') ? 'text-[#F53003] dark:text-[#FF4433] font-medium' : 'text-[#706f6c] dark:text-[#A1A09A]' }}">Trash</a>
                </nav>

                <main class="flex-1 min-w-0 p-4 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
