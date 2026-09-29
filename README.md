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
7. `php artisan serve` om te checken of alles werkt

De database hoeft niet handmatig overgezet te worden tussen machines, migraties en seeders bouwen hem overal identiek op.

## RSS-bronnen

De bronkleur-tabel wordt gevuld via `database/seeders/BronSeeder.php`. Oriëntatie komt uit de Media Bias Fact Check dataset. Momenteel actief:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The Hill | `https://thehill.com/news/feed/` | neutral |
| The Nation | `https://www.thenation.com/feed/?post_type=article` | left |
| Fox News: Politics | `https://moxie.foxnews.com/google-publisher/politics.xml` | right |

Uitgecommentarieerd in de seeder, nog niet in gebruik:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The New York Times: Politics | `https://rss.nytimes.com/services/xml/rss/nyt/Politics.xml` | left-center |
| Politico | `https://rss.politico.com/politics-news.xml` | left-center |
| The Washington Post: Politics | `https://feeds.washingtonpost.com/rss/politics` | left-center |
| Drudge Report | `https://feedpress.me/drudgereportfeed` | right-center |
| The Daily Signal | `https://www.dailysignal.com/feed/` | right |
| Washington Examiner | `https://www.washingtonexaminer.com/feed/` | right |
| National Review | `https://www.nationalreview.com/feed/` | right |

Bekende dode of onbetrouwbare feeds, niet gebruiken:
- CNN (`rss.cnn.com/rss/edition.rss`), geeft status 200 maar bevat bevroren data uit 2023
- The Wall Street Journal (oude `online.wsj.com` link werkt niet meer)
- HuffPost, The Guardian, Reuters, The Economist, New York Post, The Times (UK), MSNBC, Breitbart: nog niet bevestigd werkend

Let op: geen enkele bron mag zomaar toegevoegd worden zonder oriëntatie uit de dataset, en de tabel moet klein en zelf uitlegbaar blijven, dat is een expliciete eis uit de casus.

## Bekende beperkingen

- Een RSS-feed levert meestal alleen titel en een korte samenvatting, niet de volledige artikeltekst. De kolom `volledige_tekst` is dus voorlopig geen echte volledige tekst.
- Bij het testen van een nieuwe feed altijd de publicatiedatums van de opgehaalde artikelen controleren, een status 200 zegt niets over of de feed nog actief bijgewerkt wordt.

## Status en to-do

### Basisopzet

- [x] Laravel geïnstalleerd en lokaal draaiend
- [x] GitHub repo aangemaakt en gesynchroniseerd
- [x] `.env` en lokale database ingesteld
- [ ] Clonen en testen op tweede machine (laptop/pc)

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
- [ ] Meer bronnen met werkende feeds toevoegen (zie RSS-bronnen hierboven)

### Bouwblok 3: Gebeurtenissen groeperen

- [ ] Service-klasse aanmaken, bijvoorbeeld `app/Services/GebeurtenisMatcher.php`
- [ ] Aanroepen direct na het opslaan van een nieuw artikel in het command
- [ ] Eenvoudige matching bouwen op basis van overlappende trefwoorden in de titel
- [ ] Bepalen vanaf welke mate van overlap een artikel bij een bestaande gebeurtenis hoort
- [ ] Nieuwe gebeurtenis aanmaken als er geen match is
- [ ] `gebeurtenis_id` op het artikel updaten
- [ ] Testen met artikelen die duidelijk over hetzelfde gaan (bijvoorbeeld hetzelfde onderwerp bij twee bronnen)
- [ ] Testen met artikelen die duidelijk niet bij elkaar horen
- [ ] Resultaat controleren in phpMyAdmin: kloppen de groeperingen

### Bouwblok 4: Artikelen vergelijken

- [ ] AI-integratie opzetten (API key, configuratie in `.env`)
- [ ] Service-klasse aanmaken, bijvoorbeeld `app/Services/ArtikelVergelijker.php`
- [ ] Artikelen van een gebeurtenis ophalen (via de `artikelen()` relatie op Gebeurtenis)
- [ ] Prompt opstellen die vraagt om taalgebruik, benadrukte onderwerpen en behandelde actoren te benoemen
- [ ] AI-aanroep uitvoeren en het antwoord verwerken
- [ ] Testen met een gebeurtenis die artikelen van meerdere bronnen heeft
- [ ] Beoordelen of de output bruikbaar genoeg is als input voor bouwblok 5

### Bouwblok 5: Samenvatting genereren

- [ ] Bestaande AI-service uitbreiden met een prompt voor de neutrale samenvatting
- [ ] Prompt laten vragen om kernfeiten, betrokkenen, overeenstemming en verschil
- [ ] Antwoord verwerken tot de losse velden van het Samenvatting-model
- [ ] Samenvatting opslaan gekoppeld aan de gebeurtenis
- [ ] Testen of de samenvatting daadwerkelijk neutraal aanvoelt en geen belangrijk verschil mist
- [ ] Testen of de tekst begrijpelijk is voor een breed publiek, zoals de casus vraagt

### Nog open, ongeacht bouwblok

- [ ] Overwegen of de volledige artikeltekst gescraped moet worden naast de RSS-samenvatting
- [ ] Meer bronnen met werkende feeds vinden voor een bredere dekking van het politieke spectrum
