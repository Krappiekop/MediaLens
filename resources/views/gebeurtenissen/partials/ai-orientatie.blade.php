@php($aiBakje = $artikel->aiBakje())
@if ($aiBakje)
    <details class="mt-2 text-xs text-muted">
        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
            <span aria-hidden="true" @class([
                'h-2 w-2 shrink-0 rounded-full',
                'bg-left' => $aiBakje === 'Links',
                'bg-neutral-spectrum' => $aiBakje === 'Midden',
                'bg-right' => $aiBakje === 'Rechts',
                'border border-muted' => $aiBakje === 'Geen lading',
            ])></span>
            <span>AI: {{ $aiBakje === 'Geen lading' ? 'geen politieke lading' : $artikel->ai_orientatie }} · zekerheid {{ $artikel->ai_zekerheid }}%</span>
        </summary>
        <p class="mt-1 leading-relaxed">{{ $artikel->ai_onderbouwing }}</p>
    </details>
@endif
