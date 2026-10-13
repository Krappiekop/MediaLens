@extends('layouts.app')

@section('title', 'Alle artikelen')

@section('content')
    <section class="mb-8 flex flex-col justify-between gap-5 border-b border-line pb-6 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-accent">Nieuwsbronnen</p>
            <h1 class="text-3xl font-semibold tracking-tight">Alle artikelen</h1>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">Bekijk ook artikelen die nog niet aan een gebeurtenis zijn gekoppeld.</p>
        </div>
        <p class="text-sm text-muted"><span class="font-semibold text-ink">{{ $artikelen->total() }}</span> artikelen</p>
    </section>

    <form method="GET" action="{{ route('artikelen.index') }}" class="mb-6 grid gap-3 rounded-lg border border-line bg-surface p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(10rem,0.65fr)_minmax(10rem,0.65fr)_auto_auto]">
        <div>
            <label for="artikel-zoekterm" class="mb-1 block text-xs font-semibold text-muted">Zoek titel, tekst of bron</label>
            <input id="artikel-zoekterm" name="q" type="search" value="{{ $zoekterm }}" class="w-full rounded-md border border-line bg-paper px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">
        </div>
        <div>
            <label for="artikel-status" class="mb-1 block text-xs font-semibold text-muted">Gebeurtenis</label>
            <select id="artikel-status" name="status" class="w-full rounded-md border border-line bg-paper px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">
                <option value="alle" @selected($status === 'alle')>Alle artikelen</option>
                <option value="los" @selected($status === 'los')>Zonder gebeurtenis</option>
                <option value="gekoppeld" @selected($status === 'gekoppeld')>Met gebeurtenis</option>
            </select>
        </div>
        <div>
            <label for="artikel-perspectief" class="mb-1 block text-xs font-semibold text-muted">Perspectief</label>
            <select id="artikel-perspectief" name="perspectief" class="w-full rounded-md border border-line bg-paper px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">
                <option value="alle" @selected($perspectief === 'alle')>Alle perspectieven</option>
                <option value="links" @selected($perspectief === 'links')>Links</option>
                <option value="midden" @selected($perspectief === 'midden')>Midden</option>
                <option value="rechts" @selected($perspectief === 'rechts')>Rechts</option>
            </select>
        </div>
        <button type="submit" class="self-end rounded-md bg-ink px-4 py-2 text-sm font-semibold text-paper hover:bg-ink/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Toepassen</button>
        <a href="{{ route('artikelen.index') }}" class="self-end px-2 py-2 text-center text-sm text-muted underline decoration-line underline-offset-4 hover:text-ink">Wissen</a>
    </form>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($artikelen as $artikel)
            <article class="overflow-hidden rounded-lg border border-line bg-surface">
                @if ($artikel->afbeelding_url)
                    <div class="relative aspect-[16/9] overflow-hidden bg-paper">
                        <span class="absolute inset-0 flex items-center justify-center text-xs font-medium text-muted" aria-hidden="true">Foto niet beschikbaar</span>
                        <img src="{{ $artikel->afbeelding_url }}" alt="{{ $artikel->titel }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                        @if ($artikel->afbeelding_credit)
                            <span class="absolute bottom-0 right-0 bg-balk/75 px-2 py-1 text-xs text-white">Beeld: {{ $artikel->afbeelding_credit }}</span>
                        @endif
                    </div>
                @endif
                <div class="p-4">
                    <div class="mb-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                        @if ($artikel->bron)
                            <a href="{{ route('bronnen.show', $artikel->bron) }}" class="underline decoration-line underline-offset-4 hover:text-ink">{{ $artikel->bron->naam }}</a>
                        @else
                            <span>Bron onbekend</span>
                        @endif
                        <span aria-hidden="true">·</span>
                        <span>{{ $artikel->bron?->orientatie ?? 'Oriëntatie onbekend' }}</span>
                        <span aria-hidden="true">·</span>
                        @include('gebeurtenissen.partials.tijd', ['tijdstip' => $artikel->publicatiedatum])
                    </div>
                    <h2 class="text-lg font-semibold leading-snug">
                        @include('gebeurtenissen.partials.artikellink')
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-muted">{{ \Illuminate\Support\Str::limit($artikel->volledige_tekst, 220) }}</p>
                    @if ($artikel->gebeurtenis)
                        <a href="{{ route('gebeurtenissen.show', $artikel->gebeurtenis) }}" class="mt-4 inline-block text-sm text-muted underline decoration-line underline-offset-4 hover:text-ink">Gebeurtenis: {{ $artikel->gebeurtenis->onderwerp }}</a>
                    @else
                        <p class="mt-4 text-sm font-medium text-accent">Niet aan een gebeurtenis gekoppeld</p>
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-lg border border-line bg-surface px-5 py-8 text-center text-sm text-muted md:col-span-2">Geen artikelen gevonden.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $artikelen->links() }}
    </div>
@endsection