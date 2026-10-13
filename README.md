# MediaLens
AI-ondersteund systeem dat nieuwsartikelen over dezelfde gebeurtenis uit meerdere bronnen verzamelt, elke bron een politieke oriëntatie meegeeft, en een samenvatting genereert: kernfeiten die zonder de artikelen leesbaar zijn, betrokkenen als namenlijst, en waar de schrijfwijze overeenkomt of door framing verschilt. Gebouwd in Laravel. Overzicht, detailpagina en `/over` staan in de browser.

## Tech stack
- Laravel (laravel/laravel), lokaal via XAMPP
- MySQL database, lokaal beheerd via phpMyAdmin
- PHP 8.5
- Node.js, Vite en Tailwind voor de layout. `npm run dev` naast `php artisan serve`
- `donatello-za/rake-php-plus` (v2) voor trefwoorden in `GebeurtenisMatcher`. Gewone Composer-library, geen Laravel-package. Komt mee met `composer install`.

## Opzetten op een nieuwe machine
1. Repo clonen
2. `composer install`, daarna `npm install`. Vite en Tailwind zitten in `package.json`. `node_modules` staat in `.gitignore`, dus die map komt niet mee met een clone.
3. `.env.example` kopiëren naar `.env` en de database-instellingen invullen met je eigen lokale database naam, gebruiker en wachtwoord. Vul ook `LITELLM_BASE_URL`, `LITELLM_API_KEY` en `LITELLM_MODEL` in. Die namen staan al in `.env.example`, met model `openrouter/deepseek/deepseek-v4-flash`. De key hoort alleen in `.env`, niet in git. Optioneel: `LITELLM_THINKING_TYPE`, `LITELLM_REASONING_EFFORT` en `LITELLM_TEMPERATURE`. Leeg laten gebruikt de DeepSeek-standaard (`enabled`, `high`, `1`), via `config/services.php`.
4. `php artisan key:generate`
5. Database `medialens` aanmaken in phpMyAdmin
6. `php artisan migrate:fresh --seed`
7. `php artisan artikelen:ophalen` om artikelen binnen te halen en te groeperen. Per bron eerst `Ophalen bij …`, daarna één regel: hoeveel nieuwe artikelen, hoeveel daarvan een nieuwe gebeurtenis openden, en hoeveel aan een bestaande gebeurtenis gekoppeld zijn. Een URL die al in de database staat telt niet mee.
8. `php artisan samenvattingen:genereren` voor alle gebeurtenissen, of `php artisan samenvattingen:genereren 5` voor één id. Dit zet ook de `categorie` van de gebeurtenis. Gebeurtenissen die al een samenvatting hebben, of er nog geen krijgen, deel je in met `php artisan gebeurtenissen:categoriseren` (of `gebeurtenissen:categoriseren 5` om één id opnieuw in te delen)
9. Controleren in de Artisan-output en in phpMyAdmin (`artikelen`, `gebeurtenissen`, `samenvattingen`)
10. `php artisan serve` en open `http://127.0.0.1:8000/` (homepage) of `http://127.0.0.1:8000/gebeurtenissen` (Alles). Onderwerpen staan op `/onderwerpen/{categorie}`, bijvoorbeeld `/onderwerpen/politiek`. De lijst toont 15 gebeurtenissen per pagina, nieuwste `artikelen.publicatiedatum` eerst. Filter Alle / Met samenvatting / Zonder samenvatting gaat via de querystring (`?filter=met` of `?filter=zonder`), zonder JavaScript. Een onderwerp opent `/gebeurtenissen/{id}`. Footer **Over** en de disclaimer onder de kolommen gaan naar `/over`. In een tweede terminal: `npm run dev`. Dat schrijft `public/hot` en levert de CSS. Zonder dat proces, en zonder `public/build/manifest.json` van `npm run build`, blijft de pagina HTML. De `@if` rond `@vite` in `resources/views/layouts/app.blade.php` voorkomt dan een `ViteManifestNotFoundException`. XAMPP Apache draait PHP 8.2 en start Laravel 13 niet. De CLI-PHP (8.4+) wel. phpMyAdmin via XAMPP blijft werken.

Of feeds en samenvattingen kloppen, zie je bij stap 7 tot en met 9. Of de lijst en de detailpagina kloppen, bij stap 10 plus phpMyAdmin (`gebeurtenissen`, `artikelen`, `samenvattingen`, `bronnen`). Tweede pagina en filter “zonder samenvatting” toetsen tegen dezelfde `ORDER BY` / `HAVING` in phpMyAdmin. `/over` is vaste uitleg, geen databasecheck.

De database hoeft niet handmatig overgezet te worden tussen machines, migraties en seeders bouwen hem overal identiek op.

## RSS-bronnen
De bronnentabel wordt gevuld via `database/seeders/BronSeeder.php`. De seeder zet 124 feeds in de database, gegroepeerd per regio of categorie en binnen elke groep van links naar rechts. De oorspronkelijke 22 bronnen hebben hun oriëntatie uit de Media Bias Fact Check dataset. Bij de 102 bronnen uit bouwblok 15 is de oriëntatie een inschatting op basis van AllSides en Media Bias Fact Check, nog niet per bron nagelopen.

Verdeling: 13 `left`, 51 `left-center`, 31 `neutral`, 8 `right-center`, 21 `right` (waarvan RT en Tehran Times staatsmedia zijn).

VS: politiek:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The Nation | `https://www.thenation.com/feed/?post_type=article` | left |
| Mother Jones | `https://www.motherjones.com/feed/` | left |
| The Intercept | `https://theintercept.com/feed/?rss` | left |
| The New Republic | `https://newrepublic.com/rss.xml` | left |
| Rolling Stone: Politics | `https://www.rollingstone.com/politics/feed/` | left |
| Talking Points Memo | `https://talkingpointsmemo.com/feed` | left |
| The New York Times: Politics | `https://rss.nytimes.com/services/xml/rss/nyt/Politics.xml` | left-center |
| The Washington Post: Politics | `https://feeds.washingtonpost.com/rss/politics` | left-center |
| NPR: Politics | `https://feeds.npr.org/1014/rss.xml` | left-center |
| ABC News (VS): Politics | `https://abcnews.go.com/abcnews/politicsheadlines` | left-center |
| CBS News: Politics | `https://www.cbsnews.com/latest/rss/politics` | left-center |
| NBC News: Politics | `https://feeds.nbcnews.com/nbcnews/public/politics` | left-center |
| LA Times: Politics | `https://www.latimes.com/politics/rss2.0.xml` | left-center |
| Bloomberg: Politics | `https://feeds.bloomberg.com/politics/news.rss` | left-center |
| Axios | `https://api.axios.com/feed/` | left-center |
| Mediaite | `https://www.mediaite.com/feed/` | left-center |
| The Hill | `https://thehill.com/news/feed/` | neutral |
| Roll Call | `https://rollcall.com/feed/` | neutral |
| Semafor | `https://www.semafor.com/rss.xml` | neutral |
| The Free Press | `https://www.thefp.com/feed` | right-center |
| The Daily Signal | `https://www.dailysignal.com/feed/` | right |
| Washington Examiner | `https://www.washingtonexaminer.com/feed/` | right |
| Washington Examiner: Policy | `https://www.washingtonexaminer.com/section/policy/feed/` | right |
| Fox News: Politics | `https://moxie.foxnews.com/google-publisher/politics.xml` | right |
| The Federalist | `https://thefederalist.com/feed/` | right |
| Washington Free Beacon | `https://freebeacon.com/feed/` | right |
| The American Spectator | `https://spectator.org/feed/` | right |
| Just the News | `https://justthenews.com/rss.xml` | right |

VS: algemeen nieuws:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| Democracy Now! | `https://www.democracynow.org/democracynow.rss` | left |
| Common Dreams | `https://www.commondreams.org/feeds/feed.rss` | left |
| Truthout | `https://truthout.org/feed/` | left |
| Raw Story | `https://www.rawstory.com/feeds/feed.rss` | left |
| AlterNet | `https://www.alternet.org/feeds/feed.rss` | left |
| The New Yorker: News | `https://www.newyorker.com/feed/news` | left |
| The New York Times | `https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml` | left-center |
| The Washington Post: National | `https://feeds.washingtonpost.com/rss/national` | left-center |
| NBC News | `https://feeds.nbcnews.com/nbcnews/public/news` | left-center |
| CBS News | `https://www.cbsnews.com/feeds/rss/main.rss` | left-center |
| ABC News (VS) | `https://abcnews.go.com/abcnews/topstories` | left-center |
| PBS NewsHour | `https://www.pbs.org/newshour/feeds/rss/headlines` | left-center |
| The 19th | `https://19thnews.org/feed/` | left-center |
| The Christian Science Monitor | `https://rss.csmonitor.com/feeds/all` | neutral |
| Newsweek | `https://www.newsweek.com/rss` | neutral |
| The Hill: Policy | `https://thehill.com/policy/feed/` | neutral |
| Military Times | `https://www.militarytimes.com/arc/outboundfeeds/rss/?outputType=xml` | neutral |
| New York Post | `https://nypost.com/feed` | right-center |
| The Washington Times | `https://www.washingtontimes.com/rss/headlines/news/` | right-center |
| Fox News: US | `https://moxie.foxnews.com/google-publisher/us.xml` | right |
| Fox Business | `https://moxie.foxbusiness.com/google-publisher/latest.xml` | right |
| Breitbart | `https://feeds.feedburner.com/breitbart` | right |
| The Daily Wire | `https://www.dailywire.com/feeds/rss.xml` | right |
| TheBlaze | `https://www.theblaze.com/feeds/feed.rss` | right |
| Newsmax | `https://www.newsmax.com/rss/Newsfront/16/` | right |

VS: lokaal en regionaal:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| LA Times | `https://www.latimes.com/local/rss2.0.xml` | left-center |
| CalMatters | `https://calmatters.org/feed/` | left-center |
| The Texas Tribune | `https://www.texastribune.org/feeds/main/` | neutral |

VS: wereldnieuws:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The New York Times: World | `https://rss.nytimes.com/services/xml/rss/nyt/World.xml` | left-center |
| The Washington Post: World | `https://feeds.washingtonpost.com/rss/world` | left-center |
| NPR: World | `https://feeds.npr.org/1004/rss.xml` | left-center |
| NBC News: World | `https://feeds.nbcnews.com/nbcnews/public/world` | left-center |
| CBS News: World | `https://www.cbsnews.com/latest/rss/world` | left-center |
| LA Times: World & Nation | `https://www.latimes.com/world-nation/rss2.0.xml` | left-center |
| CNBC: World | `https://www.cnbc.com/id/100727362/device/rss/rss.html` | neutral |
| The Hill: International | `https://thehill.com/policy/international/feed/` | neutral |
| Fox News: World | `https://moxie.foxnews.com/google-publisher/world.xml` | right |

VK en Ierland:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The Mirror | `https://www.mirror.co.uk/news/world-news/?service=rss` | left |
| BBC News: World | `https://feeds.bbci.co.uk/news/world/rss.xml` | left-center |
| BBC News: US & Canada | `https://feeds.bbci.co.uk/news/world/us_and_canada/rss.xml` | left-center |
| The Guardian: World | `https://www.theguardian.com/world/rss` | left-center |
| The Guardian: UK | `https://www.theguardian.com/uk-news/rss` | left-center |
| The Guardian: US | `https://www.theguardian.com/us-news/rss` | left-center |
| RTÉ News | `https://www.rte.ie/feeds/rss/?index=/news/` | left-center |
| Sky News: World | `https://feeds.skynews.com/feeds/rss/world.xml` | neutral |
| Sky News: UK | `https://feeds.skynews.com/feeds/rss/uk.xml` | neutral |
| Daily Mail: World | `https://www.dailymail.co.uk/news/worldnews/index.rss` | right |
| The Sun | `https://www.thesun.co.uk/news/feed/` | right |
| GB News | `https://www.gbnews.com/feeds/news.rss` | right |

Europa:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| Der Spiegel International | `https://www.spiegel.de/international/index.rss` | left-center |
| Le Monde (English) | `https://www.lemonde.fr/en/rss/une.xml` | left-center |
| RFI (English) | `https://www.rfi.fr/en/rss` | left-center |
| Kyiv Independent | `https://kyivindependent.com/news-archive/rss/` | left-center |
| The Moscow Times | `https://www.themoscowtimes.com/rss/news` | left-center |
| Meduza (English) | `https://meduza.io/rss/en/all` | left-center |
| France 24 | `https://www.france24.com/en/rss` | neutral |
| Deutsche Welle | `https://rss.dw.com/xml/rss-en-world` | neutral |
| Euronews | `https://www.euronews.com/rss` | neutral |
| The Local (Europe) | `https://www.thelocal.com/feeds/rss.php` | neutral |
| DutchNews.nl | `https://www.dutchnews.nl/feed/` | neutral |
| Ukrainska Pravda (English) | `https://www.pravda.com.ua/eng/rss/` | neutral |
| Balkan Insight | `https://balkaninsight.com/feed/` | neutral |

Midden-Oosten:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| Al Jazeera English | `https://www.aljazeera.com/xml/rss/all.xml` | left-center |
| The Times of Israel | `https://www.timesofisrael.com/feed/` | left-center |
| Haaretz | `https://www.haaretz.com/srv/haaretz-latest-headlines` | left-center |
| Al-Monitor | `https://www.al-monitor.com/rss` | neutral |
| Arab News | `https://www.arabnews.com/rss.xml` | right-center |
| Daily Sabah | `https://www.dailysabah.com/rssFeed/10` | right |

Azie:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| South China Morning Post | `https://www.scmp.com/rss/91/feed/` | left-center |
| Hong Kong Free Press | `https://hongkongfp.com/feed/` | left-center |
| NDTV: Top stories | `https://feeds.feedburner.com/ndtvnews-top-stories` | left-center |
| The Hindu: International | `https://www.thehindu.com/news/international/feeder/default.rss` | left-center |
| Dawn | `https://www.dawn.com/feeds/home` | left-center |
| Rappler | `https://www.rappler.com/feed/` | left-center |
| The Japan Times | `https://www.japantimes.co.jp/feed/` | neutral |
| Bangkok Post: World | `https://www.bangkokpost.com/rss/data/world.xml` | neutral |
| The Diplomat | `https://thediplomat.com/feed/` | neutral |
| Asia Times | `https://asiatimes.com/feed/` | neutral |
| The Times of India | `https://timesofindia.indiatimes.com/rssfeedstopstories.cms` | right-center |
| Hindustan Times: World | `https://www.hindustantimes.com/feeds/rss/world-news/rssfeed.xml` | right-center |
| The Straits Times: World | `https://www.straitstimes.com/news/world/rss.xml` | right-center |

Oceanie:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| ABC News (Australia) | `https://www.abc.net.au/news/feed/45910/rss.xml` | left-center |
| The Guardian Australia | `https://www.theguardian.com/australia-news/rss` | left-center |
| The Sydney Morning Herald | `https://www.smh.com.au/rss/feed.xml` | left-center |

Amerika (buiten de VS):

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| Global News | `https://globalnews.ca/feed/` | left-center |
| MercoPress | `https://en.mercopress.com/rss/` | neutral |
| Buenos Aires Times | `https://www.batimes.com.ar/feed` | neutral |
| Mexico News Daily | `https://mexiconewsdaily.com/feed/` | neutral |
| The Globe and Mail: World | `https://www.theglobeandmail.com/arc/outboundfeeds/rss/category/world/` | right-center |

Afrika:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| Daily Maverick | `https://www.dailymaverick.co.za/dmrss/` | left-center |
| Premium Times | `https://www.premiumtimesng.com/feed` | neutral |
| Punch | `https://punchng.com/feed/` | neutral |
| AllAfrica | `https://allafrica.com/tools/headlines/rdf/latest/headlines.rdf` | neutral |
| Africanews | `https://www.africanews.com/feed/rss` | neutral |

Staatsmedia:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| RT | `https://www.rt.com/rss/news/` | right |
| Tehran Times | `https://www.tehrantimes.com/rss` | right |

Staatsmedia staan in een eigen groep. Hun oriëntatie (`right`) is lastig in te delen en hoort nog eens nagekeken te worden.

### Hoe de nieuwe bronnen getest zijn
Elke kandidaat-feed (212 stuks) is opgehaald met dezelfde `Http::get`, `simplexml_load_string`, `$xml->channel->item` en tekstfilter als `artikelen:ophalen`, zonder iets in de database te schrijven. Een feed telde als bruikbaar als:
- er minstens 5 items in staan;
- minstens 70% van de items na filtering minimaal 100 tekens en 15 woorden echte tekst heeft, zonder HTML-resten en niet gelijk aan de titel;
- elk item een geldige `pubDate` heeft (anders wordt de datum `1970-01-01`);
- het nieuwste item hooguit 7 dagen oud is.

112 feeds slaagden. Dubbele feeds (CNBC Top/Politics/World, NPR News/World, Euronews/Euronews World geven dezelfde artikelen) en Politico Europe (deels Franstalig) zijn eruit gehaald. Daarna is gezocht naar herhaalde zinnen aan het eind van de tekst. Die staan nu in het tekstfilter, zie bekende beperkingen.

Let op: Bij een nieuwe feed altijd de `volledige_tekst` van een paar items openen, niet alleen de titel of de HTTP-status.

### Onbruikbaar gebleken, niet gebruiken
- **Atom of RDF in plaats van RSS 2.0** (geen `channel->item`, dus 0 artikelen): Vox, The Atlantic, Jacobin, Business Insider, HuffPost, Nikkei Asia, Taipei Times, RFE/RL, Hürriyet Daily News
- **HTTP 403 voor de standaard user-agent**: UPI, OAN, The Post Millennial, The Western Journal, Daily Signal: Politics, Euractiv, Al Arabiya, The Telegraph: World, The East African, en de MediaNews-kranten (Denver Post, Chicago Tribune, Mercury News, Boston Herald, Orange County Register, Seattle Times)
- **Alleen een korte teaser of lege `description`**: Slate, Time, The Telegraph, The Economist, Financial Times, Politico, National Review, Daily Caller, Townhall, PJ Media, RedState, Hot Air, The American Conservative, ProPublica, Deseret News, RealClearPolitics, Foreign Policy, The Dispatch, Reason, Indian Express, The National (UAE), NZ Herald, UnHerd
- **Verouderd**: CNN (`rss.cnn.com`, bevroren data uit 2023), The Wall Street Journal (`feeds.a.dj.com`, ruim anderhalf jaar oud), Voice of America, The Jerusalem Post
- **Ongeldige XML of geen verbinding**: MSNBC, The Epoch Times, CNS News, Sky News Australia, The Korea Herald, The Daily Beast (verlopen certificaat), CBC, Miami Herald, News24
- Drudge Report (`feedpress.me/drudgereportfeed`): `description` is pagina-HTML (Patreon, andere sites, feedpress-GIF), niet de artikeltekst. Matching op die kolom groepeert dan alle Drudge-items op de boilerplate
- Reuters en AP hebben geen officiële RSS-feed meer

The Nation stond eerder op die onbruikbaar-lijst (layout-HTML plus “appeared first on The Nation”). Sinds het tekstfilter staat hij terug in de seeder. Guardian-promo's (`Get our …` en `Listen to the podcast …`) zitten in `<li><p>…</p></li>` en gaan eraf vóór tags naar spaties. De podcasttitel wisselt per artikel, dus het hele `<li>` gaat mee. Daarna blijft van `</a>,` een spatie over (`email ,`) en een tekstfilter op `email,` matcht niet. Die footer zat bij korte Nation-omschrijvingen in de eerste 40 woorden van de matcher (`post`, `appeared`, `first`, `nation`, plus de titel nog eens). De Guardian-promo (tussen de standfirst en de alinea: `get`, `our`, `email`, `free`, `app`, `daily`, `news`, `podcast`) gaf 8 gedeelde trefwoorden, boven de toenmalige drempel 7. Daardoor werden losse Guardian-AU-stukken (kolenmijn, immigratie, moordzaken) één gebeurtenis. RAKE (`en_US`) knipt nu woorden als `get` en `our` weg; de `<li>`-strip blijft de echte fix. Bestaande rijen blijven de oude tekst houden tot je ze zelf bijwerkt: de duplicaatcheck op URL slaat ze over. Daarna samenvattingen weg, `gebeurtenis_id` leegmaken, `gebeurtenissen` legen, opnieuw `koppel()`.

### Seeder opnieuw draaien
`BronSeeder` gebruikt `Bron::insert`. Opnieuw draaien op een bestaande database zet alle bronnen er dubbel in. `php artisan migrate:fresh --seed` begint schoon, maar gooit ook alle artikelen, gebeurtenissen en samenvattingen weg. Daarna `php artisan artikelen:ophalen` en `php artisan samenvattingen:genereren`. Met 124 bronnen duurt het ophalen merkbaar langer dan met 22.

## Bekende beperkingen

### Feeds
- Een RSS-feed levert meestal alleen titel en een korte samenvatting, niet de volledige artikeltekst. De kolom `volledige_tekst` is dus de RSS-omschrijving.
- Status 200 zegt niets over of de feed actueel is. Altijd publicatiedatums controleren.
- Status 200 zegt ook niets over de inhoud van `description`. Die kan HTML-layout zijn in plaats van artikeltekst. Bij het ophalen: eerst HTML-entiteiten decoderen, dan Guardian-`<li>`-promo's (`Get our …`, `Listen to the podcast …`) eraf, daarna tags naar een spatie, witruimte plat, `Continue reading...` eraf, en de WordPress-zin `The post ... appeared first on ...` (of `first appeared on`, Mediaite) eraf. Sinds bouwblok 15 gaan aan het einde van de tekst ook de vaste afsluiters van TheBlaze (`Like Blaze News? … Sign up here!`), GB News (`Our Standards: The GB News Editorial Charter`) en Punch (`Read More: https://…`) eraf, en wordt een WordPress-`[…]` vervangen door `…`. Die volgorde is nodig: een tag vóór een komma wordt een spatie, dus `email,` wordt `email ,` en een tekstfilter daarna mist de promo. `strip_tags` alleen plakt zinnen aan elkaar en haalt geen reclamezinnen weg.
- Alle filterregels op zinnen staan aan het einde van de tekst (`$`). `Read More:` midden in een zin blijft staan. Een nieuwe bron met een eigen afsluiter heeft dus een eigen regel nodig.
- De parser leest alleen RSS 2.0 (`$xml->channel->item`). Een Atom- of RDF-feed geeft geen fout, maar levert 0 artikelen op. Daarom vallen onder andere Vox, The Atlantic en Jacobin af.
- Sommige sites (UPI, de MediaNews-kranten) geven 403 op de standaard user-agent van de Laravel HTTP client.

### Groeperen
- Matching gebeurt op `volledige_tekst`, niet op de titel. Koppen over dezelfde gebeurtenis verschillen te sterk per bron. Dat wijkt af van de oorspronkelijke casusformulering (trefwoorden in de titel).
- Alleen de eerste 40 woorden van die omschrijving tellen mee (`vergelijkTekst()`). De volle tekst blijft in de database voor de AI. Zonder die knip deelde een Guardian-digest van honderden woorden makkelijk journalistiek-Engels met SCMP of NDTV. Die knip blijft, ook na RAKE.
- Trefwoorden komen uit RAKE (`donatello-za/rake-php-plus`), niet uit een eigen stopwoordenlijst. `trefwoorden()` vraagt `keywords()` (losse woorden) met taal `en_US`, omdat de feeds Engels zijn. `get()` geeft zinsdelen (`supreme court allowed`); die matchen niet met `array_intersect` op `supreme court`. RAKE gebruikt stopwoorden als snijpunten. `en_US` is veel groter dan de oude array van 22 woorden, dus de overlap daalt (`third` viel in Tinker af).
- Overlapdrempel is 6 trefwoorden (`$minimaleOverlap` in `GebeurtenisMatcher`). Het match-venster is nu strikter: alleen artikelen met dezelfde kalenderdatum mogen worden gekoppeld (`whereDate('publicatiedatum', $zelfdeDatum)`). Met de eigen lijst stond de drempel op 7 en het venster op 3. Na RAKE koppelde 7 te weinig, 5 te los; 6 is de huidige knop. De wissel naar “zelfde datum” voorkomt cross-day matchen bij een nieuwe gebeurtenis op de volgende dag. Of de groepen hetzelfde feit zijn, is nog steeds een steekproef in phpMyAdmin of de browser, geen gegeven.
- `RakePlus::create()` laadt `en_US` bij elke aanroep. Zonder hergebruik wordt `artikelen:ophalen` traag. De matcher houdt één `$rake`: eerste tekst `create()`, daarna `extract()`. Het command maakt die matcher één keer vóór de loop.
- `extract()` op dezelfde tekst blijft duur als `koppel()` elke kandidaat opnieuw door RAKE haalt. `$trefwoordCache` slaat de trefwoorden per artikel-id op (`trefwoordenVan()`). Het nieuwe artikel gaat één keer door RAKE, elke kandidaat hoogstens één keer in de hele command. `overlap()` en `zelfdeGebeurtenis()` gaan om de cache heen (Tinker met losse strings).
- Sinds bouwblok 16 groepeert de matcher standaard met embeddings (`MATCHER_METHODE=embeddings`): Ollama met `nomic-embed-text`, cosine similarity in PHP, drempel 0,85 (`MATCHER_MINIMALE_SIMILARITY`). RAKE blijft als terugval voor artikelen zonder embedding (Ollama uit) of met `MATCHER_METHODE=rake`. De bullets hierboven over 40 woorden, drempel 6 en de RAKE-cache gelden alleen voor die RAKE-route. Embeddings zien synoniemen wel, maar brede onderwerpen (Trump/Oekraïne) kunnen boven de drempel samenvallen.
- Bij beide methodes wint sinds bouwblok 16 de kandidaat met de hoogste score (meeste trefwoorden of hoogste similarity), niet meer de eerste die de drempel haalt. Ketting-matching kan nog. Voorbeeld van vóór de knip van 40 woorden: Trump/Iran-stukken plus Guardian First Thing plus SCMP-Hongkong in één groep. Voorbeeld daarna: Guardian-AU-stukken via de gedeelde promo in de eerste 40 woorden (8 trefwoorden, toen drempel 7). Die promo's (`Get our …` en `Listen to the podcast …`) gaan er bij het ophalen af als HTML-`<li>` vóór tag-strip; bestaande `volledige_tekst` blijft vies tot je die rijen bijwerkt en opnieuw koppelt.
- Artikelen worden alleen bij het ophalen of bij een handmatige `koppel()`-run gematcht. Na een wijziging in de matcher: samenvattingen weg (foreign key), `gebeurtenis_id` leegmaken, `gebeurtenissen` legen, opnieuw koppelen. Of `migrate:fresh --seed` plus `artikelen:ophalen`.

### Oriëntatie
- De oriëntatie komt van een dataset en is een generalisatie per bron, niet per artikel.
- Sinds bouwblok 15 heeft elk label meerdere bronnen (13 `left`, 51 `left-center`, 31 `neutral`, 8 `right-center`, 21 `right`). `left-center` is nog steeds de grootste groep. De AI krijgt die labels nog steeds niet per artikel in de prompt.
- De oriëntatie van de 102 nieuwe bronnen is een inschatting op basis van AllSides en Media Bias Fact Check, niet overgenomen uit dezelfde dataset als de eerste 22.
- Staatsmedia (RT, Tehran Times) hebben het label `right`. Dat label zegt weinig over staatspropaganda; MediaLens heeft daar geen apart veld voor.
- Bronnen komen uit veel landen, maar de feeds zijn allemaal Engelstalig. RAKE gebruikt `en_US`.

### Samenvatting
- De AI kan zelf ook vooringenomen zijn.
- De vier velden: `kernfeiten` is zelfstandig leesbaar (waar gaat het over, daarna de feiten), zonder bronnamen. `betrokkenen` is een kommagescheiden namenlijst, geen zin. `overeenstemming` en `verschil` gaan over schrijfwijze (toon, woordkeuze, nadruk, weglatingen), niet over een catalogus van gedeelde kloktijden. Bronnen bij naam, geen MBFC-labels (`left`, `right`, `neutral`) in de output. Oriëntatie gaat nog steeds niet per artikel mee in de prompt.
- Dat wijkt af van de eerste bouwblok-5-prompt (neutrale feiten vs. welke bron welk feit noemt) en schuift `overeenstemming`/`verschil` dichter naar de framing-vraag van bouwblok 4. De AI kiest zelf geen partij.
- Een promptwijziging vernieuwt bestaande rijen niet. `slaOp()` slaat over als er al een samenvatting is en er geen nieuwer artikel bij is. Rij in `samenvattingen` weg, daarna `php artisan samenvattingen:genereren {id}`.
- `samenvat()` stuurt een `json_schema` mee met de vier verplichte string-velden. Een lege string voldoet aan dat schema. `slaOp()` weigert een leeg veld alsnog, en het command probeert die gebeurtenis opnieuw.
- `samenvattingen:genereren` probeert een gebeurtenis maximaal 3 keer. Ongeldige JSON gooit een `RuntimeException` in `slaOp()`, vóór `create()` of `update()`. Een LLM die langer duurt dan `Http::timeout(60)` gooit een `ConnectionException`. Na 3 mislukte pogingen slaat het command die gebeurtenis over en gaat verder. Een andere exception stopt het command nog wel. Elke timeout-poging kan 60 seconden duren.
- Bij een HTTP-fout geeft `LiteLlm::vraag()` een lege string terug. Het command meldt dan een ontbrekend veld. De statuscode van de proxy staat daar niet bij.
- Prompt caching bij DeepSeek is automatisch. Het vaste system-bericht staat vooraan, de artikelen in het user-bericht. `cached_tokens` wordt gelogd in `storage/logs/laravel.log`. Een hit zie je vooral als dezelfde prompt terugkomt, zoals een retry of dezelfde gebeurtenis opnieuw samenvatten. Na een wijziging in `$systeem` is die prefix nieuw, dus `cached_tokens` begint weer laag. Verschillende gebeurtenissen blijven meestal op `0`, omdat hun artikeltekst meteen afwijkt. `migrate:fresh` leegt alleen de database, niet de cache bij de provider.
- `LiteLlm` stuurt `thinking.type`, `reasoning_effort` en `temperature` mee uit `.env`. Lege regels vallen terug op `enabled`, `high` en `1`. Bij thinking aan heeft `temperature` geen effect; `top_p` speelt alleen tussen 0,95 en 1. Effort `max` kan de timeout van 60 seconden raken. Of de Educom-proxy die velden doorgeeft, zie je aan `reasoning_content` of `reasoning_tokens` in de response, niet alleen aan een 200.

### Overzicht
- De lijst toont alleen gebeurtenissen met minstens 2 artikelen, dezelfde drempel als `samenvattingen:genereren`. Groepen met 1 artikel staan wél in de tabel `gebeurtenissen`, niet op de pagina. Dat wijkt af van een 1-op-1-check tegen de hele tabel.
- Sorteren op `withMax('artikelen', 'publicatiedatum')`, daarna `id` DESC als tiebreaker. `publicatiedatum` is een `date`, geen tijdstip: zonder tweede `orderBy` is de volgorde binnen dezelfde dag willekeurig en wijkt phpMyAdmin af van de pagina. `updated_at` van de gebeurtenis beweegt niet als de matcher een artikel aan een bestaande groep koppelt, dus die kolom is geen “nieuwste nieuws”.
- `paginate(15)` in plaats van `get()`. Paginalinks via `$gebeurtenissen->links()`. `withQueryString()` houdt `?filter=` vast op pagina 2.
- Filter via querystring, geen JavaScript: geen `filter` is Alles, `?filter=met` is `has('samenvatting')`, `?filter=zonder` is `doesntHave('samenvatting')`. Onbekende waarden gedragen zich als Alles. Extra keys in `route('gebeurtenissen.index', ['filter' => 'zonder'])` worden querystring, omdat `filter` geen routeparameter is.
- Per rij: onderwerp en de telling Links / Midden / Rechts. Wel of geen samenvatting staat niet meer per rij; dat is het filter. `withExists('samenvatting')` is daarom weg. `withCount('artikelen')` haalt `artikelen_count` nog op, de lijst toont dat totaal niet.
- De drie tellingen zijn extra `withCount`s met `whereHas('bron')`. Zelfde indeling als de kolommen: `left` en `left-center` zijn Links, `neutral` is Midden, `right-center` en `right` zijn Rechts. Geen extra AI.
- Sinds de homepage wijst `/` naar `HomeController@index` (named route `home`), niet meer naar de lijst. `/gebeurtenissen` heet `gebeurtenissen.index` en is **Alles** in het menu. Elk onderwerp linkt naar `/gebeurtenissen/{id}` via `gebeurtenissen.show`. Filterlinks gebruiken de named route, zodat ze op `/gebeurtenissen?filter=…` landen, niet op `/?filter=…`.

### Onderwerpen en homepage
- Elke gebeurtenis heeft een nullable kolom `gebeurtenissen.categorie`, met de waarden uit de enum `App\Categorie`: `wereld`, `politiek`, `economie`, `tech-wetenschap`, `sport`, `overig`. De omschrijving per categorie staat in `Categorie::beschrijving()` en gaat zo de prompt in.
- De categorie komt van de AI, niet uit de feed. `samenvat()` vraagt hem in hetzelfde `json_schema` als de vier samenvattingsvelden (een `enum`). `slaOp()` zet hem op de gebeurtenis, niet in `samenvattingen`, en gooit een `RuntimeException` bij een onbekende waarde, zodat het command het opnieuw probeert.
- `slaOp()` slaat een bestaande samenvatting zonder nieuwe artikelen over, dus oude gebeurtenissen krijgen zo geen categorie. Daarvoor is `gebeurtenissen:categoriseren`: een kleine aparte prompt (`categoriseer()`) voor gebeurtenissen met 2+ artikelen en `categorie` leeg, maximaal 3 pogingen per gebeurtenis. Met een id deelt hij die ene gebeurtenis altijd opnieuw in.
- Een gebeurtenis zonder categorie staat alleen onder Alles (`/gebeurtenissen`), niet op een onderwerppagina of in de blokken per onderwerp op de homepage.
- Menubalk: Home, Wereld, Politiek, Economie, Tech & Wetenschap, Sport, Alles (`Categorie::menu()`). `overig` staat niet in het menu maar wel onder Alles; `/onderwerpen/overig` werkt wel. Een onbekende waarde in de URL geeft 404 (enum route binding).
- Er zijn geen sportfeeds. Sport blijft vrijwel leeg tot er sportbronnen in `BronSeeder` staan.
- Onderwerppagina's gebruiken `GebeurtenisController@index` met dezelfde zoekterm, filters, sortering en paginering als Alles. Het formulier en **Wissen** blijven op het onderwerp.
- Homepage (`HomeController`, view `home.blade.php`): **Hot** zijn de 5 gebeurtenissen met de meeste artikelen in de laatste 48 uur (minstens 2). `publicatiedatum` is een datum zonder tijd, dus "48 uur" is vandaag plus gisteren. Daaronder per onderwerp de 5 nieuwste gebeurtenissen (een onderwerp zonder gebeurtenissen valt weg) en een zijkolom **Meeste artikelen** over de hele database.
- Hot hangt af van de datum van vandaag. Zonder recent `artikelen:ophalen` is dat blok leeg.
- Een onderwerppagina toont bovenaan **Trending in {onderwerp}**: de 3 gebeurtenissen van dat onderwerp die het hoogst scoren op dezelfde Hot-regel, naast elkaar (`md:grid-cols-3`, op mobiel onder elkaar). Daaronder **Meer over {onderwerp}** met de gewone lijst. De trending gebeurtenissen gaan op elke pagina uit die lijst (`whereKeyNot`), zodat ze niet dubbel staan en pagina 2 niet verschuift. Het getal "verhalen" telt ze wel mee.
- Trending verschijnt alleen op pagina 1 en alleen zonder zoekterm, samenvattingsfilter of perspectief. Met een filter staat alles weer gewoon in de lijst. Zonder gebeurtenissen in de laatste 48 uur valt het blok weg. Alles (`/gebeurtenissen`) heeft geen trending-blok.
- De kaartquery (tellingen Links/Midden/Rechts, nieuwste datum, één afbeelding) staat als scopes op `Gebeurtenis`: `zichtbaar()`, `voorKaart()`, `nieuwsteEerst()`, en `hot()` voor de 48-uursregel. Lijst, onderwerppagina's en homepage delen die.

### Detailpagina
- Bovenaan staat **Terug naar {onderwerp}** als de gebeurtenis een categorie heeft, anders **Terug naar het overzicht**.
- Bakjes `Links`, `Midden` en `Rechts` zijn alleen weergave. `bronnen.orientatie` blijft de Media Bias Fact Check-label (`left`, `left-center`, `neutral`, `right-center`, `right`). Die ruwe waarde staat per artikel onder de titel, bij de bronnaam.
- `groupBy` maakt alleen een bakje waar een artikel in zit. Daarna zet een `foreach` alsnog een lege Collection op Links, Midden en Rechts. `sortBy` forceert die volgorde. Een leeg bakje toont de kop plus "Geen artikelen in dit bakje." Een onbekende `orientatie` blijft een extra bakje en zakt via `?? 4` naar onderen.
- De drie bakjes staan in een `grid`: één kolom onder 768px (`grid-cols-1`), drie kolommen daarboven (`md:grid-cols-3`).
- Lijst, detail en `/over` delen `resources/views/layouts/app.blade.php` (kop, footer, Tailwind). De body is `min-h-screen` en een kolom; `flex-1` op `main` zet lege hoogte in het midden, zodat de footer één regel onderaan het venster blijft. Elk samenvattingsveld is één `<p>`. In de huidige rijen zitten geen regeleinden, dus `nl2br` zou die tekst niet splitsen. Zonder `public/hot` (`npm run dev`) en zonder `public/build/manifest.json` (`npm run build`) laadt `@vite` niet. De pagina blijft dan HTML zonder Tailwind.
- Onder de drie kolommen, vóór de samenvatting: korte disclaimer plus link `route('over')`. Staat buiten de `@if` van de samenvatting, dus ook bij een gebeurtenis zonder samenvatting.

### Transparantie
- `/over` (`OverController@index`, named route `over`, view `resources/views/over.blade.php`) legt uit wat labels, groepen en samenvatting wel en niet zijn. Geen database, geen AI-call. De casus noemt ook `/transparantie`; de gekozen URL is `/over`.
- Labels komen uit Media Bias Fact Check per bron, niet per artikel. Bakjes Links / Midden / Rechts zijn weergave. De ruwe label blijft in `bronnen.orientatie`.
- De matcher groepeert op trefwoordoverlap in de eerste 40 woorden van de RSS-omschrijving, binnen één kalenderdag. Dat is geen bewijs van hetzelfde feit en geen oordeel over wat waar is.
- De samenvatting komt van DeepSeek via OpenRouter en de Educom-proxy. Dat is een keus, geen neutrale arbiter. De casusformule “Groq/DeepSeek” dekt het model niet: Groq draait hier niet. MBFC-labels gaan niet per artikel mee in de prompt. `volledige_tekst` is de RSS-omschrijving.
- De footer van de layout heeft een link **Over**. Zonder die link is `/over` alleen bereikbaar via de disclaimer of door de URL te typen.

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
- [x] `description` opschonen vóór opslaan: HTML-entiteiten decoderen, Guardian-`<li>`-promo's (`Get our …`, `Listen to the podcast …`) eraf, daarna HTML-tags naar spaties, witruimte plat, `Continue reading...` eraf, WordPress-footer `The post ... appeared first on ...` eraf. Sinds bouwblok 15 ook de afsluiters van Mediaite, TheBlaze, GB News, Punch en WordPress-`[…]`
- [x] `volledige_tekst` van de later toegevoegde wereldwijde en lokale feeds controleren, niet alleen de titel of status 200

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

### Bouwblok 11: Transparantie (ethiek uit de casus)
- [x] Route `/over` naar `OverController@index`, named route `over`, view `resources/views/over.blade.php`. Niet in `GebeurtenisController`: die pagina heeft geen gebeurtenis-query
- [x] Uitleg: labels uit Media Bias Fact Check per bron, niet per artikel; bakjes zijn weergave
- [x] Benoem: matcher groepeert op trefwoordoverlap, niet op “dezelfde waarheid”; de AI kan zelf vooringenomen zijn; DeepSeek via OpenRouter is een keus, geen neutrale arbiter (geen Groq in deze app)
- [x] Op de detailpagina een korte disclaimer onder de kolommen, link `route('over')`. Ook zichtbaar zonder samenvatting
- [x] Footer-link **Over** in de layout, zodat `/over` ook vanaf het overzicht bereikbaar is
- [x] README: kopje “Transparantie” onder bekende beperkingen, zodat het niet alleen in de UI staat

### Bouwblok 12: Groeperen en onderwerp verbeteren
- [ ] `onderwerp` afleiden van gedeelde trefwoorden of de “meest centrale” titel, niet blind het eerste artikel
- [x] RAKE (`donatello-za/rake-php-plus`) als vervanging van de eigen stopwoordenlijst: `keywords()`, `en_US`, drempel 6, alleen artikelen van dezelfde datum
- [ ] Matcher: documenteer in code waarom 40 woorden, drempel 6 en de strikte same-day-regel (`$maxDagen = 0`)
- [x] Handmatig herkoppelen blijft: samenvattingen weg, `gebeurtenis_id` leegmaken, opnieuw `koppel()`, of `migrate:fresh --seed` plus `artikelen:ophalen`

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
- [ ] Latere pass gebruiken voor review van matcher-, controller- en summary-kwaliteit met AI-gestuurde feedbackloops

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

### Bouwblok 15: Bronnen uitbreiden
- [x] 212 kandidaat-feeds getest met dezelfde ophaal- en filterlogica als `artikelen:ophalen`: genoeg items, leesbare tekst na filtering, geldige `pubDate`, recent
- [x] 102 nieuwe bronnen toegevoegd aan `BronSeeder`, dubbele feeds en een deels Franstalige feed weggelaten. Totaal 124 bronnen
- [x] Seeder geordend per regio of categorie (VS politiek, VS algemeen, VS lokaal, VS wereld, VK en Ierland, Europa, Midden-Oosten, Azië, Oceanië, Amerika, Afrika, staatsmedia) en binnen elke groep van links naar rechts
- [x] Meer bronnen per label, vooral `left`, `neutral` en `right`. Staatsmedia in een eigen groep
- [x] Tekstfilter uitgebreid: `first appeared on` (Mediaite), `Like Blaze News? …` (TheBlaze), `Our Standards: The GB News Editorial Charter` (GB News), `Read More: https://…` (Punch), `[…]` wordt `…`
- [x] Feature-test `ArtikelenOphalenTest` voor elke filterregel, plus een test dat `Read More:` midden in een zin blijft staan
- [x] Onbruikbare feeds met reden in de README (Atom/RDF, 403, alleen teaser, verouderd, geen verbinding)
- [ ] Oriëntatie van de 102 nieuwe bronnen per bron nalopen in Media Bias Fact Check
- [ ] `/over` en het kopje Transparantie noemen alleen Media Bias Fact Check; aanpassen als de nieuwe labels blijven
- [ ] Atom-feeds ondersteunen (`$xml->entry`), zodat Vox, The Atlantic en Jacobin erbij kunnen
- [ ] `artikelen:ophalen` draaien met alle 124 bronnen en een steekproef van `volledige_tekst` en de groepen in phpMyAdmin bekijken

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
- [ ] Bestaande gebeurtenissen opnieuw koppelen met embeddings en de groepen vergelijken met de RAKE-groepen
- [ ] Controleren of brede onderwerpen (Trump/Oekraïne) niet te veel samenvallen; anders drempel verhogen of tijdvenster aanpassen

### Later / optioneel
- [ ] Scheduler: `artikelen:ophalen` + `samenvattingen:genereren` via Laravel `Schedule`
- [ ] Framing-analyse uit bouwblok 4 als apart veld opslaan en tonen (`vergelijk()` is uitgecommentarieerd; deel van die vraag zit nu in overeenstemming/verschil van `samenvat()`)
- [ ] Feature-tests voor `index` / `show`
- [x] Embeddings + vectorsimilariteit i.p.v. trefwoordoverlap (casus noemt dit als mogelijke clustering). Zie bouwblok 16
- [ ] Volledige artikeltekst via web scraping of een nieuws-API i.p.v. de RSS-omschrijving in `volledige_tekst`
- [ ] Oriëntatie per artikel via AI / tekstclassificatie i.p.v. alleen het MBFC-label per bron