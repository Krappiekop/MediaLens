<article class="min-w-0 p-3">
    <div class="flex items-center gap-3">
        <span class="relative flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-line bg-paper text-xs font-semibold text-ink">
            <span aria-hidden="true">{{ strtoupper(substr($artikel->bron?->naam ?? '??', 0, 2)) }}</span>
            @if ($artikel->bron_logo_url)
                <img src="{{ $artikel->bron_logo_url }}" alt="" aria-hidden="true" referrerpolicy="no-referrer" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain p-1" onerror="this.remove()">
            @endif
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold">{{ $artikel->bron?->naam ?? 'Bron onbekend' }}</p>
            <p class="truncate text-xs text-muted">{{ $artikel->bron?->orientatie ?? 'Oriëntatie onbekend' }}</p>
        </div>
        <time datetime="{{ $artikel->publicatiedatum }}" class="shrink-0 text-xs text-muted">{{ date('d-m-Y', strtotime($artikel->publicatiedatum)) }}</time>
    </div>
    <h4 class="mt-3 font-semibold leading-snug">
        <a href="{{ $artikel->url ?? '#' }}" class="underline decoration-line underline-offset-4 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ $artikel->titel }}</a>
    </h4>
</article>