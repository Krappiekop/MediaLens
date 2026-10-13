{{-- Titel als link naar het originele artikel, alleen bij een http(s)-URL; anders alleen de titel. --}}
@php($artikelUrl = $artikel->veiligeUrl())
@if ($artikelUrl)
    <a href="{{ $artikelUrl }}" target="_blank" rel="noopener noreferrer" class="underline decoration-line underline-offset-4 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ $artikel->titel }}<span class="sr-only"> (opent in een nieuw tabblad)</span></a>
@else
    {{ $artikel->titel }}
@endif
