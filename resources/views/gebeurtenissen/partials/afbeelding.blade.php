@php($afbeelding = $gebeurtenis->artikelen->first())
<span class="absolute inset-0 flex items-center justify-center text-xs font-medium text-muted" aria-hidden="true">Foto niet beschikbaar</span>
<img src="{{ $afbeelding->afbeelding_url }}" alt="{{ $afbeelding->titel }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]" onerror="this.remove()">
@if ($afbeelding->afbeelding_credit)
    <span class="absolute bottom-0 right-0 bg-ink/75 px-2 py-1 text-xs text-white">Beeld: {{ $afbeelding->afbeelding_credit }}</span>
@endif
