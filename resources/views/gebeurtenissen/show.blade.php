<!DOCTYPE html>
<html>

<head>
    <title>{{ $gebeurtenis->onderwerp }}</title>
</head>

<body>
    <h1>{{ $gebeurtenis->onderwerp }}</h1>

    <h2>Artikelen</h2>
    @foreach ($artikelenPerOrientatie as $bakje => $artikelen)
        <h3>{{ $bakje }}</h3>
        <ul>
            @foreach ($artikelen as $artikel)
                <li>
                    <a href="{{ $artikel->url }}">{{ $artikel->titel }}</a>
                    ({{ $artikel->bron->naam }}, {{ $artikel->bron->orientatie }})
                </li>
            @endforeach
        </ul>
    @endforeach

    @if ($gebeurtenis->samenvatting)
        <h2>Kernfeiten</h2>
        <p>{{ $gebeurtenis->samenvatting->kernfeiten }}</p>

        <h2>Betrokkenen</h2>
        <p>{{ $gebeurtenis->samenvatting->betrokkenen }}</p>

        <h2>Overeenstemming</h2>
        <p>{{ $gebeurtenis->samenvatting->overeenstemming }}</p>

        <h2>Verschil</h2>
        <p>{{ $gebeurtenis->samenvatting->verschil }}</p>
    @else
        <p>Deze gebeurtenis heeft nog geen samenvatting.</p>
    @endif
</body>

</html>