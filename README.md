# MediaLens
AI-ondersteund systeem dat nieuwsartikelen over dezelfde gebeurtenis uit meerdere bronnen verzamelt, elke bron een politieke oriëntatie meegeeft, en een neutrale samenvatting genereert die laat zien waar bronnen van mening verschillen. Gebouwd in Laravel. Overzicht en detailpagina van gebeurtenissen staan in de browser.

## Tech stack
- Laravel (laravel/laravel), lokaal via XAMPP
- MySQL database, lokaal beheerd via phpMyAdmin
- PHP 8.5

## Opzetten op een nieuwe machine
1. Repo clonen
2. `composer install`
3. `.env.example` kopiëren naar `.env` en de database-instellingen invullen met je eigen lokale database naam, gebruiker en wachtwoord. Vul ook `LITELLM_BASE_URL`, `LITELLM_API_KEY` en `LITELLM_MODEL` in. Die namen staan al in `.env.example`, met model `openrouter/deepseek/deepseek-v4-flash`. De key hoort alleen in `.env`, niet in git. Optioneel: `LITELLM_THINKING_TYPE`, `LITELLM_REASONING_EFFORT` en `LITELLM_TEMPERATURE`. Leeg laten gebruikt de DeepSeek-standaard (`enabled`, `high`, `1`), via `config/services.php`.
4. `php artisan key:generate`
5. Database `medialens` aanmaken in phpMyAdmin
6. `php artisan migrate:fresh --seed`
7. `php artisan artikelen:ophalen` om artikelen binnen te halen en te groeperen. Per bron eerst `Ophalen bij …`, daarna één regel: hoeveel nieuwe artikelen, hoeveel daarvan een nieuwe gebeurtenis openden, en hoeveel aan een bestaande gebeurtenis gekoppeld zijn. Een URL die al in de database staat telt niet mee.
8. `php artisan samenvattingen:genereren` voor alle gebeurtenissen, of `php artisan samenvattingen:genereren 5` voor één id
9. Controleren in de Artisan-output en in phpMyAdmin (`artikelen`, `gebeurtenissen`, `samenvattingen`)
10. `php artisan serve` en open `http://127.0.0.1:8000/` of `http://127.0.0.1:8000/gebeurtenissen`. Een onderwerp opent `/gebeurtenissen/{id}`. XAMPP Apache draait PHP 8.2 en start Laravel 13 niet. De CLI-PHP (8.4+) wel. phpMyAdmin via XAMPP blijft werken.

Of feeds en samenvattingen kloppen, zie je bij stap 7 tot en met 9. Of de lijst en de detailpagina kloppen, bij stap 10 plus phpMyAdmin (`gebeurtenissen`, `artikelen`, `samenvattingen`, `bronnen`).

De database hoeft niet handmatig overgezet te worden tussen machines, migraties en seeders bouwen hem overal identiek op.

## RSS-bronnen
De bronnentabel wordt gevuld via `database/seeders/BronSeeder.php`. Oriëntatie komt uit de Media Bias Fact Check dataset. De seeder zet deze feeds in de database.

Politiek (VS):

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The Nation | `https://www.thenation.com/feed/?post_type=article` | left |
| The New York Times: Politics | `https://rss.nytimes.com/services/xml/rss/nyt/Politics.xml` | left-center |
| The Washington Post: Politics | `https://feeds.washingtonpost.com/rss/politics` | left-center |
| The Hill | `https://thehill.com/news/feed/` | neutral |
| The Daily Signal | `https://www.dailysignal.com/feed/` | right |
| Washington Examiner | `https://www.washingtonexaminer.com/feed/` | right |
| Fox News: Politics | `https://moxie.foxnews.com/google-publisher/politics.xml` | right |

Wereldwijd:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| BBC News: World | `https://feeds.bbci.co.uk/news/world/rss.xml` | left-center |
| The Guardian: World | `https://www.theguardian.com/world/rss` | left-center |
| Al Jazeera English | `https://www.aljazeera.com/xml/rss/all.xml` | left-center |
| The New York Times: World | `https://rss.nytimes.com/services/xml/rss/nyt/World.xml` | left-center |
| The Washington Post: World | `https://feeds.washingtonpost.com/rss/world` | left-center |
| South China Morning Post | `https://www.scmp.com/rss/91/feed/` | left-center |
| ABC News (Australia) | `https://www.abc.net.au/news/feed/45910/rss.xml` | left-center |
| NDTV: Top stories | `https://feeds.feedburner.com/ndtvnews-top-stories` | left-center |
| Sky News: World | `https://feeds.skynews.com/feeds/rss/world.xml` | neutral |
| France 24 | `https://www.france24.com/en/rss` | neutral |

Lokaal (VS):

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| NBC News | `https://feeds.nbcnews.com/nbcnews/public/news` | left-center |
| CBS News | `https://www.cbsnews.com/feeds/rss/main.rss` | left-center |
| The New York Times | `https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml` | left-center |
| LA Times | `https://www.latimes.com/local/rss2.0.xml` | left-center |
| New York Post | `https://nypost.com/feed` | right-center |

Politico en National Review zitten niet meer in de seeder.

Onbruikbaar gebleken, niet gebruiken:
- CNN (`rss.cnn.com/rss/edition.rss`): status 200, maar bevroren data uit 2023
- The Wall Street Journal: oude `online.wsj.com`-link werkt niet meer
- Drudge Report (`feedpress.me/drudgereportfeed`): `description` is pagina-HTML (Patreon, andere sites, feedpress-GIF), niet de artikeltekst. Matching op die kolom groepeert dan alle Drudge-items op de boilerplate
- HuffPost, Reuters, The Economist, The Times (UK), MSNBC, Breitbart: nog niet bevestigd werkend

The Nation stond eerder op die onbruikbaar-lijst (layout-HTML plus “appeared first on The Nation”). De feed is terug in de seeder als enige bron met oriëntatie `left`. `artikelen:ophalen` haalt nu tags, extra witruimte, `Continue reading...`, de WordPress-footer `The post ... appeared first on ...` en de Guardian-promo `Get our … email … free app or daily news podcast` uit `description` voordat de tekst wordt opgeslagen. Die footer zat bij korte Nation-omschrijvingen in de eerste 40 woorden van de matcher (`post`, `appeared`, `first`, `nation`, plus de titel nog eens). De Guardian-promo (tussen de standfirst en de alinea, na tag-strip: `get`, `our`, `email`, `free`, `app`, `daily`, `news`, `podcast`) gaf 8 gedeelde trefwoorden, boven drempel 7. Daardoor werden losse Guardian-AU-stukken (kolenmijn, immigratie, moordzaken) één gebeurtenis. Bestaande rijen blijven de oude tekst houden tot je ze zelf bijwerkt: de duplicaatcheck op URL slaat ze over. Daarna `gebeurtenis_id` leegmaken, `gebeurtenissen` legen, opnieuw `koppel()`.

`right-center` is de New York Post. Neutral zijn The Hill, Sky News en France 24. Right zijn The Daily Signal, Washington Examiner en Fox News. `left` is The Nation. De overige bronnen in de seeder zijn `left-center`.

Van The Daily Signal, The Washington Post en de later toegevoegde wereldwijde en lokale feeds is gecontroleerd dat `description` echte artikeltekst is.

Let op: Bij een nieuwe feed altijd de `volledige_tekst` van een paar items openen, niet alleen de titel of de HTTP-status.

## Bekende beperkingen

### Feeds
- Een RSS-feed levert meestal alleen titel en een korte samenvatting, niet de volledige artikeltekst. De kolom `volledige_tekst` is dus de RSS-omschrijving.
- Status 200 zegt niets over of de feed actueel is. Altijd publicatiedatums controleren.
- Status 200 zegt ook niets over de inhoud van `description`. Die kan HTML-layout zijn in plaats van artikeltekst. Bij het ophalen gaan tags naar een spatie, witruimte wordt plat, `Continue reading...` eraf, de WordPress-zin `The post ... appeared first on ...` eraf, en de Guardian-promo `Get our … email … free app or daily news podcast` eraf. `strip_tags` alleen plakt zinnen aan elkaar en haalt geen reclamezinnen weg.

### Groeperen
- Matching gebeurt op `volledige_tekst`, niet op de titel. Koppen over dezelfde gebeurtenis verschillen te sterk per bron. Dat wijkt af van de oorspronkelijke casusformulering (trefwoorden in de titel).
- Alleen de eerste 40 woorden van die omschrijving tellen mee (`vergelijkTekst()`). De volle tekst blijft in de database voor de AI. Zonder die knip werd drempel 7 vrijwel niets: een Guardian-digest van honderden woorden deelde makkelijk 7 journalistieke woorden met SCMP of NDTV.
- Overlapdrempel is 7 trefwoorden (`$minimaleOverlap` in `GebeurtenisMatcher`), tijdvenster max 3 dagen. Twee gedeelde woorden was te streng op titels en te los op omschrijvingen.
- Trefwoordmatching blijft simpel: de eerste kandidaat die de drempel haalt wint. Ketting-matching kan nog. Voorbeeld van vóór de knip van 40 woorden: Trump/Iran-stukken plus Guardian First Thing plus SCMP-Hongkong in één groep. Voorbeeld daarna: Guardian-AU-stukken via de gedeelde promo in de eerste 40 woorden (8 trefwoorden, drempel 7). Die promo gaat er bij het ophalen af; bestaande `volledige_tekst` blijft vies tot je die rijen bijwerkt en opnieuw koppelt.
- Artikelen worden alleen bij het ophalen of bij een handmatige `koppel()`-run gematcht. Na een wijziging in de matcher: `gebeurtenis_id` leegmaken, `gebeurtenissen` legen, opnieuw koppelen.

### Oriëntatie
- De oriëntatie komt van een dataset en is een generalisatie per bron, niet per artikel.
- `left` is The Nation. Het spectrum is daardoor niet meer alleen left-center tot right. De AI krijgt die labels nog steeds niet per artikel in de prompt.

### Samenvatting
- De AI kan zelf ook vooringenomen zijn.
- `samenvat()` stuurt een `json_schema` mee met de vier verplichte string-velden. Een lege string voldoet aan dat schema. `slaOp()` weigert een leeg veld alsnog, en het command probeert die gebeurtenis opnieuw.
- `samenvattingen:genereren` probeert een gebeurtenis maximaal 3 keer. Ongeldige JSON gooit een `RuntimeException` in `slaOp()`, vóór `create()` of `update()`. Een LLM die langer duurt dan `Http::timeout(60)` gooit een `ConnectionException`. Na 3 mislukte pogingen slaat het command die gebeurtenis over en gaat verder. Een andere exception stopt het command nog wel. Elke timeout-poging kan 60 seconden duren.
- Bij een HTTP-fout geeft `LiteLlm::vraag()` een lege string terug. Het command meldt dan een ontbrekend veld. De statuscode van de proxy staat daar niet bij.
- Prompt caching bij DeepSeek is automatisch. Het vaste system-bericht staat vooraan, de artikelen in het user-bericht. `cached_tokens` wordt gelogd in `storage/logs/laravel.log`. Een hit zie je vooral als dezelfde prompt terugkomt, zoals een retry of dezelfde gebeurtenis opnieuw samenvatten. Verschillende gebeurtenissen blijven meestal op `0`, omdat hun artikeltekst meteen afwijkt. `migrate:fresh` leegt alleen de database, niet de cache bij de provider.
- `LiteLlm` stuurt `thinking.type`, `reasoning_effort` en `temperature` mee uit `.env`. Lege regels vallen terug op `enabled`, `high` en `1`. Bij thinking aan heeft `temperature` geen effect; `top_p` speelt alleen tussen 0,95 en 1. Effort `max` kan de timeout van 60 seconden raken. Of de Educom-proxy die velden doorgeeft, zie je aan `reasoning_content` of `reasoning_tokens` in de response, niet alleen aan een 200.

### Overzicht
- De lijst toont alleen gebeurtenissen met minstens 2 artikelen, dezelfde drempel als `samenvattingen:genereren`. Groepen met 1 artikel staan wél in de tabel `gebeurtenissen`, niet op de pagina. Dat wijkt af van een 1-op-1-check tegen de hele tabel.
- `/` en `/gebeurtenissen` wijzen naar dezelfde `index`. Elk onderwerp linkt naar `/gebeurtenissen/{id}` via de named route `gebeurtenissen.show`.

### Detailpagina
- Bakjes `Links`, `Midden` en `Rechts` zijn alleen weergave. `bronnen.orientatie` blijft de Media Bias Fact Check-label (`left`, `left-center`, `neutral`, `right-center`, `right`). Die ruwe waarde staat per artikel achter de bronnaam.
- `groupBy` zet bakjes in de volgorde van het eerste artikel. `sortBy` daarna forceert Links, Midden, Rechts. Een bakje zonder artikelen krijgt geen kop.
- De views zijn kale HTML, nog geen gedeelde layout.

## Status en to-do

### Basisopzet
- [x] Laravel geïnstalleerd en lokaal draaiend
- [x] GitHub repo aangemaakt en gesynchroniseerd
- [x] `.env` en lokale database ingesteld
- [x] Clonen en testen op tweede machine (laptop/pc)

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
- [x] `description` opschonen vóór opslaan: HTML-tags naar spaties, witruimte plat, `Continue reading...` eraf, WordPress-footer `The post ... appeared first on ...` eraf, Guardian-promo `Get our … email … free app or daily news podcast` eraf
- [x] `volledige_tekst` van de later toegevoegde wereldwijde en lokale feeds controleren, niet alleen de titel of status 200

### Bouwblok 3: Gebeurtenissen groeperen
- [x] Service-klasse `app/Services/GebeurtenisMatcher.php`
- [x] Aanroepen direct na het opslaan van een nieuw artikel in het command
- [x] Stopwoorden eruit filteren
- [x] Matching op overlap in de eerste 40 woorden van `volledige_tekst` (RSS-omschrijving), niet de titel
- [x] HTML uit de tekst strippen voor de trefwoorden
- [x] Gebeurtenis krijgt als `onderwerp` de titel van het eerste artikel
- [x] Groepering binnen max 3 dagen
- [x] Drempel: minstens 7 gedeelde trefwoorden (`$minimaleOverlap`)
- [x] Nieuwe gebeurtenis aanmaken als er geen match is
- [x] `gebeurtenis_id` op het artikel updaten
- [x] Getest met artikelen over hetzelfde feit bij meerdere bronnen (SCOTUS third-country deportations)
- [x] Getest dat duidelijk andere onderwerpen meestal niet bij elkaar horen
- [x] Resultaat gecontroleerd in phpMyAdmin
- [x] `koppel()` geeft `'nieuw'`, `'bestaand'` of `'overgeslagen'` terug, zodat het command kan tellen. Geen `echo` meer in de service

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

### Bouwblok 6: Overzicht van gebeurtenissen
- [x] Route `/` en `/gebeurtenissen` in `routes/web.php` naar `GebeurtenisController@index`, in plaats van de standaard welkomstpagina
- [x] Controller `app/Http/Controllers/GebeurtenisController.php` (Laravel-conventie: een pagina hoort in een controller, niet in een closure)
- [x] `index`-methode die gebeurtenissen (met minstens 2 artikelen) ophaalt, met het aantal artikelen en of er een samenvatting is
- [x] Blade-view `resources/views/gebeurtenissen/index.blade.php`
- [x] Per gebeurtenis: onderwerp, aantal artikelen, en of er al een samenvatting is
- [x] Gebeurtenissen zonder samenvatting blijven zichtbaar
- [x] Getest in de browser: onderwerp, artikelcount en wel/geen samenvatting kloppen met phpMyAdmin (alleen groepen met 2+ artikelen)

### Bouwblok 7: Detailpagina van één gebeurtenis
- [x] Route `/gebeurtenissen/{gebeurtenis}` naar een `show`-methode op dezelfde controller
- [x] Route model binding: Laravel zoekt de `Gebeurtenis` zelf op via het id in de URL
- [x] Blade-view `resources/views/gebeurtenissen/show.blade.php`
- [x] Samenvatting tonen in de vier velden: kernfeiten, betrokkenen, overeenstemming, verschil
- [x] Duidelijke lege staat als deze gebeurtenis nog geen samenvatting heeft
- [x] Artikelen ophalen inclusief bron, via de relatie `artikelen.bron`
- [x] Artikelen groeperen op `orientatie` van de bron
- [x] Oriëntatie tonen zoals die in de database staat (`left-center`, `neutral`, `right-center`, `right`). Bundelen naar Links, Midden en Rechts is alleen weergave, de opgeslagen waarde blijft de Media Bias Fact Check-label
- [x] Bakjes altijd in volgorde Links, Midden, Rechts (`sortBy` na `groupBy`)
- [x] Per artikel: titel, bronnaam en link naar de originele URL
- [x] Vanaf het overzicht linkt elk onderwerp naar deze pagina (`route('gebeurtenissen.show', $gebeurtenis)`)
- [x] Getest met een gebeurtenis die een samenvatting én artikelen van meerdere bronnen heeft
- [x] Getest met een gebeurtenis zonder samenvatting

### Bouwblok 8: Layout en leesbare UI
- [ ] Blade-layout `resources/views/layouts/app.blade.php` met `@yield` / `@section` (Laravel-conventie; nu twee losse HTML-documenten)
- [ ] Vite/Tailwind koppelen (`@vite` in de layout); `npm run dev` naast `php artisan serve`
- [ ] Kop MediaLens, link naar het overzicht, op de detailpagina een terug-link via named route
- [ ] Typografie: samenvatting als alinea’s (`nl2br` of aparte `<p>`), artikellijst leesbaar op mobiel
- [ ] Named route voor `index` (nu alleen `gebeurtenissen.show`)
- [ ] Getest: `/` en `/gebeurtenissen/{id}` delen header/footer; zonder Vite-build geen kapotte pagina (fallback of duidelijke README-stap)

### Bouwblok 9: Spectrum als drie kolommen
- [ ] Detailpagina: drie kolommen Links / Midden / Rechts (stacked op smal scherm)
- [ ] Lege kolom: duidelijke lege staat, geen crash
- [ ] Per artikel: titel, bronnaam, ruwe MBFC-label, link (blijft)
- [ ] Optioneel op het overzicht: mini-indicatie hoeveel artikelen links/midden/rechts (geen extra AI)
- [ ] Getest met een groep die niet alle drie de bakjes vult

### Bouwblok 10: Overzicht sorteren en pagineren
- [ ] Sorteren: nieuwste gebeurtenis eerst (bijv. `updated_at` of max `artikelen.publicatiedatum`)
- [ ] Laravel `paginate()` in `index()`, links in Blade
- [ ] Filter: alle / alleen mét samenvatting / alleen zonder (querystring, geen JavaScript-plicht)
- [ ] Gebeurtenissen met 1 artikel blijven buiten de lijst (zelfde drempel als bouwblok 5/6)
- [ ] Getest: tweede pagina, filter “zonder samenvatting”, volgorde klopt met phpMyAdmin

### Bouwblok 11: Transparantie (ethiek uit de casus)
- [ ] Route `/over` of `/transparantie` met uitleg: labels komen uit Media Bias Fact Check per bron, niet per artikel; bakjes zijn weergave
- [ ] Benoem: matcher groepeert op trefwoordoverlap, niet op “dezelfde waarheid”; de AI kan zelf vooringenomen zijn; Groq/DeepSeek is een keus, geen neutrale arbiter
- [ ] Op de detailpagina een korte disclaimer onder de kolommen, link naar die pagina
- [ ] README: kopje “Transparantie” onder bekende beperkingen, zodat het niet alleen in de UI staat

### Bouwblok 12: Groeperen en onderwerp verbeteren
- [ ] `onderwerp` afleiden van gedeelde trefwoorden of de “meest centrale” titel, niet blind het eerste artikel
- [ ] Matcher: documenteer in code waarom 40 woorden en drempel 7; eventueel RAKE (php-package) als vervanging van de eigen stopwoordenlijst
- [ ] Handmatig herkoppelen blijft: `gebeurtenis_id` leegmaken, opnieuw `koppel()`
- [ ] Getest op een bekende ketting-fout (digest vs echt nieuws) en op een goede groep (meerdere bronnen, zelfde feit)
- [ ] Afwijking van de casus blijft: matching op omschrijving, niet op titel

### Later / optioneel
- [ ] RAKE als bouwblok 12 geen RAKE wordt (eigen stopwoordenlijst vervangen)
- [ ] Scheduler: `artikelen:ophalen` + `samenvattingen:genereren` via Laravel `Schedule`
- [ ] Framing-analyse uit bouwblok 4 opslaan en tonen (`vergelijk()` is nu uitgecommentarieerd)
- [ ] Feature-tests voor `index` / `show`
- [ ] Embeddings + vectorsimilariteit i.p.v. trefwoordoverlap (casus noemt dit als mogelijke clustering)
- [ ] Volledige artikeltekst via web scraping of een nieuws-API i.p.v. de RSS-omschrijving in `volledige_tekst`
- [ ] Oriëntatie per artikel via AI / tekstclassificatie i.p.v. alleen het MBFC-label per bron