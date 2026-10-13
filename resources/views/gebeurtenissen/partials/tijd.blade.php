{{-- Binnen 24 uur relatief ("2 uur geleden"), daarna de datum. De precieze tijd staat in de tooltip. --}}
@if ($tijdstip)
    @php($moment = \Illuminate\Support\Carbon::parse($tijdstip))
    <time datetime="{{ $moment->toIso8601String() }}" title="{{ $moment->format('d-m-Y H:i') }}" @isset($class) class="{{ $class }}" @endisset>{{ $moment->greaterThan(now()->subDay()) ? $moment->min(now())->locale('nl')->diffForHumans() : $moment->format('d-m-Y') }}</time>
@endif
