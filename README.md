# MediaLens
AI-ondersteund systeem dat nieuwsartikelen over dezelfde gebeurtenis uit meerdere bronnen verzamelt, elke bron een politieke oriëntatie meegeeft, en een neutrale samenvatting genereert die laat zien waar bronnen van mening verschillen. Backend-only in deze fase, gebouwd in Laravel.

## Tech stack
- Laravel (laravel/laravel), lokaal via XAMPP
- MySQL database, lokaal beheerd via phpMyAdmin
- PHP 8.5

## Opzetten op een nieuwe machine
1. Repo clonen
2. `composer install`
3. `.env.example` kopiëren naar `.env` en de database-instellingen invullen met je eigen lokale database naam, gebruiker en wachtwoord. Vul ook `LITELLM_BASE_URL`, `LITELLM_API_KEY` en `LITELLM_MODEL` in. Die namen staan al in `.env.example`, met model `openrouter/deepseek/deepseek-v4-flash`. De key hoort alleen in `.env`, niet in git.
4. `php artisan key:generate`
5. Database `medialens` aanmaken in phpMyAdmin
6. `php artisan migrate:fresh --seed`
7. `php artisan artikelen:ophalen` om artikelen binnen te halen en te groeperen. Per bron eerst `Ophalen bij …`, daarna één regel: hoeveel nieuwe artikelen, hoeveel daarvan een nieuwe gebeurtenis openden, en hoeveel aan een bestaande gebeurtenis gekoppeld zijn. Een URL die al in de database staat telt niet mee.
8. `php artisan samenvattingen:genereren` voor alle gebeurtenissen, of `php artisan samenvattingen:genereren 5` voor één id
9. Controleren in de Artisan-output en in phpMyAdmin (`artikelen`, `gebeurtenissen`, `samenvattingen`)

`php artisan serve` laat alleen zien dat Laravel start. Deze fase is backend-only. Of feeds en samenvattingen kloppen, zie je bij stap 7 tot en met 9.

De database hoeft niet handmatig overgezet te worden tussen machines, migraties en seeders bouwen hem overal identiek op.

## RSS-bronnen
De bronnentabel wordt gevuld via `database/seeders/BronSeeder.php`. Oriëntatie komt uit de Media Bias Fact Check dataset. De seeder zet deze feeds in de database.

Politiek (VS):

| Bron | Feed URL | Oriëntatie |
|---|---|---|
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
- The Nation (`thenation.com/feed/`): `description` is layout-HTML plus “appeared first on The Nation”, eveneens onbruikbaar voor matching
- HuffPost, Reuters, The Economist, The Times (UK), MSNBC, Breitbart: nog niet bevestigd werkend

Er is geen bron met oriëntatie `left`. `right-center` is de New York Post. Neutral zijn The Hill, Sky News en France 24. Right zijn The Daily Signal, Washington Examiner en Fox News. De overige bronnen in de seeder zijn `left-center`. Zonder een `left`-bron blijft het spectrum scheef, en bouwblok 4 en 5 laten daardoor minder “links versus rechts” zien.

Van The Daily Signal en The Washington Post is gecontroleerd dat `description` echte artikeltekst is. Van de later toegevoegde wereldwijde en lokale feeds is die controle nog niet overal gedaan.

Let op: Bij een nieuwe feed altijd de `volledige_tekst` van een paar items openen, niet alleen de titel of de HTTP-status.

## Bekende beperkingen
- Een RSS-feed levert meestal alleen titel en een korte samenvatting, niet de volledige artikeltekst. De kolom `volledige_tekst` is dus de RSS-omschrijving.
- Status 200 zegt niets over of de feed actueel is. Altijd publicatiedatums controleren.
- Status 200 zegt ook niets over de inhoud van `description`. Die kan HTML-layout zijn in plaats van artikeltekst. `strip_tags` haalt tags weg, niet herhaalde reclamezinnen.
- Matching gebeurt op `volledige_tekst`, niet op de titel. Koppen over dezelfde gebeurtenis verschillen te sterk per bron. Dat wijkt af van de oorspronkelijke casusformulering (trefwoorden in de titel).
- Overlapdrempel is 7 trefwoorden (`$minimaleOverlap` in `GebeurtenisMatcher`), tijdvenster max 3 dagen. Twee gedeelde woorden was te streng op titels en te los op omschrijvingen.
- Trefwoordmatching blijft simpel: de eerste kandidaat die de drempel haalt wint. Daardoor kan een artikel in een groep belanden via ketting-matching, zonder dat het over hetzelfde nieuwsfeit gaat. Voorbeeld: in een SCOTUS-groep over third-country deportations zat ook een artikel over student loans, via gedeelde woorden als Trump of administration.
- Artikelen worden alleen bij het ophalen of bij een handmatige `koppel()`-run gematcht. Na een wijziging in de matcher: `gebeurtenis_id` leegmaken, `gebeurtenissen` legen, opnieuw koppelen.
- De oriëntatie komt van een dataset en is een generalisatie per bron, niet per artikel.
- De AI (bouwblok 4 en 5) kan zelf ook vooringenomen zijn.
- `samenvattingen:genereren` probeert een gebeurtenis maximaal 3 keer. Ongeldige JSON gooit een `RuntimeException` in `slaOp()`, vóór `create()` of `update()`. Een LLM die langer duurt dan `Http::timeout(60)` gooit een `ConnectionException`. Na 3 mislukte pogingen slaat het command die gebeurtenis over en gaat verder. Een andere exception stopt het command nog wel. Elke timeout-poging kan 60 seconden duren.

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

### Bouwblok 3: Gebeurtenissen groeperen
- [x] Service-klasse `app/Services/GebeurtenisMatcher.php`
- [x] Aanroepen direct na het opslaan van een nieuw artikel in het command
- [x] Stopwoorden eruit filteren
- [x] Matching op overlap in `volledige_tekst` (RSS-omschrijving), niet de titel
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

### Nog open, ongeacht bouwblok
- [ ] Een werkende bron met oriëntatie `left` vinden, zodat het spectrum weer klopt
- [ ] Overwegen of de volledige artikeltekst gescraped moet worden naast de RSS-samenvatting
- [x] `volledige_tekst` van de later toegevoegde wereldwijde en lokale feeds controleren, niet alleen de titel of status 200
- [ ] Prompt caching
- [ ] JSON-format best practices in de samenvattingsprompt