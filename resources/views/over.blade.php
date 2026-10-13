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
        Elke bron heeft één politieke oriëntatie: left, left-center, neutral, right-center of right.
        Dat is een generalisatie van de hele site, niet een oordeel over dit ene artikel.
        Een stuk van The New York Times krijgt left-center omdat de bron dat label heeft,
        niet omdat een model de tekst heeft geclassificeerd.
    </p>
    <p class="mt-2 leading-relaxed">
        MediaLens volgt 124 bronnen uit de VS, het VK, Europa, het Midden-Oosten, Azië, Oceanië,
        Amerika en Afrika. De labels van 115 bronnen komen uit een dataset van Media Bias Fact Check
        (verzameld door onderzoeksinstituut Idiap). Die dataset is een momentopname; Media Bias Fact Check
        past labels soms later aan. Negen bronnen staan er niet in; hun label is een inschatting op basis van
        AllSides en Media Bias Fact Check. Staatsmedia zoals RT en Tehran Times passen slecht
        op een links-rechts-as; hun label is een noodoplossing.
    </p>
    <p class="mt-2 leading-relaxed">
        Dezelfde dataset geeft per bron ook een oordeel over feitelijke berichtgeving (high, mixed of low).
        Op de <a href="{{ route('bronnen.index') }}" class="underline decoration-line underline-offset-4 hover:text-ink">bronpagina's</a>
        staat dat als betrouwbaarheid Hoog, Gemengd of Laag. Ook dat geldt voor de hele bron, niet per artikel.
    </p>
    <p class="mt-2 leading-relaxed">
        De bakjes Links, Midden en Rechts op de detailpagina zijn alleen weergave.
        Left en left-center vallen onder Links, neutral onder Midden, right-center en right onder Rechts.
        In de database blijft de oorspronkelijke label staan. Die zie je onder de titel, bij de bronnaam.
    </p>

    <h2 class="mt-8 text-xl font-semibold">Groepen zijn gelijkenis, geen waarheid</h2>
    <p class="mt-2 leading-relaxed">
        Artikelen komen in één gebeurtenis als hun tekst genoeg op elkaar lijkt en ze binnen 48 uur van elkaar verschenen.
        Daarvoor zet een klein taalmodel (nomic-embed-text, lokaal via Ollama) de titel en het begin van
        de RSS-omschrijving om in een reeks getallen die de betekenis weergeeft. Een nieuw artikel komt bij
        de gebeurtenis van het artikel dat er het meest op lijkt, als die gelijkenis hoog genoeg is.
        Zo kunnen ook artikelen samenkomen die andere woorden gebruiken voor hetzelfde nieuws.
    </p>
    <p class="mt-2 leading-relaxed">
        Is dat model niet beschikbaar, dan valt MediaLens terug op gedeelde trefwoorden in de eerste 40 woorden
        van de omschrijving. Beide methodes zijn geen bewijs dat artikelen hetzelfde feit beschrijven,
        en al helemaal geen oordeel over wat waar is. Twee verschillende verhalen over hetzelfde onderwerp
        kunnen in één groep belanden. Nieuws dat langer dan 48 uur doorloopt, valt uiteen in meerdere gebeurtenissen;
        onder een gebeurtenis staan daarom gerelateerde gebeurtenissen die er inhoudelijk op lijken.
    </p>
    <p class="mt-2 leading-relaxed">
        Het blok Blindspot op de homepage toont gebeurtenissen van de afgelopen week waarvan minstens 80% van de
        linkse en rechtse artikelen van één kant komt. MediaLens volgt meer linkse dan rechtse bronnen, dus
        "vooral links" komt vaker voor. Een blindspot zegt dus iets over de bronnen die MediaLens volgt,
        niet over alle media.
    </p>

    <h2 class="mt-8 text-xl font-semibold">De samenvatting is geen neutrale scheidsrechter</h2>
    <p class="mt-2 leading-relaxed">
        De tekst onder Kernfeiten, Betrokkenen, Overeenstemming en Verschil komt van een taalmodel
        (DeepSeek, via OpenRouter en de Educom-proxy). Dat model is een keus, geen neutrale arbiter.
        Het kan zelf vooringenomen zijn. Het krijgt de oriëntatielabels niet per artikel te zien,
        zodat het die labels niet nabauwt. Overeenstemming en verschil gaan over schrijfwijze,
        niet over welke bron “gelijk” heeft.
    </p>
    <p class="mt-2 leading-relaxed">
        Hetzelfde model kiest ook het onderwerp van een gebeurtenis (Wereld, Politiek, Economie,
        Tech &amp; Wetenschap, Sport of Overig). Die indeling bepaalt waar de gebeurtenis in het menu staat,
        en kan net zo goed fout zijn.
    </p>
    <p class="mt-2 leading-relaxed">
        De kolom volledige tekst is de RSS-omschrijving, meestal geen compleet artikel.
        Wat het model niet in die korte tekst ziet, kan het ook niet samenvatten.
    </p>
@endsection