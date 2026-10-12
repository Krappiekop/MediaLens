@extends('layouts.app')

@section('title', $gebeurtenis->onderwerp)

@section('content')
    <a href="{{ route('gebeurtenissen.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-muted underline decoration-line underline-offset-4 hover:text-ink">&larr; Terug naar het overzicht</a>

    <header class="mt-6 border-b border-line pb-6">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-accent">Berichtgeving vergelijken</p>
        <h1 class="max-w-4xl text-3xl font-semibold leading-tight tracking-tight">{{ $gebeurtenis->onderwerp }}</h1>
        <p class="mt-3 text-sm text-muted">{{ $gebeurtenis->artikelen->count() }} artikelen uit verschillende bronnen</p>
    </header>

    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between gap-4">
            <h2 class="text-xl font-semibold">Artikelen per perspectief</h2>
            <a href="{{ route('over') }}" class="text-sm text-muted underline decoration-line underline-offset-4 hover:text-ink">Over de labels</a>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach ($artikelenPerOrientatie as $bakje => $artikelen)
            <section class="min-w-0 overflow-hidden rounded-lg border border-line bg-white">
                <header class="border-b border-line px-4 py-3">
                    <h3 class="font-semibold">{{ $bakje }}</h3>
                    <p class="mt-1 text-xs text-muted">{{ $artikelen->count() }} {{ $artikelen->count() === 1 ? 'artikel' : 'artikelen' }}</p>
                </header>
                @if ($artikelen->isEmpty())
                    <p class="px-4 py-5 text-sm text-muted">Geen artikelen in dit perspectief.</p>
                @else
                    <div class="divide-y divide-line">
                        @foreach ($artikelen as $artikel)
                            <article class="p-3">
                                <div class="relative mb-3 aspect-[16/9] overflow-hidden rounded-md bg-paper">
                                    <span class="absolute inset-0 flex items-center justify-center text-xs font-medium text-muted" aria-hidden="true">Geen afbeelding beschikbaar</span>
                                    @if ($artikel->afbeelding_url)
                                        <img src="{{ $artikel->afbeelding_url }}" alt="{{ $artikel->titel }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                    @endif
                                </div>
                                <h4 class="font-semibold leading-snug">
                                    <a href="{{ $artikel->url ?? '#' }}" class="underline decoration-line underline-offset-4 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ $artikel->titel }}</a>
                                </h4>
                                <p class="mt-2 text-xs leading-relaxed text-muted">
                                    @if ($artikel->bron)
                                        {{ $artikel->bron->naam }} · {{ $artikel->bron->orientatie }}
                                    @else
                                        Bron onbekend
                                    @endif
                                    · <time datetime="{{ $artikel->publicatiedatum }}">{{ date('d-m-Y', strtotime($artikel->publicatiedatum)) }}</time>
                                </p>
                                @if ($artikel->afbeelding_credit)
                                    <p class="mt-2 text-xs text-muted">Beeld: {{ $artikel->afbeelding_credit }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
        </div>
    </section>
    
    <p class="mt-5 rounded-md border-l-4 border-accent bg-white px-4 py-3 text-sm leading-relaxed text-muted">
        Links, Midden en Rechts zijn weergavegroepen. De oriëntatielabels komen uit Media Bias Fact Check per bron, niet per artikel.
    </p>

    @if ($gebeurtenis->samenvatting)
        <section class="mt-8 rounded-lg border border-line bg-white p-5 md:p-7">
            <p class="text-xs font-semibold uppercase tracking-wider text-right">AI-samenvatting</p>
            <h2 class="mt-2 text-2xl font-semibold">Kernfeiten</h2>
            <p class="mt-3 leading-relaxed text-ink">{{ $gebeurtenis->samenvatting->kernfeiten }}</p>

            <div class="mt-7 grid gap-6 border-t border-line pt-6 md:grid-cols-3">
                <section>
                    <h3 class="font-semibold">Betrokkenen</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $gebeurtenis->samenvatting->betrokkenen }}</p>
                </section>
                <section>
                    <h3 class="font-semibold">Overeenstemming</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $gebeurtenis->samenvatting->overeenstemming }}</p>
                </section>
                <section>
                    <h3 class="font-semibold">Verschil</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $gebeurtenis->samenvatting->verschil }}</p>
                </section>
            </div>
        </section>
    @else
        <p class="mt-8 rounded-lg border border-line bg-white p-5 text-sm text-muted">Deze gebeurtenis heeft nog geen samenvatting.</p>
    @endif
@endsection