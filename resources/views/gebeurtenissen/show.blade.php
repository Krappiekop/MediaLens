@extends('layouts.app')

@section('title', $gebeurtenis->onderwerp)

@section('content')
    <p>
        <a href="{{ route('gebeurtenissen.index') }}" class="text-sm text-blue-800 underline">Terug naar het overzicht</a>
    </p>

    <h1 class="mt-4 text-2xl font-semibold">{{ $gebeurtenis->onderwerp }}</h1>

    <h2 class="mt-8 text-xl font-semibold">Artikelen</h2>
    @foreach ($artikelenPerOrientatie as $bakje => $artikelen)
        <h3 class="mt-4 text-lg font-semibold">{{ $bakje }}</h3>
        <ul class="mt-2 list-disc space-y-3 pl-5">
            @foreach ($artikelen as $artikel)
                <li>
                    <a href="{{ $artikel->url }}" class="text-blue-800 underline">{{ $artikel->titel }}</a>
                    <span class="block text-sm text-gray-600">{{ $artikel->bron->naam }}, {{ $artikel->bron->orientatie }}</span>
                </li>
            @endforeach
        </ul>
    @endforeach

    @if ($gebeurtenis->samenvatting)
        <h2 class="mt-8 text-xl font-semibold">Kernfeiten</h2>
        <p class="mt-2 leading-relaxed">{{ $gebeurtenis->samenvatting->kernfeiten }}</p>

        <h2 class="mt-8 text-xl font-semibold">Betrokkenen</h2>
        <p class="mt-2 leading-relaxed">{{ $gebeurtenis->samenvatting->betrokkenen }}</p>

        <h2 class="mt-8 text-xl font-semibold">Overeenstemming</h2>
        <p class="mt-2 leading-relaxed">{{ $gebeurtenis->samenvatting->overeenstemming }}</p>

        <h2 class="mt-8 text-xl font-semibold">Verschil</h2>
        <p class="mt-2 leading-relaxed">{{ $gebeurtenis->samenvatting->verschil }}</p>
    @else
        <p class="mt-8 text-gray-700">Deze gebeurtenis heeft nog geen samenvatting.</p>
    @endif
@endsection