@php($totaal = max(1, $gebeurtenis->artikelen_count))
<div class="{{ ($compact ?? false) ? 'w-32' : 'w-fit max-w-full' }}">
    <div class="flex h-2 w-full overflow-hidden rounded-full bg-paper" role="img" aria-label="Verdeling van artikelen: {{ $gebeurtenis->links_count }} links, {{ $gebeurtenis->midden_count }} midden, {{ $gebeurtenis->rechts_count }} rechts">
        <span class="bg-left" style="width: {{ ($gebeurtenis->links_count / $totaal) * 100 }}%"></span>
        <span class="bg-neutral-spectrum" style="width: {{ ($gebeurtenis->midden_count / $totaal) * 100 }}%"></span>
        <span class="bg-right" style="width: {{ ($gebeurtenis->rechts_count / $totaal) * 100 }}%"></span>
    </div>
    @if (! ($compact ?? false))
    <div class="mt-3 flex w-fit max-w-full flex-wrap gap-x-5 gap-y-2 text-xs text-muted">
        <span class="inline-flex items-center gap-2 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-left"></span>Links {{ $gebeurtenis->links_count }}</span>
        <span class="inline-flex items-center gap-2 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-neutral-spectrum"></span>Midden {{ $gebeurtenis->midden_count }}</span>
        <span class="inline-flex items-center gap-2 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-right"></span>Rechts {{ $gebeurtenis->rechts_count }}</span>
    </div>
    @endif
</div>
