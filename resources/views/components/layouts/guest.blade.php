<!DOCTYPE html>
<html lang="bg" class="dark">
    <head>
        @include('partials.head')
        <title>{{ $title ?? 'LawDiff - Инструмент за анализ на законопроекти' }}</title>
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <div class="flex min-h-screen flex-col">
            <!-- Top Navigation Bar -->
            <nav class="border-b border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-800">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                    <!-- Logo and App Name -->
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <x-app-logo class="h-8 w-8" />
                        <span class="text-xl font-semibold text-zinc-900 dark:text-white">
                            LawDiff
                        </span>
                    </a>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="flex flex-1 items-center justify-center px-6 py-12">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="border-t border-neutral-200 bg-white py-6 dark:border-neutral-700 dark:bg-zinc-800">
                <div class="mx-auto max-w-7xl px-6 text-center">
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        &copy; {{ date('Y') }} LawDiff. Всички права запазени.
                    </flux:text>
                </div>
            </footer>
        </div>

        @fluxScripts
    </body>
</html>
