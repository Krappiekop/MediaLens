@extends('layouts.app')

@section('title', 'Gebeurtenissen')

@section('content')
    <section class="mb-8 flex flex-col justify-between gap-5 border-b border-line pb-6 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-accent">Nieuws, naast elkaar</p>
            <h1 class="text-3xl font-semibold tracking-tight">Gebeurtenissen</h1>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">Bekijk hoe verschillende nieuwsbronnen dezelfde gebeurtenis behandelen.</p>
        </div>
        <p class="text-sm text-muted"><span class="font-semibold text-ink">{{ $gebeurtenissen->total() }}</span> verhalen</p>
    </section>

    <form method="GET" action="{{ route('gebeurtenissen.index') }}" class="mb-6 grid gap-3 rounded-lg border border-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(10rem,0.65fr)_minmax(10rem,0.65fr)_auto_auto]">
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
        <button type="submit" class="self-end rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-ink/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Toepassen</button>
        <a href="{{ route('gebeurtenissen.index') }}" class="self-end px-2 py-2 text-center text-sm text-muted underline decoration-line underline-offset-4 hover:text-ink">Wissen</a>
    </form>

    <div class="space-y-4">
        @forelse ($gebeurtenissen as $gebeurtenis)
            @php($totaal = max(1, $gebeurtenis->artikelen_count))
            <a href="{{ route('gebeurtenissen.show', $gebeurtenis) }}" class="group block overflow-hidden rounded-lg border border-line bg-white shadow-sm transition hover:border-ink/30 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                <article class="{{ $gebeurtenis->artikelen->isNotEmpty() ? 'md:grid md:grid-cols-[minmax(0,17rem)_minmax(0,1fr)]' : '' }}">
                    @if ($gebeurtenis->artikelen->isNotEmpty())
                        <div class="relative aspect-[16/9] overflow-hidden bg-paper md:aspect-auto md:min-h-full">
                            <span class="absolute inset-0 flex items-center justify-center text-xs font-medium text-muted" aria-hidden="true">Foto niet beschikbaar</span>
                            <img src="{{ $gebeurtenis->artikelen->first()->afbeelding_url }}" alt="{{ $gebeurtenis->artikelen->first()->titel }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]" onerror="this.remove()">
                            @if ($gebeurtenis->artikelen->first()->afbeelding_credit)
                                <span class="absolute bottom-0 right-0 bg-ink/75 px-2 py-1 text-xs text-white">Beeld: {{ $gebeurtenis->artikelen->first()->afbeelding_credit }}</span>
                            @endif
                        </div>
                    @endif
                    <div class="p-5 md:p-6">
                        <div class="mb-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                            <time datetime="{{ $gebeurtenis->artikelen_max_publicatiedatum }}">{{ date('d-m-Y', strtotime($gebeurtenis->artikelen_max_publicatiedatum)) }}</time>
                            <span aria-hidden="true">·</span>
                            <span>{{ $gebeurtenis->artikelen_count }} artikelen</span>
                        </div>
                        <h2 class="text-xl font-semibold leading-snug group-hover:text-accent">{{ $gebeurtenis->onderwerp }}</h2>
                        <div class="mt-5 w-fit max-w-full">
                            <div class="flex h-2 w-full overflow-hidden rounded-full bg-paper" role="img" aria-label="Verdeling van artikelen: {{ $gebeurtenis->links_count }} links, {{ $gebeurtenis->midden_count }} midden, {{ $gebeurtenis->rechts_count }} rechts">
                                <span class="bg-left" style="width: {{ ($gebeurtenis->links_count / $totaal) * 100 }}%"></span>
                                <span class="bg-neutral-spectrum" style="width: {{ ($gebeurtenis->midden_count / $totaal) * 100 }}%"></span>
                                <span class="bg-right" style="width: {{ ($gebeurtenis->rechts_count / $totaal) * 100 }}%"></span>
                            </div>
                            <div class="mt-3 flex w-fit max-w-full flex-wrap gap-x-5 gap-y-2 text-xs text-muted">
                                <span class="inline-flex items-center gap-2 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-left"></span>Links {{ $gebeurtenis->links_count }}</span>
                                <span class="inline-flex items-center gap-2 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-neutral-spectrum"></span>Midden {{ $gebeurtenis->midden_count }}</span>
                                <span class="inline-flex items-center gap-2 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-right"></span>Rechts {{ $gebeurtenis->rechts_count }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            </a>
        @empty
            <p class="rounded-lg border border-line bg-white px-5 py-8 text-center text-sm text-muted">Geen gebeurtenissen gevonden.</p>
        @endforelse
    </div>

    {{ $gebeurtenissen->links() }}
@endsection