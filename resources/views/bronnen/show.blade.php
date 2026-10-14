@extends('layouts.app')

@section('title', $bron->naam)

@section('content')
    <a href="{{ route('bronnen.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-muted underline decoration-line underline-offset-4 hover:text-ink">&larr; Terug naar alle bronnen</a>

    <header class="mt-6 border-b border-line pb-6">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-accent">Bron</p>
        <h1 class="text-3xl font-semibold leading-tight tracking-tight">{{ $bron->naam }}</h1>
    </header>

    <dl class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-lg border border-line bg-surface p-4">
            <dt class="text-xs font-semibold text-muted">Politiek label (MBFC)</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $bron->bakje() }}</dd>
            <dd class="text-xs text-muted">{{ $bron->orientatie ?? 'Onbekend' }}</dd>
        </div>
        <div class="rounded-lg border border-dashed border-line bg-surface p-4">
            <dt class="text-xs font-semibold text-muted">AI-schatting</dt>
            @if ($aiSchatting)
                <dd class="mt-1 text-lg font-semibold">{{ \App\Models\Bron::bakjeVan($aiSchatting['orientatie']) }}</dd>
                <dd class="text-xs text-muted">{{ $aiSchatting['orientatie'] }} · score {{ number_format($aiSchatting['score'], 1, ',', '') }}</dd>
                <dd class="text-xs text-muted">{{ $aiSchatting['aantal'] }} {{ $aiSchatting['aantal'] === 1 ? 'politiek artikel' : 'politieke artikelen' }}, gem. zekerheid {{ $aiSchatting['gemiddelde_zekerheid'] }}%</dd>
                @if (\App\Models\Bron::bakjeVan($aiSchatting['orientatie']) !== $bron->bakje())
                    <dd class="mt-1 text-xs font-semibold text-accent">Wijkt af van MBFC</dd>
                @endif
            @else
                <dd class="mt-1 text-lg font-semibold">Nog geen</dd>
                <dd class="text-xs text-muted">geen politiek geladen artikelen geschat</dd>
            @endif
        </div>
        <div class="rounded-lg border border-line bg-surface p-4">
            <dt class="text-xs font-semibold text-muted">Betrouwbaarheid</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $bron->betrouwbaarheidLabel() }}</dd>
            <dd class="text-xs text-muted">{{ $bron->betrouwbaarheid ? 'factual reporting: '.$bron->betrouwbaarheid : 'nog niet opgezocht' }}</dd>
        </div>
        <div class="rounded-lg border border-line bg-surface p-4">
            <dt class="text-xs font-semibold text-muted">Artikelen</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $aantalArtikelen }}</dd>
        </div>
        <div class="rounded-lg border border-line bg-surface p-4">
            <dt class="text-xs font-semibold text-muted">Gebeurtenissen</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $aantalGebeurtenissen }}</dd>
            <dd class="text-xs text-muted">met minstens 2 artikelen</dd>
        </div>
    </dl>

    <p class="mt-5 rounded-md border-l-4 border-accent bg-surface px-4 py-3 text-sm leading-relaxed text-muted">
        Label en betrouwbaarheid komen uit de dataset van Media Bias Fact Check en gelden voor de hele bron, niet per artikel.
        De AI-schatting is een tweede, onafhankelijke meting: een taalmodel schat per artikel de oriëntatie,
        en hier staat het gemiddelde van de politiek geladen artikelen, gewogen naar zekerheid.
        <a href="{{ route('over') }}" class="underline decoration-line underline-offset-4 hover:text-ink">Meer over de labels</a>
    </p>

    <section aria-labelledby="laatste-artikelen-titel" class="mt-10">
        <h2 id="laatste-artikelen-titel" class="text-xl font-semibold">Laatste artikelen</h2>
        @if ($laatsteArtikelen->isEmpty())
            <p class="mt-4 rounded-lg border border-line bg-surface px-5 py-8 text-center text-sm text-muted">Nog geen artikelen van deze bron.</p>
        @else
            <ul class="mt-4 divide-y divide-line rounded-lg border border-line bg-surface">
                @foreach ($laatsteArtikelen as $artikel)
                    <li class="p-4">
                        @include('gebeurtenissen.partials.tijd', ['tijdstip' => $artikel->publicatiedatum, 'class' => 'text-xs text-muted'])
                        <h3 class="mt-1 font-semibold leading-snug">
                            @include('gebeurtenissen.partials.artikellink')
                        </h3>
                        @include('gebeurtenissen.partials.ai-orientatie')
                        @if ($artikel->gebeurtenis)
                            <a href="{{ route('gebeurtenissen.show', $artikel->gebeurtenis) }}" class="mt-2 inline-block text-sm text-muted underline decoration-line underline-offset-4 hover:text-ink">Gebeurtenis: {{ $artikel->gebeurtenis->onderwerp }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
