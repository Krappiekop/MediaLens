@extends('layouts.app')

@section('title', $gebeurtenis->onderwerp)

@section('content')
    @if ($gebeurtenis->categorie)
        <a href="{{ route('gebeurtenissen.categorie', $gebeurtenis->categorie) }}" class="inline-flex items-center gap-2 text-sm font-medium text-muted underline decoration-line underline-offset-4 hover:text-ink">&larr; Terug naar {{ $gebeurtenis->categorie->label() }}</a>
    @else
        <a href="{{ route('gebeurtenissen.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-muted underline decoration-line underline-offset-4 hover:text-ink">&larr; Terug naar het overzicht</a>
    @endif

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
        @php
            $aantalPreview ??= 3;
            $aantalInKolommen = collect(['Links', 'Midden', 'Rechts'])->sum(fn (string $bakje): int => $artikelenPerOrientatie->get($bakje, collect())->count());
            $extraBlokken = collect(['Links', 'Midden', 'Rechts'])
                ->filter(fn (string $bakje): bool => $artikelenPerOrientatie->get($bakje, collect())->count() > $aantalPreview)
                ->map(fn (string $bakje): string => 'meer-'.strtolower($bakje));
        @endphp
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            @foreach (['Links', 'Midden', 'Rechts'] as $bakje)
                @php($artikelen = $artikelenPerOrientatie->get($bakje, collect()))
                <section class="min-w-0 overflow-hidden rounded-lg border border-line bg-surface">
                    <header class="border-b border-line px-4 py-3">
                        <h3 class="font-semibold">{{ $bakje }}</h3>
                        <p class="mt-1 text-xs text-muted">{{ $artikelen->count() }} {{ $artikelen->count() === 1 ? 'artikel' : 'artikelen' }}</p>
                    </header>
                    @if ($artikelen->isEmpty())
                        <p class="px-4 py-5 text-sm text-muted">Geen artikelen in dit perspectief.</p>
                    @else
                        <div class="divide-y divide-line">
                            @foreach ($artikelen->take($aantalPreview) as $artikel)
                                @include('gebeurtenissen.partials.artikel', ['artikel' => $artikel])
                            @endforeach
                        </div>
                        @if ($artikelen->count() > $aantalPreview)
                            {{-- zonder JavaScript blijft dit blok zichtbaar; app.js verbergt het achter "Toon alle artikelen" --}}
                            <div id="meer-{{ strtolower($bakje) }}" data-extra-artikelen class="divide-y divide-line border-t border-line">
                                @foreach ($artikelen->skip($aantalPreview) as $artikel)
                                    @include('gebeurtenissen.partials.artikel', ['artikel' => $artikel])
                                @endforeach
                            </div>
                        @endif
                    @endif
                </section>
            @endforeach
        </div>

        @if ($extraBlokken->isNotEmpty())
            <div class="mt-4 flex justify-center">
                <button type="button" hidden data-toon-alles aria-expanded="true" aria-controls="{{ $extraBlokken->implode(' ') }}"
                    data-tekst-meer="Toon alle {{ $aantalInKolommen }} artikelen" data-tekst-minder="Toon minder artikelen"
                    class="rounded-md border border-line bg-surface px-4 py-2 text-sm font-semibold hover:border-ink/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Toon minder artikelen</button>
            </div>
        @endif

        @if (isset($onbekendeArtikelen) && $onbekendeArtikelen->isNotEmpty())
            <details class="mt-4 overflow-hidden rounded-lg border border-line bg-surface">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 font-semibold marker:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                    <span>Bekijk onbekende bronnen ({{ $onbekendeArtikelen->count() }})</span>
                    <span aria-hidden="true" class="text-accent">+</span>
                </summary>
                <div class="grid grid-cols-1 divide-y divide-line md:grid-cols-3 md:divide-x md:divide-y-0">
                    @foreach ($onbekendeArtikelen as $artikel)
                        @include('gebeurtenissen.partials.artikel', ['artikel' => $artikel])
                    @endforeach
                </div>
            </details>
        @endif
    </section>
    
    <p class="mt-5 rounded-md border-l-4 border-accent bg-surface px-4 py-3 text-sm leading-relaxed text-muted">
        Links, Midden en Rechts zijn weergavegroepen. De oriëntatielabels komen uit Media Bias Fact Check per bron, niet per artikel.
    </p>

    @if ($gebeurtenis->samenvatting)
        <section class="mt-8 rounded-lg border border-line bg-surface p-5 md:p-7">
            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-right">AI-samenvatting</p>
                <p class="text-xs text-muted">
                    Gemaakt op {{ $gebeurtenis->samenvatting->updated_at->format('d-m-Y H:i') }}@if ($gebeurtenis->samenvatting->model) met {{ $gebeurtenis->samenvatting->model }}@endif
                </p>
            </div>
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
        <p class="mt-8 rounded-lg border border-line bg-surface p-5 text-sm text-muted">Deze gebeurtenis heeft nog geen samenvatting.</p>
    @endif

    @if (isset($gerelateerd) && $gerelateerd->isNotEmpty())
        <section aria-labelledby="gerelateerd-titel" class="mt-10">
            <h2 id="gerelateerd-titel" class="text-xl font-semibold">Gerelateerde gebeurtenissen</h2>
            <p class="mt-1 text-xs text-muted">Gebeurtenissen waarvan de artikelen inhoudelijk het meest op deze lijken</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach ($gerelateerd as $gerelateerde)
                    @include('gebeurtenissen.partials.tegel', ['gebeurtenis' => $gerelateerde, 'klein' => true])
                @endforeach
            </div>
        </section>
    @endif
@endsection