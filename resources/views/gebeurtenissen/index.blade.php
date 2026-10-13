@extends('layouts.app')

@section('title', $categorie?->label() ?? 'Gebeurtenissen')

@section('content')
    <section class="mb-8 flex flex-col justify-between gap-5 border-b border-line pb-6 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-accent">{{ $categorie ? 'Onderwerp' : 'Nieuws, naast elkaar' }}</p>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $categorie?->label() ?? 'Alle gebeurtenissen' }}</h1>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">Bekijk hoe verschillende nieuwsbronnen dezelfde gebeurtenis behandelen.</p>
        </div>
        <p class="text-sm text-muted"><span class="font-semibold text-ink">{{ $aantalVerhalen }}</span> verhalen</p>
    </section>

    @if ($trending->isNotEmpty())
        <section aria-labelledby="trending-titel" class="mb-10">
            <div class="mb-4">
                <h2 id="trending-titel" class="text-xl font-semibold">Trending in {{ $categorie->label() }}</h2>
                <p class="mt-1 text-xs text-muted">Meeste artikelen in de afgelopen 48 uur</p>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($trending as $gebeurtenis)
                    @include('gebeurtenissen.partials.tegel', ['klein' => true])
                @endforeach
            </div>
        </section>

        <h2 class="mb-4 text-xl font-semibold">Meer over {{ $categorie->label() }}</h2>
    @endif

    <form method="GET" action="{{ $lijstUrl }}" class="mb-6 grid gap-3 rounded-lg border border-line bg-surface p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(10rem,0.65fr)_minmax(10rem,0.65fr)_auto_auto]">
        <div>
            <label for="gebeurtenis-zoekterm" class="mb-1 block text-xs font-semibold text-muted">Zoek onderwerp of artikel</label>
            <input id="gebeurtenis-zoekterm" name="q" type="search" value="{{ $zoekterm }}" class="w-full rounded-md border border-line bg-paper px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">
        </div>
        <div>
            <label for="samenvatting-filter" class="mb-1 block text-xs font-semibold text-muted">Samenvatting</label>
            <select id="samenvatting-filter" name="filter" class="w-full rounded-md border border-line bg-paper px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">
                <option value="alle" @selected($filter === 'alle')>Alle</option>
                <option value="met" @selected($filter === 'met')>Met samenvatting</option>
                <option value="zonder" @selected($filter === 'zonder')>Zonder samenvatting</option>
            </select>
        </div>
        <div>
            <label for="perspectief-filter" class="mb-1 block text-xs font-semibold text-muted">Perspectief</label>
            <select id="perspectief-filter" name="perspectief" class="w-full rounded-md border border-line bg-paper px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">
                <option value="alle" @selected($perspectief === 'alle')>Alle perspectieven</option>
                <option value="links" @selected($perspectief === 'links')>Links</option>
                <option value="midden" @selected($perspectief === 'midden')>Midden</option>
                <option value="rechts" @selected($perspectief === 'rechts')>Rechts</option>
            </select>
        </div>
        <button type="submit" class="self-end rounded-md bg-ink px-4 py-2 text-sm font-semibold text-paper hover:bg-ink/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Toepassen</button>
        <a href="{{ $lijstUrl }}" class="self-end px-2 py-2 text-center text-sm text-muted underline decoration-line underline-offset-4 hover:text-ink">Wissen</a>
    </form>

    <div class="space-y-4">
        @forelse ($gebeurtenissen as $gebeurtenis)
            <a href="{{ route('gebeurtenissen.show', $gebeurtenis) }}" class="group block overflow-hidden rounded-lg border border-line bg-surface shadow-sm transition hover:border-ink/30 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                <article class="{{ $gebeurtenis->artikelen->isNotEmpty() ? 'md:grid md:grid-cols-[minmax(0,17rem)_minmax(0,1fr)]' : '' }}">
                    @if ($gebeurtenis->artikelen->isNotEmpty())
                        <div class="relative aspect-[16/9] overflow-hidden bg-paper md:aspect-auto md:min-h-full">
                            @include('gebeurtenissen.partials.afbeelding')
                        </div>
                    @endif
                    <div class="p-5 md:p-6">
                        <div class="mb-3">
                            @include('gebeurtenissen.partials.meta')
                        </div>
                        <h2 class="text-xl font-semibold leading-snug group-hover:text-accent">{{ $gebeurtenis->onderwerp }}</h2>
                        <div class="mt-5">
                            @include('gebeurtenissen.partials.verdeling')
                        </div>
                    </div>
                </article>
            </a>
        @empty
            <p class="rounded-lg border border-line bg-surface px-5 py-8 text-center text-sm text-muted">Geen gebeurtenissen gevonden.</p>
        @endforelse
    </div>

    {{ $gebeurtenissen->links() }}
@endsection
