<article class="min-w-0 p-3">
    <div class="flex items-center gap-3">
        <span class="relative flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-line bg-paper text-xs font-semibold text-ink">
            <span aria-hidden="true">{{ mb_strtoupper(mb_substr($artikel->bron?->naam ?? '??', 0, 2)) }}</span>
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
        @include('gebeurtenissen.partials.artikellink')
    </h4>
    @if (filled($artikel->volledige_tekst))
        <p class="mt-2 text-sm leading-relaxed text-muted">{{ \Illuminate\Support\Str::limit($artikel->volledige_tekst, 200) }}</p>
    @endif
</article>