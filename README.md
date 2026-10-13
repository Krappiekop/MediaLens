# MediaLens

MediaLens verzamelt nieuwsartikelen uit 124 bronnen, groepeert artikelen over dezelfde gebeurtenis en zet ze naast elkaar op politieke oriëntatie: **Links**, **Midden** en **Rechts**. Een AI-samenvatting laat per gebeurtenis zien wat de kernfeiten zijn, wie erbij betrokken is, en waar bronnen in schrijfwijze overeenkomen of verschillen.

Het idee lijkt op [Ground News](https://ground.news): niet één bron lezen, maar zien hoe verschillende kanten hetzelfde nieuws brengen. MediaLens is gebouwd als casus bij Educom, in Laravel.

## Hoe het werkt

```
RSS-feeds ──► artikelen:ophalen ──► groeperen ──► samenvattingen:genereren ──► website
 (124)        tekst opschonen       embeddings      kernfeiten, betrokkenen,    Links / Midden /
              en opslaan            (Ollama)        overeenkomst, verschil,     Rechts per gebeurtenis
                                                    onderwerp (DeepSeek)
```

1. **Ophalen.** Een Artisan-command haalt elke RSS-feed op, haalt HTML en vaste reclamezinnen uit de tekst en slaat nieuwe artikelen op. Een URL die al bestaat, wordt overgeslagen.
2. **Groeperen.** Elk nieuw artikel krijgt een *embedding*: een reeks getallen die de betekenis van de tekst weergeeft, gemaakt door een lokaal model (`nomic-embed-text` via [Ollama](https://ollama.com)). Het artikel komt bij de gebeurtenis van het artikel van dezelfde dag dat er het meest op lijkt. Lijkt niets genoeg, dan begint het een nieuwe gebeurtenis. Draait Ollama niet, dan valt MediaLens terug op gedeelde trefwoorden (RAKE).
3. **Samenvatten.** Voor elke gebeurtenis met minstens twee artikelen schrijft een taalmodel (DeepSeek, via de Educom-proxy) een samenvatting in vier velden en kiest het een onderwerp, zoals Politiek of Wereld.
4. **Tonen.** De website zet de artikelen per gebeurtenis in drie kolommen, op basis van het label van hun bron.

## Pagina's

| URL | Wat je ziet |
|---|---|
| `/` | Homepage: **Hot** (meeste artikelen in de laatste 48 uur), per onderwerp de nieuwste gebeurtenissen, en de gebeurtenissen met de meeste artikelen |
| `/onderwerpen/{onderwerp}` | Eén onderwerp (bijv. `/onderwerpen/politiek`), met bovenaan de 3 trending gebeurtenissen |
| `/gebeurtenissen` | Alle gebeurtenissen, met zoeken en filters op samenvatting en perspectief |
| `/gebeurtenissen/{id}` | Eén gebeurtenis: samenvatting en de artikelen in de kolommen Links, Midden en Rechts |
| `/artikelen` | Alle losse artikelen, ook die nog niet bij een gebeurtenis horen |
| `/over` | Uitleg voor bezoekers: wat de labels, groepen en samenvatting wel en niet betekenen |

## Tech stack

- **Laravel 13** op **PHP 8.4+**, met **MySQL** (lokaal via XAMPP en phpMyAdmin)
- **Blade**, **Tailwind CSS** en **Vite** voor de frontend
- **Ollama** met `nomic-embed-text` voor embeddings, lokaal en gratis
- **LiteLLM-proxy** (Educom) met **DeepSeek** voor samenvattingen en onderwerpen
- [`donatello-za/rake-php-plus`](https://github.com/Donatello-za/rake-php-plus) voor trefwoorden, als terugval bij het groeperen
- **PHPUnit** voor de tests

## Installatie

Je hebt nodig: PHP 8.4 of hoger, Composer, Node.js, MySQL en [Ollama](https://ollama.com/download).

```bash
git clone https://github.com/Krappiekop/MediaLens.git
cd MediaLens
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Vul in `.env` in:
- de databasegegevens (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). Maak de database `medialens` eerst aan, bijvoorbeeld in phpMyAdmin;
- `LITELLM_API_KEY` voor de samenvattingen. Deze key hoort alleen in `.env`, nooit in git.

Haal het embedding-model op. De Ollama-app moet daarna op de achtergrond blijven draaien:

```bash
ollama pull nomic-embed-text
```

Bouw de database op en vul hem:

```bash
php artisan migrate --seed            # tabellen + 124 bronnen
php artisan artikelen:ophalen         # artikelen ophalen en groeperen
php artisan samenvattingen:genereren  # samenvattingen en onderwerpen via de AI
```

Start de site:

```bash
composer run dev
```

Dit start onder andere de Laravel-server en Vite tegelijk. Open daarna `http://127.0.0.1:8000`. Liever los? Draai `php artisan serve` en `npm run dev` in twee terminals.

> **XAMPP:** de Apache van XAMPP draait PHP 8.2 en kan Laravel 13 niet starten. Gebruik `php artisan serve` (of `composer run dev`) met een PHP 8.4+ op de command line. phpMyAdmin via XAMPP werkt gewoon.

## Commands

| Command | Wat het doet |
|---|---|
| `php artisan artikelen:ophalen` | Haalt alle feeds op, slaat nieuwe artikelen op, maakt embeddings en koppelt ze aan een gebeurtenis |
| `php artisan samenvattingen:genereren {id?}` | Maakt of vernieuwt samenvattingen voor gebeurtenissen met 2+ artikelen, en kiest het onderwerp |
| `php artisan gebeurtenissen:categoriseren {id?}` | Deelt gebeurtenissen zonder onderwerp alsnog in |
| `php artisan artikelen:embeddings` | Maakt embeddings voor bestaande artikelen die er nog geen hebben |

## Configuratie

Alle instellingen staan in `.env`; de standaardwaarden staan in `.env.example`.

| Variabele | Standaard | Betekenis |
|---|---|---|
| `LITELLM_BASE_URL`, `LITELLM_API_KEY`, `LITELLM_MODEL` | Educom-proxy, DeepSeek | Taalmodel voor samenvattingen |
| `OLLAMA_BASE_URL` | `http://localhost:11434` | Adres van de lokale Ollama-server |
| `OLLAMA_EMBEDDING_MODEL` | `nomic-embed-text` | Model voor embeddings |
| `MATCHER_METHODE` | `embeddings` | `embeddings` of `rake` (alleen trefwoorden) |
| `MATCHER_MINIMALE_SIMILARITY` | `0.85` | Hoe sterk artikelen moeten lijken om samen een gebeurtenis te vormen (0 tot 1) |

Na een wijziging in `.env`: `php artisan config:clear`.

## Tests

```bash
php artisan test
```

De tests draaien op een SQLite-database in het geheugen. Feeds, Ollama en de AI worden nagebootst met `Http::fake()`, dus er is geen internet, Ollama of API-key nodig.

## Beperkingen

MediaLens wil laten zien hoe bronnen verschillen, maar het systeem maakt zelf ook keuzes. De belangrijkste:

- **Labels gelden per bron, niet per artikel.** Ze komen uit een [dataset van Media Bias Fact Check](https://github.com/idiap/Factual-Reporting-and-Political-Bias-Web-Interactions/blob/main/data/mbfc.csv). Voor 9 bronnen die daar niet in staan, is het label een inschatting.
- **Groepen zijn gelijkenis, geen waarheid.** Twee verhalen over hetzelfde onderwerp kunnen samen in één gebeurtenis komen. Artikelen worden alleen binnen dezelfde kalenderdag vergeleken.
- **De samenvatting komt van een taalmodel** en kan zelf vooringenomen zijn. Het model ziet de labels bewust niet.
- **RSS geeft meestal alleen een korte omschrijving**, geen volledig artikel. Wat daar niet in staat, kan de AI niet samenvatten.
- **Alleen Engelstalige RSS 2.0-feeds.** Atom-feeds worden nog niet gelezen.

## Meer lezen

| Bestand | Inhoud |
|---|---|
| [BRONNEN.md](BRONNEN.md) | Alle 124 bronnen met feed-URL en oriëntatie, hoe ze getest zijn, en welke feeds afvielen |
| [ONTWERPKEUZES.md](ONTWERPKEUZES.md) | Technische keuzes en beperkingen per onderdeel: feeds, groeperen, samenvatting, overzicht, detailpagina |
| [TODO.md](TODO.md) | Voortgang per fase en bouwblok, met alle open punten onder Volgende stappen |
| [notities/](notities/) | Mijn leernotities per bouwblok, ingedeeld in dezelfde fases als TODO.md: wat ik gebouwd heb en waarom |
