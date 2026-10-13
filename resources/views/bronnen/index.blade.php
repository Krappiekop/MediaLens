@extends('layouts.app')

@section('title', 'Bronnen')

@section('content')
    <section class="mb-8 flex flex-col justify-between gap-5 border-b border-line pb-6 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-accent">Nieuwsbronnen</p>
            <h1 class="text-3xl font-semibold tracking-tight">Bronnen</h1>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">Alle bronnen die MediaLens volgt, met hun politieke label en betrouwbaarheid volgens Media Bias Fact Check.</p>
        </div>
        <p class="text-sm text-muted"><span class="font-semibold text-ink">{{ $aantalBronnen }}</span> bronnen</p>
    </section>

    <div class="grid gap-6 md:grid-cols-3">
        @foreach ($bronnenPerBakje as $bakje => $bronnen)
            <section aria-labelledby="bakje-{{ \Illuminate\Support\Str::slug($bakje) }}" class="min-w-0 overflow-hidden rounded-lg border border-line bg-surface @if ($bakje === 'Onbekend') md:col-span-3 @endif">
                <header class="border-b border-line px-4 py-3">
                    <h2 id="bakje-{{ \Illuminate\Support\Str::slug($bakje) }}" class="font-semibold">{{ $bakje }}</h2>
                    <p class="mt-1 text-xs text-muted">{{ $bronnen->count() }} {{ $bronnen->count() === 1 ? 'bron' : 'bronnen' }}</p>
                </header>
                <ul class="divide-y divide-line">
                    @foreach ($bronnen as $bron)
                        <li>
                            <a href="{{ route('bronnen.show', $bron) }}" class="group flex items-baseline justify-between gap-3 px-4 py-3 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-accent">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold group-hover:text-accent">{{ $bron->naam }}</span>
                                    <span class="block text-xs text-muted">{{ $bron->orientatie }} · betrouwbaarheid {{ strtolower($bron->betrouwbaarheidLabel()) }}</span>
                                </span>
                                <span class="shrink-0 text-xs text-muted">{{ $bron->artikelen_count }} artikelen</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
@endsection
