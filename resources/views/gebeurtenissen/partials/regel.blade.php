<a href="{{ route('gebeurtenissen.show', $gebeurtenis) }}" class="group flex gap-4 py-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
    @if ($gebeurtenis->artikelen->isNotEmpty())
        <div class="relative aspect-[4/3] w-24 shrink-0 overflow-hidden rounded-md bg-paper">
            <img src="{{ $gebeurtenis->artikelen->first()->afbeelding_url }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
        </div>
    @endif
    <article class="flex min-w-0 flex-1 flex-col gap-2">
        @include('gebeurtenissen.partials.meta')
        <h3 class="font-semibold leading-snug group-hover:text-accent">{{ $gebeurtenis->onderwerp }}</h3>
        @include('gebeurtenissen.partials.verdeling', ['compact' => true])
    </article>
</a>
