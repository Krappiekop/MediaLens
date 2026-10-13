<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MediaLens')</title>

    {{-- vóór de CSS, zodat een donkere pagina niet eerst licht flitst: eigen keus uit localStorage, anders het systeem --}}
    <script>
        (() => {
            let thema = null;
            try {
                thema = localStorage.getItem('thema');
            } catch {}
            const donker = thema === 'donker' || (thema === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', donker);
        })();
    </script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="flex min-h-screen flex-col bg-paper text-ink antialiased">
    <header class="border-b-4 border-accent bg-balk px-4 text-white">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold">
                <span class="h-9 w-1.5 rounded-sm bg-accent" aria-hidden="true"></span>
                <span class="text-2xl">MediaLens</span>
            </a>
            <nav aria-label="Hoofdnavigatie" class="flex items-center gap-4 text-sm">
                <a href="{{ route('artikelen.index') }}" class="text-white/80 underline decoration-white/30 underline-offset-4 hover:text-white">Artikelen</a>
                <a href="{{ route('bronnen.index') }}" class="text-white/80 underline decoration-white/30 underline-offset-4 hover:text-white">Bronnen</a>
                <a href="{{ route('over') }}" class="text-white/80 underline decoration-white/30 underline-offset-4 hover:text-white">Over MediaLens</a>
                <button type="button" hidden data-thema-knop aria-pressed="false" aria-label="Donkere modus aanzetten"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-white/30 text-white/80 hover:border-white hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                    {{-- maan in lichte modus, zon in donkere modus --}}
                    <svg class="h-4 w-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                    <svg class="hidden h-4 w-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                </button>
            </nav>
        </div>
    </header>

    @php
        $huidigeCategorie = request()->route('categorie');
        $huidigeCategorie = $huidigeCategorie instanceof \App\Categorie ? $huidigeCategorie->value : $huidigeCategorie;
        $menu = [['label' => 'Home', 'url' => route('home'), 'actief' => request()->routeIs('home')]];
        foreach (\App\Categorie::menu() as $menuCategorie) {
            $menu[] = [
                'label' => $menuCategorie->label(),
                'url' => route('gebeurtenissen.categorie', $menuCategorie),
                'actief' => request()->routeIs('gebeurtenissen.categorie') && $huidigeCategorie === $menuCategorie->value,
            ];
        }
        $menu[] = ['label' => 'Alles', 'url' => route('gebeurtenissen.index'), 'actief' => request()->routeIs('gebeurtenissen.index')];
    @endphp
    <nav aria-label="Onderwerpen" class="border-b border-line bg-surface px-4">
        <ul class="mx-auto flex w-full max-w-6xl gap-1 overflow-x-auto text-sm font-medium">
            @foreach ($menu as $item)
                <li class="shrink-0">
                    <a href="{{ $item['url'] }}" @if ($item['actief']) aria-current="page" @endif class="block border-b-2 px-3 py-3 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-accent {{ $item['actief'] ? 'border-accent text-ink' : 'border-transparent text-muted hover:border-line hover:text-ink' }}">{{ $item['label'] }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 md:px-6 md:py-10">
        @yield('content')
    </main>

    <footer class="border-t border-line bg-surface px-4 py-5 text-sm text-muted">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between">
            <span>MediaLens</span>
            <a href="{{ route('over') }}" class="underline decoration-line underline-offset-4 hover:text-ink">Werkwijze en bronnen</a>
        </div>
    </footer>
</body>

</html>