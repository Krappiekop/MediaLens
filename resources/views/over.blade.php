@extends('layouts.app')

@section('title', 'Over MediaLens')

@section('content')
    <h1 class="text-2xl font-semibold">Over MediaLens</h1>

    <p class="mt-4 leading-relaxed">
        MediaLens groepeert nieuwsartikelen over dezelfde gebeurtenis en zet bronnen naast elkaar.
        Deze pagina legt uit wat de labels, de groepen en de samenvatting wel en niet zijn.
    </p>

    <h2 class="mt-8 text-xl font-semibold">Labels per bron, niet per artikel</h2>
    <p class="mt-2 leading-relaxed">
        Elke bron heeft één politieke oriëntatie uit de dataset van Media Bias Fact Check:
        left, left-center, neutral, right-center of right. Dat is een generalisatie van de hele site,
        niet een oordeel over dit ene artikel. Een stuk van The New York Times krijgt left-center
        omdat de bron dat label heeft, niet omdat een model de tekst heeft geclassificeerd.
    </p>
    <p class="mt-2 leading-relaxed">
        De bakjes Links, Midden en Rechts op de detailpagina zijn alleen weergave.
        Left en left-center vallen onder Links, neutral onder Midden, right-center en right onder Rechts.
        In de database blijft de oorspronkelijke label staan. Die zie je onder de titel, bij de bronnaam.
    </p>

    <h2 class="mt-8 text-xl font-semibold">Groepen zijn trefwoordoverlap, geen waarheid</h2>
    <p class="mt-2 leading-relaxed">
        Artikelen komen in één gebeurtenis als ze in de eerste 40 woorden van de RSS-omschrijving
        genoeg trefwoorden delen, binnen één kalenderdag. Dat is geen bewijs dat ze hetzelfde feit
        beschrijven, en al helemaal geen oordeel over wat waar is. Koppen verschillen te sterk per bron,
        daarom vergelijkt de matcher de omschrijving, niet de titel.
    </p>

    <h2 class="mt-8 text-xl font-semibold">De samenvatting is geen neutrale scheidsrechter</h2>
    <p class="mt-2 leading-relaxed">
        De tekst onder Kernfeiten, Betrokkenen, Overeenstemming en Verschil komt van een taalmodel
        (DeepSeek, via OpenRouter en de Educom-proxy). Dat model is een keus, geen neutrale arbiter.
        Het kan zelf vooringenomen zijn. Het krijgt de Media Bias Fact Check-labels niet per artikel te zien,
        zodat het die labels niet nabauwt. Overeenstemming en verschil gaan over schrijfwijze,
        niet over welke bron “gelijk” heeft.
    </p>
    <p class="mt-2 leading-relaxed">
        De kolom volledige tekst is de RSS-omschrijving, meestal geen compleet artikel.
        Wat het model niet in die korte tekst ziet, kan het ook niet samenvatten.
    </p>
@endsection