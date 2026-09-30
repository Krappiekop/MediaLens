# MediaLens
AI-ondersteund systeem dat nieuwsartikelen over dezelfde gebeurtenis uit meerdere bronnen verzamelt, elke bron een politieke oriëntatie meegeeft, en een neutrale samenvatting genereert die laat zien waar bronnen van mening verschillen. Backend-only in deze fase, gebouwd in Laravel.

## Tech stack
- Laravel (laravel/laravel), lokaal via XAMPP
- MySQL database, lokaal beheerd via phpMyAdmin
- PHP 8.5

## Opzetten op een nieuwe machine
1. Repo clonen
2. `composer install`
3. `.env.example` kopiëren naar `.env` en de database-instellingen invullen met je eigen lokale database naam, gebruiker en wachtwoord
4. `php artisan key:generate`
5. Database `medialens` aanmaken in phpMyAdmin
6. `php artisan migrate:fresh --seed`
7. `php artisan artikelen:ophalen` om artikelen binnen te halen en te groeperen
8. `php artisan serve` om te checken of alles werkt

De database hoeft niet handmatig overgezet te worden tussen machines, migraties en seeders bouwen hem overal identiek op.

## RSS-bronnen
De bronnentabel wordt gevuld via `database/seeders/BronSeeder.php`. Oriëntatie komt uit de Media Bias Fact Check dataset. Momenteel actief:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The Hill | `https://thehill.com/news/feed/` | neutral |
| The New York Times: Politics | `https://rss.nytimes.com/services/xml/rss/nyt/Politics.xml` | left-center |
| The Washington Post: Politics | `https://feeds.washingtonpost.com/rss/politics` | left-center |
| The Daily Signal | `https://www.dailysignal.com/feed/` | right |
| Fox News: Politics | `https://moxie.foxnews.com/google-publisher/politics.xml` | right |

Uitgecommentarieerd in de seeder, nog niet in gebruik:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| Politico | `https://rss.politico.com/politics-news.xml` | left-center |
| Washington Examiner | `https://www.washingtonexaminer.com/feed/` | right |
| National Review | `https://www.nationalreview.com/feed/` | right |

Onbruikbaar gebleken, niet gebruiken:
- CNN (`rss.cnn.com/rss/edition.rss`): status 200, maar bevroren data uit 2023
- The Wall Street Journal: oude `online.wsj.com`-link werkt niet meer
- Drudge Report (`feedpress.me/drudgereportfeed`): `description` is pagina-HTML (Patreon, andere sites, feedpress-GIF), niet de artikeltekst. Matching op die kolom groepeert dan alle Drudge-items op de boilerplate
- The Nation (`thenation.com/feed/`): `description` is layout-HTML plus “appeared first on The Nation”, eveneens onbruikbaar voor matching
- HuffPost, The Guardian, Reuters, The Economist, New York Post, The Times (UK), MSNBC, Breitbart: nog niet bevestigd werkend

Er is nu geen bron met oriëntatie `left` en geen `right-center`. Hill is neutral, NYT en Washington Post zijn left-center, Daily Signal en Fox zijn right. Dat is een gat in het spectrum. Bouwblok 4 en 5 kunnen daardoor minder “links versus rechts” laten zien.

Let op: Bij een nieuwe feed altijd de `volledige_tekst` van een paar items openen, niet alleen de titel of de HTTP-status.

## Bekende beperkingen
- Een RSS-feed levert meestal alleen titel en een korte samenvatting, niet de volledige artikeltekst. De kolom `volledige_tekst` is dus de RSS-omschrijving.
- Status 200 zegt niets over of de feed actueel is. Altijd publicatiedatums controleren.
- Status 200 zegt ook niets over de inhoud van `description`. Die kan HTML-layout zijn in plaats van artikeltekst. `strip_tags` haalt tags weg, niet herhaalde reclamezinnen.
- Matching gebeurt op `volledige_tekst`, niet op de titel. Koppen over dezelfde gebeurtenis verschillen te sterk per bron. Dat wijkt af van de oorspronkelijke casusformulering (trefwoorden in de titel).
- Overlapdrempel is 6 trefwoorden, tijdvenster max 3 dagen. Twee gedeelde woorden was te streng op titels en te los op omschrijvingen.
- Trefwoordmatching blijft simpel: de eerste kandidaat die de drempel haalt wint. Daardoor kan een artikel in een groep belanden via ketting-matching, zonder dat het over hetzelfde nieuwsfeit gaat. Voorbeeld: in een SCOTUS-groep over third-country deportations zat ook een artikel over student loans, via gedeelde woorden als Trump of administration.
- Artikelen worden alleen bij het ophalen of bij een handmatige `koppel()`-run gematcht. Na een wijziging in de matcher: `gebeurtenis_id` leegmaken, `gebeurtenissen` legen, opnieuw koppelen.
- De oriëntatie komt van een dataset en is een generalisatie per bron, niet per artikel.
- De AI (bouwblok 4 en 5) kan zelf ook vooringenomen zijn.

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

### Bouwblok 3: Gebeurtenissen groeperen
- [x] Service-klasse `app/Services/GebeurtenisMatcher.php`
- [x] Aanroepen direct na het opslaan van een nieuw artikel in het command
- [x] Stopwoorden eruit filteren
- [x] Matching op overlap in `volledige_tekst` (RSS-omschrijving), niet de titel
- [x] HTML uit de tekst strippen voor de trefwoorden
- [x] Gebeurtenis krijgt als `onderwerp` de titel van het eerste artikel
- [x] Groepering binnen max 3 dagen
- [x] Drempel: minstens 6 gedeelde trefwoorden
- [x] Nieuwe gebeurtenis aanmaken als er geen match is
- [x] `gebeurtenis_id` op het artikel updaten
- [x] Getest met artikelen over hetzelfde feit bij meerdere bronnen (SCOTUS third-country deportations)
- [x] Getest dat duidelijk andere onderwerpen meestal niet bij elkaar horen
- [x] Resultaat gecontroleerd in phpMyAdmin

### Bouwblok 4: Artikelen vergelijken
- [ ] AI-integratie opzetten (API key, configuratie in `.env`)
- [ ] Service-klasse aanmaken, bijvoorbeeld `app/Services/ArtikelVergelijker.php`
- [ ] Artikelen van een gebeurtenis ophalen (via de `artikelen()` relatie op Gebeurtenis)
- [ ] Prompt opstellen die vraagt om taalgebruik, benadrukte onderwerpen en behandelde actoren te benoemen
- [ ] AI-aanroep uitvoeren en het antwoord verwerken
- [ ] Testen met een gebeurtenis die artikelen van meerdere bronnen heeft
- [ ] Beoordelen of de output bruikbaar genoeg is als input voor bouwblok 5

### Bouwblok 5: Samenvatting genereren
- [ ] Artisan command om een samenvatting te genereren (bijvoorbeeld samenvattingen:genereren)
- [ ] Bestaande AI-service uitbreiden met een prompt voor de neutrale samenvatting
- [ ] Prompt laten vragen om kernfeiten, betrokkenen, overeenstemming en verschil
- [ ] Antwoord verwerken tot de losse velden van het Samenvatting-model
- [ ] Samenvatting opslaan gekoppeld aan de gebeurtenis
- [ ] Alleen samenvatten als er nog geen samenvatting is, of als er nieuwe artikelen bij zijn gekomen
- [ ] Testen of de samenvatting daadwerkelijk neutraal aanvoelt en geen belangrijk verschil mist
- [ ] Testen of de tekst begrijpelijk is voor een breed publiek, zoals de casus vraagt

### Nog open, ongeacht bouwblok
- [ ] Een werkende bron met oriëntatie `left` vinden, zodat het spectrum weer klopt
- [ ] Overwegen of de volledige artikeltekst gescraped moet worden naast de RSS-samenvatting
- [ ] Eventueel een `right-center` bron zoeken als vervanging van Drudge
