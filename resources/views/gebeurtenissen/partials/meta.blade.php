<div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
    @if ($gebeurtenis->categorie)
        <span class="font-semibold uppercase tracking-wider text-accent">{{ $gebeurtenis->categorie->label() }}</span>
        <span aria-hidden="true">·</span>
    @endif
    <time datetime="{{ $gebeurtenis->artikelen_max_publicatiedatum }}">{{ date('d-m-Y', strtotime($gebeurtenis->artikelen_max_publicatiedatum)) }}</time>
    <span aria-hidden="true">·</span>
    <span>{{ $gebeurtenis->artikelen_count }} artikelen</span>
</div>
