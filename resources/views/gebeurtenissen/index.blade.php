@extends('layouts.app')

@section('title', 'Gebeurtenissen')

@section('content')
    <h1 class="text-2xl font-semibold">Gebeurtenissen</h1>
    <ul class="mt-4 list-disc space-y-3 pl-5">
        @foreach ($gebeurtenissen as $gebeurtenis)
            <li>
                <a href="{{ route('gebeurtenissen.show', $gebeurtenis) }}" class="text-blue-800 underline">
                    {{ $gebeurtenis->onderwerp }}
                </a>
                
                <span class="block text-sm text-gray-600">
                    Links {{ $gebeurtenis->links_count }},
                    Midden {{ $gebeurtenis->midden_count }},
                    Rechts {{ $gebeurtenis->rechts_count }}
                </span>
                @if ($gebeurtenis->samenvatting_exists)
                    wel samenvatting
                @else
                    geen samenvatting
                @endif
            </li>
        @endforeach
    </ul>
@endsection