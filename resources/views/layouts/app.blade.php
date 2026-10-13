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
            <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold">
                <span class="h-9 w-1.5 rounded-sm bg-accent" aria-hidden="true"></span>
                <span class="text-2xl">MediaLens</span>
            </a>
            <nav aria-label="Hoofdnavigatie" class="flex items-center gap-4 text-sm">
                <a href="{{ route('artikelen.index') }}" class="text-white/80 underline decoration-white/30 underline-offset-4 hover:text-white">Artikelen</a>
                <a href="{{ route('over') }}" class="text-white/80 underline decoration-white/30 underline-offset-4 hover:text-white">Over MediaLens</a>
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
    <nav aria-label="Onderwerpen" class="border-b border-line bg-white px-4">
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

    <footer class="border-t border-line bg-white px-4 py-5 text-sm text-muted">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between">
            <span>MediaLens</span>
            <a href="{{ route('over') }}" class="underline decoration-line underline-offset-4 hover:text-ink">Werkwijze en bronnen</a>
        </div>
    </footer>
</body>

</html>