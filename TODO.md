# Status en to-do

Voortgang van MediaLens, per fase en per bouwblok. Bouwblokken houden hun oorspronkelijke nummer, ook als ze in een andere fase staan dan hun nummer doet vermoeden. Afgevinkt is gebouwd en getest. Alles wat nog open staat, staat samen onder [Fase 6: Volgende stappen](#fase-6-volgende-stappen).

| Fase | Bouwblokken | Status |
|---|---|---|
| [Basisopzet](#basisopzet) | | Klaar |
| [Fase 1: Data verzamelen](#fase-1-data-verzamelen) | 1, 2, 15 | Klaar |
| [Fase 2: Gebeurtenissen groeperen](#fase-2-gebeurtenissen-groeperen) | 3, 12, 16 | Klaar |
| [Fase 3: AI-analyse en samenvatting](#fase-3-ai-analyse-en-samenvatting) | 4, 5 | Klaar |
| [Fase 4: Website](#fase-4-website) | 6, 7, 8, 9, 10, 14 | Klaar |
| [Fase 5: Transparantie en kwaliteit](#fase-5-transparantie-en-kwaliteit) | 11, 13 | Klaar |
| [Fase 6: Volgende stappen](#fase-6-volgende-stappen) | 17 | Open |

## Basisopzet
- [x] Laravel geïnstalleerd en lokaal draaiend
- [x] GitHub repo aangemaakt en gesynchroniseerd
- [x] `.env` en lokale database ingesteld
- [x] Clonen en testen op tweede machine (laptop/pc)

## Fase 1: Data verzamelen
Database opzetten, RSS-feeds ophalen en opschonen, en het aantal bronnen uitbreiden.

### Bouwblok 1: Databasemodel
- [x] Migraties voor bronnen, gebeurtenissen, artikelen, samenvattingen
- [x] Foreign keys en relaties in de migraties (`bron_id`, `gebeurtenis_id`)
- [x] Eloquent models met `$table` expliciet gezet (Nederlandse tabelnamen worden niet automatisch herkend)
- [x] Relaties op de models: hasMany, belongsTo, hasOne
- [x] `$fillable` op elk model dat via `create()` of `update()` gevuld wordt
- [x] BronSeeder met bronnen, oriëntatie en feed-URL
- [x] Bron met oriëntatie `left`: The Nation in de seeder (`thenation.com/feed/?post_type=article`)
- [x] Getest met `migrate:fresh --seed`

### Bouwblok 2: Artikelen verzamelen
- [x] Artisan command `artikelen:ophalen` aangemaakt
- [x] Bronnen met een feed-URL ophalen uit de database
- [x] Feed per bron ophalen met de Laravel HTTP client
- [x] Feed-inhoud parsen met `simplexml_load_string`
- [x] Titel, URL, publicatiedatum en tekst per item uitlezen
- [x] Publicatiedatum omzetten naar het juiste formaat
- [x] Duplicaatcheck op URL voordat een artikel wordt opgeslagen
- [x] Artikel opslaan gekoppeld aan de juiste bron
- [x] Gecontroleerd dat een tweede keer draaien geen nieuwe duplicaten oplevert
- [x] Foutafhandeling bij geen verbinding (`ConnectionException`)
- [x] Foutafhandeling bij 4xx/5xx (`failed()`)
- [x] Foutafhandeling bij kapotte of lege XML bij status 200
- [x] Per bron één totaalregel in plaats van een regel per artikel: nieuwe artikelen, nieuwe gebeurtenissen, gekoppeld aan een bestaande gebeurtenis
- [x] `description` opschonen vóór opslaan: HTML-entiteiten decoderen, Guardian-`<li>`-promo's (`Get our …`, `Listen to the podcast …`) eraf, daarna HTML-tags naar spaties, witruimte plat, `Continue reading...` eraf, WordPress-footer `The post ... appeared first on ...` eraf. Sinds bouwblok 15 ook de afsluiters van Mediaite, TheBlaze, GB News, Punch en WordPress-`[…]`
- [x] `volledige_tekst` van de later toegevoegde wereldwijde en lokale feeds controleren, niet alleen de titel of status 200

### Bouwblok 15: Bronnen uitbreiden
- [x] 212 kandidaat-feeds getest met dezelfde ophaal- en filterlogica als `artikelen:ophalen`: genoeg items, leesbare tekst na filtering, geldige `pubDate`, recent
- [x] 102 nieuwe bronnen toegevoegd aan `BronSeeder`, dubbele feeds en een deels Franstalige feed weggelaten. Totaal 124 bronnen
- [x] Seeder geordend per regio of categorie (VS politiek, VS algemeen, VS lokaal, VS wereld, VK en Ierland, Europa, Midden-Oosten, Azië, Oceanië, Amerika, Afrika, staatsmedia) en binnen elke groep van links naar rechts
- [x] Meer bronnen per label, vooral `left`, `neutral` en `right`. Staatsmedia in een eigen groep
- [x] Tekstfilter uitgebreid: `first appeared on` (Mediaite), `Like Blaze News? …` (TheBlaze), `Our Standards: The GB News Editorial Charter` (GB News), `Read More: https://…` (Punch), `[…]` wordt `…`
- [x] Feature-test `ArtikelenOphalenTest` voor elke filterregel, plus een test dat `Read More:` midden in een zin blijft staan
- [x] Onbruikbare feeds met reden vastgelegd in [BRONNEN.md](BRONNEN.md) (Atom/RDF, 403, alleen teaser, verouderd, geen verbinding)
- [x] Oriëntatie van alle bronnen nagelopen in de MBFC-dataset van idiap: 90 klopten, 25 aangepast (seeder, database en [BRONNEN.md](BRONNEN.md)), 9 staan niet in de dataset
- [x] `/over` en het kopje Transparantie noemen de dataset van idiap en de 9 ingeschatte labels

## Fase 2: Gebeurtenissen groeperen
Artikelen over hetzelfde nieuws bij elkaar zetten: eerst met trefwoorden (RAKE), daarna met embeddings.

### Bouwblok 3: Gebeurtenissen groeperen
- [x] Service-klasse `app/Services/GebeurtenisMatcher.php`
- [x] Aanroepen direct na het opslaan van een nieuw artikel in het command
- [x] Trefwoorden via RAKE (`donatello-za/rake-php-plus`, `en_US`, `keywords()`), niet meer een eigen stopwoordenlijst
- [x] Matching op overlap in de eerste 40 woorden van `volledige_tekst` (RSS-omschrijving), niet de titel
- [x] HTML uit de tekst strippen voor de trefwoorden
- [x] Gebeurtenis krijgt als `onderwerp` de titel van het eerste artikel
- [x] Groepering alleen binnen dezelfde kalenderdatum (`whereDate('publicatiedatum', $zelfdeDatum)`) — geen match meer op de volgende dag
- [x] Drempel: minstens 6 gedeelde trefwoorden (`$minimaleOverlap`). Was 7 bij de eigen stopwoordenlijst; na RAKE 5, daarna 6
- [x] Eén `RakePlus`-instance hergebruiken (`extract()`), anders laadt elke vergelijking `en_US` opnieuw
- [x] Trefwoorden per artikel-id cachen (`trefwoordenVan()`), anders draait RAKE opnieuw per kandidaat
- [x] Nieuwe gebeurtenis aanmaken als er geen match is
- [x] `gebeurtenis_id` op het artikel updaten
- [x] Getest met artikelen over hetzelfde feit bij meerdere bronnen (SCOTUS third-country deportations)
- [x] Getest dat duidelijk andere onderwerpen meestal niet bij elkaar horen
- [x] Resultaat gecontroleerd in phpMyAdmin
- [x] `koppel()` geeft `'nieuw'`, `'bestaand'` of `'overgeslagen'` terug, zodat het command kan tellen. Geen `echo` meer in de service

### Bouwblok 12: Groeperen en onderwerp verbeteren
- [x] RAKE (`donatello-za/rake-php-plus`) als vervanging van de eigen stopwoordenlijst: `keywords()`, `en_US`, drempel 6, alleen artikelen van dezelfde datum
- [x] Handmatig herkoppelen blijft: samenvattingen weg, `gebeurtenis_id` leegmaken, opnieuw `koppel()`, of `migrate:fresh --seed` plus `artikelen:ophalen`

### Bouwblok 16: Groeperen met embeddings (Ollama)
- [x] RAKE-matcher kiest de kandidaat met de meeste overlap in plaats van de eerste die de drempel haalt
- [x] Ollama lokaal gebruikt met het model `nomic-embed-text` (768 getallen per tekst); geen VS Code-extensie, de Ollama-app draait op `http://localhost:11434`
- [x] Service `Ollama` die een batch teksten naar `/api/embed` stuurt, met duidelijke fouten bij ontbrekende config of een lege respons
- [x] Kolom `embedding` (JSON) op `artikelen`; MySQL blijft, cosine similarity wordt in PHP berekend
- [x] Embedding-tekst: `clustering: ` + titel + eerste 100 woorden van `volledige_tekst`
- [x] `GebeurtenisMatcher` koppelt aan de kandidaat met de hoogste similarity boven `MATCHER_MINIMALE_SIMILARITY`; zelfde-dag-regel blijft
- [x] Methode instelbaar via `MATCHER_METHODE` (`embeddings` of `rake`); zonder embedding (Ollama uit) valt de matcher terug op RAKE
- [x] `artikelen:ophalen` maakt per bron één batch embeddings en koppelt daarna
- [x] Command `artikelen:embeddings` voor bestaande artikelen zonder embedding; 4.206 artikelen in 9 min 42 s (±140 ms per artikel)
- [x] Drempel afgestemd op echte data: 0,85. Daarboven vooral dezelfde gebeurtenis die RAKE miste, tussen 0,80 en 0,85 vooral hetzelfde onderwerp met een ander verhaal
- [x] Tests: beste kandidaat wint, nieuwe gebeurtenis onder de drempel, embeddings via de feed opgeslagen, terugval op RAKE als Ollama onbereikbaar is

## Fase 3: AI-analyse en samenvatting
Artikelen per gebeurtenis door een taalmodel laten vergelijken en samenvatten.

### Bouwblok 4: Artikelen vergelijken
- [x] AI-integratie opzetten (API key, configuratie in `.env`)
- [x] Service-klasse aanmaken, bijvoorbeeld `app/Services/ArtikelVergelijker.php`
- [x] Artikelen van een gebeurtenis ophalen (via de `artikelen()` relatie op Gebeurtenis)
- [x] Prompt opstellen die vraagt om taalgebruik, benadrukte onderwerpen en behandelde actoren te benoemen
- [x] AI-aanroep uitvoeren en het antwoord verwerken
- [x] Testen met een gebeurtenis die artikelen van meerdere bronnen heeft
- [x] Beoordelen of de output bruikbaar genoeg is als input voor bouwblok 5

### Bouwblok 5: Samenvatting genereren
- [x] Artisan command om een samenvatting te genereren (bijvoorbeeld samenvattingen:genereren)
- [x] Bestaande AI-service uitbreiden met een prompt voor de neutrale samenvatting
- [x] Prompt laten vragen om kernfeiten, betrokkenen, overeenstemming en verschil
- [x] Antwoord verwerken tot de losse velden van het Samenvatting-model
- [x] Samenvatting opslaan gekoppeld aan de gebeurtenis
- [x] Alleen samenvatten als er nog geen samenvatting is, of als er nieuwe artikelen bij zijn gekomen
- [x] Testen of de samenvatting daadwerkelijk neutraal aanvoelt en geen belangrijk verschil mist
- [x] Testen of de tekst begrijpelijk is voor een breed publiek, zoals de casus vraagt
- [x] Maximaal 3 pogingen per gebeurtenis bij ongeldige JSON of een timeout, daarna die gebeurtenis overslaan en doorgaan
- [x] JSON-format best practices in de samenvattingsprompt (`json_schema`)
- [x] Prompt caching (vast system-bericht vooraan, artikelen in het user-bericht; `cached_tokens` in de log)
- [x] Input- en outputtokens loggen, plus een kostenschatting uit `.env`
- [x] LLM-parameters via `.env`: `LITELLM_THINKING_TYPE`, `LITELLM_REASONING_EFFORT`, `LITELLM_TEMPERATURE` (leeg = DeepSeek-standaard)
- [x] Prompt aangescherpt: kernfeiten zelfstandig leesbaar, betrokkenen als kommagescheiden namen, overeenstemming/verschil over schrijfwijze. Geen MBFC-labels in de prompt of in die twee velden

## Fase 4: Website
Overzicht, detailpagina, layout en vormgeving.

### Bouwblok 6: Overzicht van gebeurtenissen
- [x] Route `/` en `/gebeurtenissen` in `routes/web.php` naar `GebeurtenisController@index`, in plaats van de standaard welkomstpagina
- [x] Controller `app/Http/Controllers/GebeurtenisController.php` (Laravel-conventie: een pagina hoort in een controller, niet in een closure)
- [x] `index`-methode die gebeurtenissen (met minstens 2 artikelen) ophaalt, met het aantal artikelen en of er een samenvatting is
- [x] Blade-view `resources/views/gebeurtenissen/index.blade.php`
- [x] Per gebeurtenis: onderwerp, aantal artikelen, en of er al een samenvatting is. Sinds bouwblok 9 toont de lijst de telling Links / Midden / Rechts in plaats van `artikelen_count`. Sinds bouwblok 10 is wel/geen samenvatting het filter op de lijst, niet meer tekst per rij
- [x] Gebeurtenissen zonder samenvatting blijven zichtbaar (filter Alle of Zonder)
- [x] Getest in de browser: onderwerp en telling kloppen met phpMyAdmin (alleen groepen met 2+ artikelen)

### Bouwblok 7: Detailpagina van één gebeurtenis
- [x] Route `/gebeurtenissen/{gebeurtenis}` naar een `show`-methode op dezelfde controller
- [x] Route model binding: Laravel zoekt de `Gebeurtenis` zelf op via het id in de URL
- [x] Blade-view `resources/views/gebeurtenissen/show.blade.php`
- [x] Samenvatting tonen in de vier velden: kernfeiten, betrokkenen, overeenstemming, verschil
- [x] Duidelijke lege staat als deze gebeurtenis nog geen samenvatting heeft
- [x] Artikelen ophalen inclusief bron, via de relatie `artikelen.bron`
- [x] Artikelen groeperen op `orientatie` van de bron
- [x] Oriëntatie tonen zoals die in de database staat (`left-center`, `neutral`, `right-center`, `right`). Bundelen naar Links, Midden en Rechts is alleen weergave, de opgeslagen waarde blijft de Media Bias Fact Check-label
- [x] Bakjes altijd in volgorde Links, Midden, Rechts (`sortBy` na `groupBy`). Sinds bouwblok 9 is een leeg bakje zichtbaar, met een lege staat
- [x] Per artikel: titel, bronnaam en link naar de originele URL
- [x] Vanaf het overzicht linkt elk onderwerp naar deze pagina (`route('gebeurtenissen.show', $gebeurtenis)`)
- [x] Getest met een gebeurtenis die een samenvatting én artikelen van meerdere bronnen heeft
- [x] Getest met een gebeurtenis zonder samenvatting

### Bouwblok 8: Layout en leesbare UI
- [x] Blade-layout `resources/views/layouts/app.blade.php` met `@yield` / `@section`
- [x] Vite/Tailwind koppelen (`@vite` in de layout); `npm run dev` naast `php artisan serve`
- [x] Kop MediaLens, link naar het overzicht, op de detailpagina een terug-link via named route
- [x] Typografie: samenvatting als aparte `<p>` per veld, artikellijst leesbaar op mobiel
- [x] Named route voor `index`: `gebeurtenissen.index` op `/gebeurtenissen`
- [x] Getest: `/` en `/gebeurtenissen/{id}` delen header/footer; zonder `public/hot` of `public/build/manifest.json` blijft de pagina HTML (geen `ViteManifestNotFoundException`). Sinds bouwblok 11 deelt `/over` dezelfde layout; de footer heeft een link naar `route('over')`

### Bouwblok 9: Spectrum als drie kolommen
- [x] Detailpagina: drie kolommen Links / Midden / Rechts (`md:grid-cols-3`, onder 768px onder elkaar)
- [x] Lege kolom: kop plus "Geen artikelen in dit bakje.", geen crash
- [x] Per artikel: titel, bronnaam, ruwe MBFC-label, link
- [x] Op het overzicht: telling Links / Midden / Rechts via `withCount` en `whereHas` (geen extra AI). De regel met `artikelen_count` is uit de lijst gehaald
- [x] Getest met een groep die niet alle drie de bakjes vult (`/gebeurtenissen/130` zonder Midden, `/gebeurtenissen/207` alleen Links)

### Bouwblok 10: Overzicht sorteren en pagineren
- [x] Sorteren: nieuwste `artikelen.publicatiedatum` eerst (`withMax`), daarna `id` DESC als tiebreaker. Niet `updated_at` van de gebeurtenis
- [x] Laravel `paginate(15)` in `index()`, `$gebeurtenissen->links()` in Blade, `withQueryString()` zodat het filter op pagina 2 blijft
- [x] Filter: alle / alleen mét samenvatting / alleen zonder (querystring `?filter=met` of `?filter=zonder`, geen JavaScript)
- [x] Gebeurtenissen met 1 artikel blijven buiten de lijst (zelfde drempel als bouwblok 5/6)
- [x] Wel/geen samenvatting per rij weggehaald; `withExists('samenvatting')` ook, die kolom werd nergens meer gelezen
- [x] Getest: tweede pagina, filter “zonder samenvatting”, volgorde klopt met phpMyAdmin (`ORDER BY nieuwste DESC, g.id DESC`)

### Bouwblok 14: Visuele vormgeving en artikelafbeeldingen
- [x] Homepage vormgeven als een overzichtelijke nieuwsfeed, geïnspireerd op de scanbare opmaak van Ground News; eigen MediaLens-stijl behouden en geen ontwerp of merkonderdelen letterlijk kopiëren
- [x] Gebeurtenissen als duidelijke kaarten tonen met onderwerp, publicatiedatum, aantal artikelen en verdeling Links / Midden / Rechts
- [x] Afbeelding uit `media:content`, `media:thumbnail` of een afbeeldings-`enclosure` op de gebeurteniskaart tonen; audio-enclosures overslaan
- [x] Afbeeldings-URL en optionele credit opslaan; de kaart toont de afbeelding in een vaste verhouding met titel als alt-tekst, lazy loading en credit waar beschikbaar
- [x] Detailpagina visueel laten aansluiten op de feed met kleine bronfavicons (initialen als fallback), herkenbare oriëntatielabels en ruimte voor de samenvatting; geen artikelafbeeldingen op deze pagina
- [x] Onbekende of ontbrekende oriëntaties alleen tonen in een inklapbaar blok als zulke artikelen bestaan
- [x] Verdelingsbalk op het overzicht beperken tot de breedte van de Links/Midden/Rechts-labels
- [x] Layout testen op mobiel en desktop, inclusief artikelen zonder afbeelding, lange koppen en lege perspectiefkolommen
- [x] Controleren op toegankelijkheid en stabiele layout: voldoende contrast, toetsenbordbediening, alt-tekst en geen verspringende inhoud bij het laden van afbeeldingen
- [x] Pagina `/artikelen` met alle artikelen, inclusief artikelen zonder gebeurtenis; afbeeldingen en credits worden getoond waar beschikbaar
- [x] Zoeken en filteren op gebeurtenissen en artikelen: zoekterm, samenvattingsstatus en perspectief; op artikelen ook wel/geen gebeurtenis
- [x] Menubalk met onderwerpen: Home, Wereld, Politiek, Economie, Tech & Wetenschap, Sport, Alles. De AI kiest de categorie bij `samenvattingen:genereren`; `gebeurtenissen:categoriseren` deelt bestaande gebeurtenissen in
- [x] Homepage `/` als overzicht: Hot (meeste artikelen in de laatste 48 uur), per onderwerp de 5 nieuwste gebeurtenissen, en de 5 gebeurtenissen met de meeste artikelen
- [x] Sportfeeds toevoegen, anders blijft het onderwerp Sport leeg
- [x] Onderwerppagina's tonen eerst 3 trending gebeurtenissen naast elkaar (meeste artikelen in de laatste 48 uur, zelfde regel als Hot), daarna de rest van de lijst zonder die 3

## Fase 5: Transparantie en kwaliteit
Uitleggen wat MediaLens wel en niet kan, en de code laten reviewen.

### Bouwblok 11: Transparantie (ethiek uit de casus)
- [x] Route `/over` naar `OverController@index`, named route `over`, view `resources/views/over.blade.php`. Niet in `GebeurtenisController`: die pagina heeft geen gebeurtenis-query
- [x] Uitleg: labels uit Media Bias Fact Check per bron, niet per artikel; bakjes zijn weergave
- [x] Benoem: matcher groepeert op trefwoordoverlap, niet op “dezelfde waarheid”; de AI kan zelf vooringenomen zijn; DeepSeek via OpenRouter is een keus, geen neutrale arbiter (geen Groq in deze app). Sinds bouwblok 16 legt `/over` ook de embeddings uit
- [x] Op de detailpagina een korte disclaimer onder de kolommen, link `route('over')`. Ook zichtbaar zonder samenvatting
- [x] Footer-link **Over** in de layout, zodat `/over` ook vanaf het overzicht bereikbaar is
- [x] Kopje “Transparantie” in [ONTWERPKEUZES.md](ONTWERPKEUZES.md#transparantie), zodat het niet alleen in de UI staat

### Bouwblok 13: Feedback krijgen en verwerken met AI
- [x] AI gebruikt om de README, notities en projectstructuur te reviewen en de belangrijkste risico's te benoemen
- [x] AI-feedback gebruikt om de eerste failing test te analyseren en de echte oorzaak te vinden: testdatabase was niet goed ingesteld
- [x] `RefreshDatabase` toegevoegd aan `ExampleTest` zodat Laravel de SQLite-testdatabase correct opbouwt per test
- [x] Maatwerk-feedback toegepast op `Bron`-model: `fillable` toegevoegd zodat testdata zonder mass-assignment-fout kan worden aangemaakt
- [x] Extra feature-test toegevoegd voor de overzichtspagina met echte sample-data
- [x] AI-audit gebruikt om de LLM-call te verbeteren: duidelijke fouten bij proxy-fouten, lege response en ontbrekende config
- [x] Fail-fast check toegevoegd in `LiteLlm`: `base_url`, `api_key` en `model` moeten aanwezig zijn voordat de API wordt aangeroepen
- [x] Extra test toegevoegd voor een 502-proxyfout en een incomplete LLM-configuratie
- [x] Detailpagina robuuster gemaakt bij lege of incomplete data: `null`-check voor artikelen en bron-orientatie, geen crash bij lege bakjes
- [x] View aangepast zodat een artikel zonder bron nog steeds veilig renderen kan met een fallback-tekst
- [x] AI-output validatie opgescherpt in `ArtikelVergelijker`: veldwaarden moeten echte tekststrings zijn, geen arrays of lege waarden
- [x] Retry-loop in `SamenvattingenGenereren` aangepast zodat bekende netwerk- en runtimefouten opnieuw worden geprobeerd, terwijl onverwachte exceptions expliciet worden gelogd en niet stilzwijgend worden doorgegeven
- [x] Extra regressietesten toegevoegd voor ontbrekende bron-data en ongeldige AI-output
- [x] Verificatie uitgevoerd met `php artisan test --filter=ExampleTest`; de suite draait groen
- [x] AI-feedback verwerkt als echte hardening pass in plaats van alleen “blind” codewijzigingen

## Fase 6: Volgende stappen
Alles wat nog open staat, gegroepeerd op soort. Open punten uit eerdere bouwblokken staan hier ook, met het bouwblok erbij.

### Bouwblok 17: Nieuwe functionaliteiten en verbeteringen

**Nieuwe functionaliteiten**
- [x] Blindspot-blok: gebeurtenissen die bijna alleen door links of alleen door rechts worden gebracht
- [x] Gerelateerde gebeurtenissen op de detailpagina: met de embeddings de 3 gebeurtenissen tonen die er het meest op lijken
- [x] Bronpagina: per bron het label, de laatste artikelen en hoe vaak die bron in gebeurtenissen voorkomt. De idiap-dataset heeft ook een kolom `factual_reporting` (high/mixed/low); die kan als "betrouwbaarheid" naast de politieke kleur
- [x] Gebeurtenis over meerdere dagen: nieuws dat doorloopt, valt nu uiteen in losse gebeurtenissen per dag. De vergelijking uitbreiden naar bijvoorbeeld 48 uur, of gebeurtenissen van opeenvolgende dagen als tijdlijn aan elkaar koppelen

**Groeperen en data**
- [x] Publicatietijd opslaan, niet alleen de datum (`publicatiedatum` is nu een `date`). Daarmee kan: "2 uur geleden" tonen; Hot echt op 48 uur zetten in plaats van "vandaag plus gisteren"; het middernachtprobleem bij het groeperen oplossen
- [x] Betere titel (`onderwerp`) voor een gebeurtenis: nu is dat de titel van het eerste artikel. Bijvoorbeeld met de embeddings de "meest centrale" titel kiezen (open sinds bouwblok 12)
- [x] Matcher: in code documenteren waarom 40 woorden, drempel 6, drempel 0,85 en het venster van 48 uur (`$maxUren`, sinds bouwblok 17; daarvoor de same-day-regel) (open sinds bouwblok 12)
- [x] Oriëntatie van de 9 bronnen buiten de idiap-dataset met de hand nalopen op mediabiasfactcheck.com (open sinds bouwblok 15)

**Website**
- [x] Omschrijving onder de artikeltitel op de detailpagina
- [x] Samenvatting: datum en model tonen
- [x] Externe links: artikellinks openen nu in hetzelfde tabblad
- [x] alleen preview laten zien bij de artikelen per perspectief bij show pagina. ook "Toon alles" knop aanmaken die alle artikelen toont binnen show pagina.

**Cosmetisch**
- [x] Dark mode

**Techniek en onderhoud**
- [x] Feeds parallel ophalen (`Http::pool()`)
- [x] Scheduler: `artikelen:ophalen` en `samenvattingen:genereren` automatisch elk uur laten draaien via Laravel `Schedule` in `routes/console.php`
- [x] Latere pass gebruiken voor review van matcher-, controller- en summary-kwaliteit met AI-gestuurde feedbackloops (open sinds bouwblok 13)

### Later / optioneel
- [ ] Volledige artikeltekst via web scraping of een nieuws-API i.p.v. de RSS-omschrijving in `volledige_tekst`
- [ ] Oriëntatie per artikel via AI / tekstclassificatie i.p.v. alleen het MBFC-label per bron
