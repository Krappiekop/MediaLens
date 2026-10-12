<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MediaLens')</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="flex min-h-screen flex-col bg-paper text-ink antialiased">
    <header class="border-b-4 border-accent bg-ink px-4 text-white">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between py-4">
            <a href="{{ route('gebeurtenissen.index') }}" class="flex items-center gap-3 font-semibold">
                <span class="h-9 w-1.5 rounded-sm bg-accent" aria-hidden="true"></span>
                <span class="text-2xl">MediaLens</span>
            </a>
            <nav aria-label="Hoofdnavigatie" class="flex items-center gap-4 text-sm">
                <a href="{{ route('artikelen.index') }}" class="text-white/80 underline decoration-white/30 underline-offset-4 hover:text-white">Artikelen</a>
                <a href="{{ route('over') }}" class="text-white/80 underline decoration-white/30 underline-offset-4 hover:text-white">Over MediaLens</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 md:px-6 md:py-10">
        @yield('content')
    </main>

    <footer class="border-t border-line bg-white px-4 py-5 text-sm text-muted">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between">
            <span>MediaLens</span>
            <a href="{{ route('over') }}" class="underline decoration-line underline-offset-4 hover:text-ink">Werkwijze en bronnen</a>
        </div>
    </footer>
</body>

</html>