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
2. **Groeperen.** Elk nieuw artikel krijgt een *embedding*: een reeks getallen die de betekenis van de tekst weergeeft, gemaakt door een lokaal model (`nomic-embed-text` via [Ollama](https://ollama.com)). Het artikel komt bij de gebeurtenis van het artikel uit de 48 uur ervoor of erna dat er het meest op lijkt. Lijkt niets genoeg, dan begint het een nieuwe gebeurtenis. Draait Ollama niet, dan valt MediaLens terug op gedeelde trefwoorden (RAKE).
3. **Samenvatten.** Voor elke gebeurtenis met minstens twee artikelen schrijft een taalmodel (DeepSeek, via de Educom-proxy) een samenvatting in vier velden en kiest het een onderwerp, zoals Politiek of Wereld.
4. **Tonen.** De website zet de artikelen per gebeurtenis in drie kolommen, op basis van het label van hun bron.
5. **Toetsen.** Los van de kolommen kan hetzelfde taalmodel de oriëntatie van losse artikelen schatten, met een zekerheid en een onderbouwing. Die schatting staat naast het MBFC-label, als controle. De indeling in kolommen blijft het MBFC-label.

## Pagina's

| URL | Wat je ziet |
|---|---|
| `/` | Homepage: **Hot** (meeste artikelen in de laatste 48 uur), **Blindspot** (gebeurtenissen die bijna alleen door links of door rechts worden gebracht), per onderwerp de nieuwste gebeurtenissen, en de gebeurtenissen met de meeste artikelen |
| `/onderwerpen/{onderwerp}` | Eén onderwerp (bijv. `/onderwerpen/politiek`), met bovenaan de 3 trending gebeurtenissen |
| `/gebeurtenissen` | Alle gebeurtenissen, met zoeken en filters op samenvatting en perspectief |
| `/gebeurtenissen/{id}` | Eén gebeurtenis: de artikelen in de kolommen Links, Midden en Rechts, de samenvatting en tot 3 gerelateerde gebeurtenissen |
| `/artikelen` | Alle losse artikelen, ook die nog niet bij een gebeurtenis horen |
| `/bronnen` | Alle bronnen per Links, Midden en Rechts, met hun betrouwbaarheid |
| `/bronnen/{id}` | Eén bron: MBFC-label naast de AI-schatting, betrouwbaarheid, aantal artikelen en gebeurtenissen, en de laatste artikelen |
| `/over` | Uitleg voor bezoekers: wat de labels, groepen, samenvatting en AI-schatting wel en niet betekenen |

Bij elk artikel met een schatting staat een uitklapbaar label "AI: <label> · zekerheid X%" met de onderbouwing.

## Tech stack

- **Laravel 13** op **PHP 8.4+**, met **MySQL** (lokaal via XAMPP en phpMyAdmin)
- **Blade**, **Tailwind CSS** en **Vite** voor de frontend
- **Ollama** met `nomic-embed-text` voor embeddings, lokaal en gratis
- **LiteLLM-proxy** (Educom) met **DeepSeek** voor samenvattingen, onderwerpen en de oriëntatieschatting per artikel
- [`donatello-za/rake-php-plus`](https://github.com/Donatello-za/rake-php-plus) voor trefwoorden, als terugval bij het groeperen
- **PHPUnit** voor de tests
- Voor AI-assistenten staan in `.mcp.json` twee MCP-servers: **Laravel Boost** (database, logs, documentatie) en **Whimsical** (de borden bij `ANALYSE.md`)

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

Wil je dat MediaLens elk uur zelf nieuwe artikelen ophaalt en samenvat? Laat dan in een extra terminal de scheduler draaien:

```bash
php artisan schedule:work
```

Op een server doe je dat met één cron-regel die elke minuut `php artisan schedule:run` draait.

> **XAMPP:** de Apache van XAMPP draait PHP 8.2 en kan Laravel 13 niet starten. Gebruik `php artisan serve` (of `composer run dev`) met een PHP 8.4+ op de command line. phpMyAdmin via XAMPP werkt gewoon.

## Commands

| Command | Wat het doet |
|---|---|
| `php artisan artikelen:ophalen` | Haalt alle feeds op (10 tegelijk), slaat nieuwe artikelen op, maakt embeddings en koppelt ze aan een gebeurtenis. Een artikel dat een eerdere run zonder gebeurtenis liet staan, wordt alsnog gekoppeld. Draait elk uur via de scheduler |
| `php artisan samenvattingen:genereren {id?}` | Maakt of vernieuwt samenvattingen voor gebeurtenissen met 2+ artikelen, en kiest het onderwerp. Draait elk uur via de scheduler, na het ophalen |
| `php artisan gebeurtenissen:categoriseren {id?}` | Deelt gebeurtenissen zonder onderwerp alsnog in |
| `php artisan artikelen:embeddings` | Maakt embeddings voor bestaande artikelen die er nog geen hebben |
| `php artisan gebeurtenissen:bijwerken {id?}` | Kiest de meest centrale titel als onderwerp en berekent de gemiddelde embedding (voor gerelateerde gebeurtenissen). Gebeurt bij het koppelen al vanzelf; dit is voor bestaande data |
| `php artisan artikelen:orientatie-schatten {--bron=} {--limit=20} {--opnieuw}` | Laat de AI de oriëntatie van de nieuwste artikelen zonder schatting schatten. Kost een AI-call per artikel, daarom een limiet. Draait niet via de scheduler |
| `php artisan orientatie:vergelijken {--min=1}` | Zet per bron het MBFC-label naast de AI-schatting: grootste afwijkingen, overeenstemming per bakje en een confusion matrix per artikel. Alleen uit de database, geen AI-calls |

## Configuratie

Alle instellingen staan in `.env`; de standaardwaarden staan in `.env.example`.

| Variabele | Standaard | Betekenis |
|---|---|---|
| `LITELLM_BASE_URL`, `LITELLM_API_KEY`, `LITELLM_MODEL` | Educom-proxy, DeepSeek | Taalmodel voor samenvattingen, onderwerpen en de oriëntatieschatting |
| `LITELLM_REASONING_EFFORT` | leeg | Hoeveel het model nadenkt: leeg = standaard van de provider, `uit`, of `minimal`, `low`, `medium`, `high`. Per soort aanroep te overschrijven met `LITELLM_REASONING_SAMENVATTING`, `_CATEGORIE` en `_ORIENTATIE` |
| `LITELLM_PRICE_INPUT`, `_OUTPUT`, `_CACHED` | DeepSeek-prijzen | Prijs per miljoen tokens, voor de kostenschatting in de log als OpenRouter zelf geen kosten meestuurt |
| `OLLAMA_BASE_URL` | `http://localhost:11434` | Adres van de lokale Ollama-server |
| `OLLAMA_EMBEDDING_MODEL` | `nomic-embed-text` | Model voor embeddings |
| `MATCHER_METHODE` | `embeddings` | `embeddings` of `rake` (alleen trefwoorden) |
| `MATCHER_MINIMALE_SIMILARITY` | `0.85` | Hoe sterk artikelen moeten lijken om samen een gebeurtenis te vormen (0 tot 1) |
| `MATCHER_MINIMALE_SIMILARITY_GERELATEERD` | `0.8` | Hoe sterk een andere gebeurtenis moet lijken om als gerelateerd te tonen (0 tot 1) |

Na een wijziging in `.env`: `php artisan config:clear`.

Elke AI-aanroep staat als `llm usage` in `storage/logs/laravel.log`: soort aanroep, provider, duur, tokens (ook uit de cache en voor het nadenken) en kosten.

## Tests

```bash
php artisan test
```

De tests draaien op een SQLite-database in het geheugen. Feeds, Ollama en de AI worden nagebootst met `Http::fake()`, dus er is geen internet, Ollama of API-key nodig. De tests schrijven niet in de log (`LOG_CHANNEL=null`).

Let op: de app zelf draait op MySQL. Wat in SQLite slaagt, kan in MySQL anders uitpakken (bijvoorbeeld rekenen met een `unsigned` kolom). Controleer database-werk daarom ook op de echte data.

## Branches

De repo gebruikt [git-flow](https://github.com/gittower/git-flow-next) (`git-flow-next`, de klassieke indeling):

- `main`: releases
- `develop`: integratie van afgeronde features
- `feature/…`, `bugfix/…`, `release/…`, `hotfix/…`: werk in uitvoering

Een nieuwe feature begin je met `git flow feature start <naam>` en rond je af met `git flow feature finish <naam>`.

## Beperkingen

MediaLens wil laten zien hoe bronnen verschillen, maar het systeem maakt zelf ook keuzes. De belangrijkste:

- **Labels gelden per bron, niet per artikel.** Ze komen uit een [dataset van Media Bias Fact Check](https://github.com/idiap/Factual-Reporting-and-Political-Bias-Web-Interactions/blob/main/data/mbfc.csv). 9 bronnen die daar niet in staan, zijn met de hand opgezocht op [mediabiasfactcheck.com](https://mediabiasfactcheck.com).
- **De AI-schatting per artikel is een toets, geen tweede waarheid.** Het model ziet alleen de korte RSS-tekst en schat 57% van de politiek geladen artikelen als `neutral`. In de steekproef (614 artikelen) kwam de AI bij 47% van de bronnen in hetzelfde bakje uit als MBFC. Het trekt beide kanten naar het midden, rechts iets sterker. Een lichte bias van het model naar links is dus niet uitgesloten.
- **Groepen zijn gelijkenis, geen waarheid.** Twee verhalen over hetzelfde onderwerp kunnen samen in één gebeurtenis komen. Artikelen worden alleen vergeleken met artikelen uit de 48 uur ervoor of erna; nieuws dat langer doorloopt, valt uiteen in meerdere gebeurtenissen.
- **De samenvatting komt van een taalmodel** en kan zelf vooringenomen zijn. Het model ziet de labels bewust niet.
- **RSS geeft meestal alleen een korte omschrijving**, geen volledig artikel. Wat daar niet in staat, kan de AI niet samenvatten.
- **Alleen Engelstalige RSS 2.0-feeds.** Atom-feeds worden nog niet gelezen.

## Meer lezen

| Bestand | Inhoud |
|---|---|
| [BRONNEN.md](BRONNEN.md) | Alle 124 bronnen met feed-URL en oriëntatie, hoe ze getest zijn, en welke feeds afvielen |
| [ONTWERPKEUZES.md](ONTWERPKEUZES.md) | Technische keuzes en beperkingen per onderdeel: feeds, groeperen, oriëntatie en AI-schatting, samenvatting, overzicht, detailpagina |
| [ANALYSE.md](ANALYSE.md) | Analyse van de applicatie: doel, pagina's en schermflow, technische werking, de toets van de oriëntatielabels, bevindingen uit de code-analyse. Met bovenaan de links naar de Whimsical-borden: [schermflow](https://whimsical.com/XLiinR7RzVycN7qhuSqC8j), [wireframes](https://whimsical.com/9E3kn34v9AC6X7D4MSzKF9) en [systeemdocumentatie](https://whimsical.com/X61Kj9QiipYssucXJ6R8y2) |
| [TODO.md](TODO.md) | Voortgang per fase en bouwblok, met alle open punten onder Volgende stappen |
| [notities/](notities/) | Mijn leernotities per bouwblok, ingedeeld in dezelfde fases als TODO.md: wat ik gebouwd heb en waarom |
