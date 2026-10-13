<a href="{{ route('gebeurtenissen.show', $gebeurtenis) }}" class="group flex h-full flex-col overflow-hidden rounded-lg border border-line bg-white shadow-sm transition hover:border-ink/30 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
    @if ($gebeurtenis->artikelen->isNotEmpty())
        <div class="relative aspect-[16/9] overflow-hidden bg-paper">
            @include('gebeurtenissen.partials.afbeelding')
        </div>
    @endif
    <article class="flex flex-1 flex-col gap-3 p-5 md:p-6">
        @include('gebeurtenissen.partials.meta')
        <h3 class="{{ ($klein ?? false) ? 'text-lg' : 'text-2xl' }} font-semibold leading-snug group-hover:text-accent">{{ $gebeurtenis->onderwerp }}</h3>
        <div class="mt-auto pt-2">
            @include('gebeurtenissen.partials.verdeling')
        </div>
    </article>
</a>
