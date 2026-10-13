@extends('layouts.app')

@section('title', 'MediaLens')

@section('content')
    <section class="mb-8 border-b border-line pb-6">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-accent">Nieuws, naast elkaar</p>
        <h1 class="text-3xl font-semibold tracking-tight">Vandaag in het nieuws</h1>
        <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">Bekijk hoe verschillende nieuwsbronnen dezelfde gebeurtenis behandelen.</p>
    </section>

    <section aria-labelledby="hot-titel" class="mb-12">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 id="hot-titel" class="text-xl font-semibold">Hot</h2>
                <p class="mt-1 text-xs text-muted">Meeste artikelen in de afgelopen 48 uur</p>
            </div>
            <a href="{{ route('gebeurtenissen.index') }}" class="text-sm text-muted underline decoration-line underline-offset-4 hover:text-ink">Alle gebeurtenissen</a>
        </div>

        @if ($hot->isEmpty())
            <p class="rounded-lg border border-line bg-white px-5 py-8 text-center text-sm text-muted">Geen gebeurtenissen in de afgelopen 48 uur.</p>
        @else
            <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                @include('gebeurtenissen.partials.tegel', ['gebeurtenis' => $hot->first()])
                @if ($hot->count() > 1)
                    <div class="divide-y divide-line rounded-lg border border-line bg-white px-5">
                        @foreach ($hot->skip(1) as $gebeurtenis)
                            @include('gebeurtenissen.partials.regel')
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </section>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div class="grid gap-8 md:grid-cols-2">
            @if ($perCategorie->isEmpty())
                <p class="rounded-lg border border-line bg-white px-5 py-8 text-center text-sm text-muted md:col-span-2">Nog geen gebeurtenissen ingedeeld per onderwerp.</p>
            @endif
            @foreach ($perCategorie as $waarde => $gebeurtenissen)
                @php($categorie = \App\Categorie::from($waarde))
                <section aria-labelledby="categorie-{{ $waarde }}" class="min-w-0">
                    <div class="flex items-end justify-between gap-4 border-b-2 border-ink pb-2">
                        <h2 id="categorie-{{ $waarde }}" class="text-lg font-semibold">{{ $categorie->label() }}</h2>
                        <a href="{{ route('gebeurtenissen.categorie', $categorie) }}" class="text-sm text-muted underline decoration-line underline-offset-4 hover:text-ink">Meer<span class="sr-only"> over {{ $categorie->label() }}</span></a>
                    </div>
                    <div class="divide-y divide-line">
                        @foreach ($gebeurtenissen as $gebeurtenis)
                            @include('gebeurtenissen.partials.regel')
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <aside aria-labelledby="meeste-artikelen-titel" class="h-fit rounded-lg border border-line bg-white p-5">
            <h2 id="meeste-artikelen-titel" class="text-lg font-semibold">Meeste artikelen</h2>
            <p class="mt-1 text-xs text-muted">Gebeurtenissen met de meeste berichtgeving</p>
            @if ($meesteArtikelen->isEmpty())
                <p class="mt-4 text-sm text-muted">Nog geen gebeurtenissen.</p>
            @else
                <ol class="mt-2 divide-y divide-line">
                    @foreach ($meesteArtikelen as $gebeurtenis)
                        <li class="flex gap-3">
                            <span class="pt-4 text-2xl font-semibold text-muted/60" aria-hidden="true">{{ $loop->iteration }}</span>
                            <a href="{{ route('gebeurtenissen.show', $gebeurtenis) }}" class="group flex min-w-0 flex-1 flex-col gap-2 py-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                                @include('gebeurtenissen.partials.meta')
                                <span class="font-semibold leading-snug group-hover:text-accent">{{ $gebeurtenis->onderwerp }}</span>
                                @include('gebeurtenissen.partials.verdeling', ['compact' => true])
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </aside>
    </div>
@endsection
