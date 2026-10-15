# Analyse van MediaLens

Een analyse van de applicatie zoals die er na bouwblok 18 uitziet: wat MediaLens doet, hoe het technisch werkt, wat de toets van de oriëntatielabels opleverde, en wat er bij een analyse van de code opviel.

De schermflow, wireframes en systeemdocumentatie staan in Whimsical:

| Bord | Inhoud |
|---|---|
| [Schermflow](https://whimsical.com/XLiinR7RzVycN7qhuSqC8j) | Alle pagina's en de links ertussen, met het hoofdpad Home → Gebeurtenis → bron |
| [Wireframes](https://whimsical.com/9E3kn34v9AC6X7D4MSzKF9) | Home, Gebeurtenis, Bron en Alle gebeurtenissen |
| [Systeemdocumentatie](https://whimsical.com/X61Kj9QiipYssucXJ6R8y2) | Pipeline, datamodel, onderdelen, externe diensten en stack |

Stand van de data bij deze analyse (15-10-2026): 124 bronnen, 5.777 artikelen, 3.542 gebeurtenissen waarvan 564 zichtbaar (2+ artikelen), 440 samenvattingen, 614 artikelen met een AI-schatting van de oriëntatie.

## 1. Doel en doelgroep

MediaLens laat zien hoe verschillende kanten hetzelfde nieuws brengen. Het verzamelt artikelen uit 124 Engelstalige nieuwsbronnen, groepeert artikelen over dezelfde gebeurtenis, en zet ze naast elkaar in drie kolommen: Links, Midden en Rechts. Een AI-samenvatting geeft per gebeurtenis de kernfeiten en beschrijft waar bronnen in schrijfwijze overeenkomen en verschillen.

De doelgroep is een lezer die niet één bron wil volgen, maar wil zien wat andere kanten over hetzelfde nieuws schrijven, en wat een kant weglaat (de blindspot). Het voorbeeld is [Ground News](https://ground.news).

MediaLens is gebouwd als casus bij Educom. Het is een website om te lezen: er zijn geen accounts en bezoekers kunnen niets invoeren behalve zoektermen en filters.

## 2. Functionele beschrijving

### Pagina's

| Pagina | URL | Wat de bezoeker ziet en doet |
|---|---|---|
| Home | `/` | Hot (meeste artikelen in 48 uur), Blindspot (vooral links of vooral rechts, laatste 7 dagen), per onderwerp de nieuwste gebeurtenissen, gebeurtenissen met de meeste artikelen |
| Onderwerp | `/onderwerpen/{onderwerp}` | Gebeurtenissen van één onderwerp (Wereld, Politiek, Economie, Tech & Wetenschap, Sport), met 3 trending bovenaan |
| Alle gebeurtenissen | `/gebeurtenissen` | Lijst met zoeken (`?q=`), filter op wel/geen samenvatting en op perspectief |
| Gebeurtenis | `/gebeurtenissen/{id}` | Artikelen in de kolommen Links / Midden / Rechts (3 per kolom, rest achter "Toon alle"), de samenvatting, tot 3 gerelateerde gebeurtenissen |
| Artikelen | `/artikelen` | Alle losse artikelen, met zoeken en filters op gekoppeld/los en perspectief |
| Bronnen | `/bronnen` | Alle bronnen per Links / Midden / Rechts, met betrouwbaarheid en aantal artikelen |
| Bron | `/bronnen/{id}` | MBFC-label naast de AI-schatting, betrouwbaarheid, tellingen, laatste 10 artikelen |
| Over | `/over` | Uitleg: wat labels, groepen, samenvatting en AI-schatting wel en niet betekenen |

### Schermflow

- Bovenaan elke pagina staan twee balken. De kopbalk heeft het logo (naar Home), Artikelen, Bronnen, Over MediaLens en de knop voor dark mode. De menubalk eronder heeft Home, de vijf onderwerpen en Alles (`/gebeurtenissen`). De footer linkt nog een keer naar Over ("Werkwijze en bronnen").
- Elke gebeurteniskaart (home, onderwerp, lijst, gerelateerd) gaat naar de gebeurtenispagina.
- Op de gebeurtenispagina gaat de titel van een artikel naar de site van de bron (nieuw tabblad), en de bronnaam naar de bronpagina.
- Op de bronpagina en `/artikelen` gaat een artikel naar zijn gebeurtenis, als het er een heeft.

Het hoofdpad is dus: **Home → Gebeurtenis → (artikel bij de bron) of Bron**.

### Wat een artikel toont
Titel, bron met favicon, tijd ("2 uur geleden" of een datum), het MBFC-label van de bron, en als er een schatting is: een uitklapbaar label "AI: … · zekerheid X%" met de onderbouwing.

## 3. Technische werking

### Stack
Laravel 13 op PHP 8.4+, MySQL, Blade met Tailwind CSS v4 en Vite. Ollama (lokaal, `nomic-embed-text`) voor embeddings. DeepSeek via de LiteLLM-proxy van Educom voor samenvattingen, onderwerpen en de oriëntatieschatting. PHPUnit-tests op SQLite in het geheugen, met alle externe diensten nagebootst.

### Pipeline

```
RSS-feeds (124)
  │  artikelen:ophalen          elk uur, 10 feeds tegelijk (Http::pool)
  ▼
artikelen                       tekst opgeschoond, alleen http(s)-links, dubbele URL's overgeslagen
  │  GebeurtenisMatcher         embedding per artikel (Ollama), terugval op RAKE-trefwoorden
  ▼
gebeurtenissen                  koppelen aan het meest gelijkende artikel binnen 48 uur (≥ 0,85),
  │                             anders een nieuwe gebeurtenis; centrale titel en gemiddelde embedding bijwerken
  │  samenvattingen:genereren   elk uur, na het ophalen
  ▼
samenvattingen + categorie      alleen bij 2+ artikelen, opnieuw zodra er een artikel bij komt
  │
  ▼
website                         kolommen op het MBFC-label van de bron

los daarvan, met de hand:
artikelen:orientatie-schatten → artikelen.ai_*   (AI-schatting per artikel)
orientatie:vergelijken                            (MBFC tegen AI, alleen database)
```

### Onderdelen

| Onderdeel | Bestand | Taak |
|---|---|---|
| Ophalen | `app/Console/Commands/ArtikelenOphalen.php` | Feeds parallel ophalen, XML lezen, tekst schoonmaken, afbeelding zoeken, artikel opslaan |
| Matcher | `app/Services/GebeurtenisMatcher.php` | Embeddings maken, beste kandidaat kiezen, gebeurtenis aanmaken of bijwerken, gerelateerde gebeurtenissen |
| Embeddings | `app/Services/Ollama.php` | Batch van teksten naar `/api/embed` |
| Taalmodel | `app/Services/LiteLlm.php` | Chat-aanroep met `json_schema`, kosten per aanroep in de log |
| Samenvatting | `app/Services/ArtikelVergelijker.php` | Prompt met alle artikelen van een gebeurtenis, vier velden plus categorie, validatie |
| Oriëntatie | `app/Services/OrientatieSchatter.php` | Prompt met één artikel, zonder bronnaam of label; zekerheid begrensd in de code |
| Planning | `routes/console.php` | `artikelen:ophalen` en `samenvattingen:genereren` elk uur, zonder overlap |

### Datamodel

```
bronnen 1 ──── * artikelen * ──── 0..1 gebeurtenissen 1 ──── 0..1 samenvattingen
```

| Tabel | Belangrijkste kolommen |
|---|---|
| `bronnen` | `naam`, `feed_url`, `orientatie` (MBFC: `left` … `right`), `betrouwbaarheid` (`high` / `mixed` / `low`) |
| `artikelen` | `titel`, `publicatiedatum`, `volledige_tekst` (RSS-omschrijving), `url`, `afbeelding_url`, `afbeelding_credit`, `embedding` (JSON, 768 getallen), `bron_id`, `gebeurtenis_id` (nullable), `ai_orientatie`, `ai_politiek`, `ai_zekerheid`, `ai_onderbouwing`, `ai_model`, `ai_geschat_op` |
| `gebeurtenissen` | `onderwerp` (titel van het meest centrale artikel), `categorie`, `embedding` (gemiddelde van de artikelen) |
| `samenvattingen` | `kernfeiten`, `betrokkenen`, `overeenstemming`, `verschil`, `model`, `gebeurtenis_id` |

De indeling Links / Midden / Rechts is geen kolom. Die wordt bij het tonen afgeleid uit `bronnen.orientatie`.

### Externe diensten

| Dienst | Waarvoor | Als hij niet werkt |
|---|---|---|
| RSS-feeds van 124 bronnen | Artikelen | Die bron wordt overgeslagen, de rest gaat door |
| Ollama (lokaal) | Embeddings | Terugval op RAKE-trefwoorden; later bijwerken met `artikelen:embeddings` |
| LiteLLM-proxy (Educom) met DeepSeek | Samenvatting, categorie, oriëntatie | 3 pogingen, daarna overslaan; de volgende run probeert het opnieuw |
| Favicons van de bronsites | Logo bij een artikel | Initialen in plaats van een logo |

## 4. De oriëntatietoets

### Vraag
De kolommen gebruiken het MBFC-label van de bron. Dat is een oordeel over een hele redactie, niet over één artikel. Klopt dat label met wat er in de artikelen staat?

### Methode
- Een taalmodel schat per artikel de oriëntatie op dezelfde schaal als MBFC, met een zekerheid en een onderbouwing.
- Het model krijgt alleen titel en tekst. De bronnaam en het MBFC-label gaan niet mee, anders meet je of het model MBFC kent.
- Artikelen zonder politieke lading (sport, bedrijfsnieuws) tellen niet mee voor de bron. De zekerheid is hooguit 40% bij geen lading of een tekst van minder dan 300 tekens.
- Per bron het gemiddelde van de politiek geladen artikelen, gewogen naar zekerheid (`left` = -2 … `right` = +2).
- `orientatie:vergelijken` zet dat naast MBFC: per bron, per MBFC-bakje en als matrix per artikel.

Steekproef: 614 artikelen, nieuwste eerst, 68 bronnen met minstens één politiek geladen artikel.

### Uitkomst

| MBFC | Bronnen | AI in hetzelfde bakje | Gemiddelde afwijking |
|---|---|---|---|
| Links | 39 | 38% | +0,9 |
| Midden | 10 | 80% | -0,3 |
| Rechts | 19 | 47% | -1,2 |
| Totaal | 68 | 47% | +0,1 |

| MBFC-bakje van de bron \ AI per artikel | Links | Midden | Rechts | Geen lading |
|---|---|---|---|---|
| Links | 95 | 141 | 6 | 108 |
| Midden | 6 | 25 | 0 | 31 |
| Rechts | 16 | 63 | 46 | 77 |

### Conclusie
- De AI trekt beide kanten naar het midden: 57% van de politiek geladen artikelen wordt `neutral`. De RSS-tekst is meestal de feitelijke eerste alinea, en MBFC beoordeelt ook welke onderwerpen een redactie kiest. Die twee meten dus niet hetzelfde.
- Dat de AI meer artikelen links dan rechts schat (116 tegen 52), komt vooral door de bronnenmix: 350 artikelen van linkse tegen 202 van rechtse bronnen.
- Daarbovenop is er een lichte trek naar links: bij rechtse bronnen schat de AI 13% van de geladen artikelen links, bij linkse bronnen 3% rechts. Bias van het model is daarmee niet bewezen, maar ook niet uitgesloten.
- Het MBFC-label blijft de basis voor de kolommen. De AI-schatting staat ernaast als aanwijzing, niet als correctie.

## 5. Bevindingen uit de code-analyse

Deze bevindingen komen uit een analyse van de code door Claude (AI), met de cijfers gecontroleerd op de echte database. Ze zijn nog niet allemaal door mij nagelopen. De kolom "Nagelopen" vul ik in na controle, met wat de AI goed of fout zag.

### Wat goed in elkaar zit
- **Elke externe dienst mag uitvallen.** Een kapotte feed stopt de rest niet, zonder Ollama valt de matcher terug op RAKE, en AI-aanroepen hebben 3 pogingen.
- **AI-uitvoer wordt niet vertrouwd.** Elk veld wordt in PHP gecontroleerd, ook als het `json_schema` het al afdwingt; grenzen uit de prompt (zekerheid ≤ 40) worden in de code afgedwongen.
- **Klein aanvalsoppervlak.** Alleen GET-routes, geen accounts, geen formulieren. `javascript:`-links uit feeds worden geweerd en `%` en `_` in zoektermen tellen letterlijk.
- **Keuzes staan bij de code.** De drempels van de matcher hebben een PHPDoc met de meting en de notitie waar die vandaan komt.

### Data

| # | Bevinding | Gevolg | Mogelijke oplossing | Nagelopen |
|---|---|---|---|---|
| D1 | De feed van **Daily Mail: World** levert oude artikelen: 91 artikelen van vóór 2026, het oudste van 17-06-2011. `artikelen:ophalen` accepteert elke datum | Oude artikelen staan in de database en kunnen onderling een gebeurtenis vormen. In de lijsten vallen ze weg omdat die op nieuwste staan | Items ouder dan een paar dagen overslaan bij het ophalen | |
| D2 | De grootste gebeurtenis heeft **124 artikelen** ("Deadly Saudi airport strikes…"), daarna 88 en 82. De matcher vergelijkt met het beste losse artikel, dus een grote groep heeft veel ingangen | Verhalen over hetzelfde onderwerp maar een ander feit groeien aan elkaar vast (in notitie 17 gemeten op 1,7%, maar het effect zit vooral in deze grote groepen) | Extra eis: het artikel moet ook op het groepsgemiddelde lijken | |
| D3 | De samenvatting stuurt **alle** artikelen van een gebeurtenis mee, bij gebeurtenis 239 ±47.000 tekens, en wordt opnieuw gemaakt zodra er een artikel bij komt | Grote gebeurtenissen kosten elk uur een grote AI-aanroep; de prompt groeit mee | Per kolom alleen de nieuwste N artikelen meesturen, of niet vaker dan eens per X uur vernieuwen | |
| D4 | 124 zichtbare gebeurtenissen hebben geen samenvatting en geen categorie | Ze staan niet onder een onderwerp op de home. Waarschijnlijk een achterstand of mislukte aanroepen; nog niet onderzocht | In de log kijken (`llm usage`, fouten) welke het zijn | |

### Snelheid bij groei

| # | Bevinding | Gevolg | Mogelijke oplossing | Nagelopen |
|---|---|---|---|---|
| S1 | `samenvattingen:genereren` loopt elk uur **alle** 3.542 gebeurtenissen langs, ook de ±3.000 met één artikel, met 1–2 queries per gebeurtenis | Zinloos werk dat elk uur groeit | In de query al `zichtbaar()` gebruiken | |
| S2 | De dubbelcheck `Artikel::where('url', $url)` draait per feed-item, maar `url` (tot 2048 tekens) heeft geen index | Elke run ±3.000 keer de hele tabel doorzoeken; wordt trager naarmate de database groeit | Kolom met een hash van de URL, met een unieke index | |
| S3 | `publicatiedatum` heeft geen index, terwijl het matchvenster, Hot, de blindspot en alle lijsten erop filteren of sorteren | Zelfde: trager bij groei | Index op `publicatiedatum` | |
| S4 | De gebeurtenispagina laadt alle kolommen van elk artikel, ook de embedding (768 getallen als JSON) | Bij 124 artikelen worden 124 vectoren uit MySQL gehaald die de pagina niet gebruikt. In notitie 17 bleek dat transport het dure deel | Kolommen selecteren zonder `embedding`, zoals `voorKaart()` al doet | |
### Code

| # | Bevinding | Gevolg | Mogelijke oplossing | Nagelopen |
|---|---|---|---|---|
| C1 | De koppeling Links / Midden / Rechts ↔ MBFC-labels staat op **4 plekken**: `Bron::bakjeVan()`, `Gebeurtenis::scopeVoorKaart()`, `ArtikelController` en `GebeurtenisController` | Een wijziging in de indeling moet op 4 plekken tegelijk, anders tellen kaarten en filters anders dan de kolommen | Eén methode die per bakje de labels geeft, en die overal gebruiken | |
| C2 | Niet overal return types: `GebeurtenisController`, `HomeController`, `OverController`, de relaties op de modellen, `SamenvattingenGenereren::handle()` | Ongelijk aan de rest van de code en aan de projectregels | Return types toevoegen | |
| C3 | In `LiteLlm::vraag()` is `$systeem !== null` altijd waar (het is een `string`), wordt de URL en key opnieuw uit `config()` gelezen in plaats van de gecontroleerde variabelen, en wordt `$errorBody` niet gebruikt | Geen fout, wel verwarrend | Opruimen. Gedaan in bouwblok 19, bij het herschrijven van `LiteLlm` | |
| C4 | `resources/views/welcome.blade.php` (standaard van Laravel) verwijst naar de routes `login` en `register`, die niet bestaan. Ook het model `User` en de tabel `users` worden niet gebruikt | Dode code; de view crasht als iemand hem ooit gebruikt | Verwijderen | |

## 6. Beperkingen en vervolgstappen

### Beperkingen
- Labels gelden per bron (MBFC), niet per artikel; de AI-schatting is een toets, geen vervanging.
- RSS geeft meestal alleen een korte omschrijving. Samenvatting en oriëntatieschatting zien niet het hele artikel.
- Groeperen is gelijkenis binnen 48 uur, geen bewijs van hetzelfde feit.
- De samenvatting en de schatting komen van één model (DeepSeek), dat zelf een bias kan hebben.
- MediaLens volgt meer linkse (75) dan rechtse (31) bronnen. Blindspot en tellingen zeggen iets over die mix.
- Alleen Engelstalige RSS 2.0-feeds.

### Vervolgstappen
1. De bevindingen uit hoofdstuk 5 nalopen en de terechte oplossen, te beginnen met D1, D3, S1 en C1.
2. De oriëntatieschatting op meer artikelen draaien, gelijk verdeeld over de bronnen, en met een tweede model ernaast om bias van DeepSeek te meten.
3. Volledige artikeltekst (scraping of een nieuws-API) in plaats van alleen de RSS-omschrijving.
4. Een tijdlijn voor nieuws dat langer dan 48 uur doorloopt.
5. Atom-feeds ondersteunen.
