<article class="min-w-0 p-3">
    <div class="flex items-center gap-3">
        <span class="relative flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-line bg-paper text-xs font-semibold text-ink">
            <span aria-hidden="true">{{ strtoupper(substr($artikel->bron?->naam ?? '??', 0, 2)) }}</span>
            @if ($artikel->bron_logo_url)
                <img src="{{ $artikel->bron_logo_url }}" alt="" aria-hidden="true" referrerpolicy="no-referrer" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain p-1" onerror="this.remove()">
            @endif
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold">
                @if ($artikel->bron)
                    <a href="{{ route('bronnen.show', $artikel->bron) }}" class="hover:text-accent hover:underline">{{ $artikel->bron->naam }}</a>
                @else
                    Bron onbekend
                @endif
            </p>
            <p class="truncate text-xs text-muted">{{ $artikel->bron?->orientatie ?? 'Oriëntatie onbekend' }}</p>
        </div>
        @include('gebeurtenissen.partials.tijd', ['tijdstip' => $artikel->publicatiedatum, 'class' => 'shrink-0 text-xs text-muted'])
    </div>
    <h4 class="mt-3 font-semibold leading-snug">
        <a href="{{ $artikel->url ?? '#' }}" target="_blank" rel="noopener noreferrer" class="underline decoration-line underline-offset-4 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ $artikel->titel }}<span class="sr-only"> (opent in een nieuw tabblad)</span></a>
    </h4>
    @if (filled($artikel->volledige_tekst))
        <p class="mt-2 text-sm leading-relaxed text-muted">{{ \Illuminate\Support\Str::limit($artikel->volledige_tekst, 200) }}</p>
    @endif
</article>