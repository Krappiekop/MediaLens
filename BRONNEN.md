# Bronnen

Alle nieuwsbronnen van MediaLens, met feed-URL en politieke oriëntatie, plus hoe de feeds getest zijn en welke afvielen. Voor een korte introductie, zie de [README](README.md).

De bronnentabel wordt gevuld via `database/seeders/BronSeeder.php`. De seeder zet 124 feeds in de database, gegroepeerd per regio of categorie en binnen elke groep van links naar rechts. De oriëntatie komt uit de Media Bias Fact Check-dataset van idiap ([`mbfc.csv`](https://github.com/idiap/Factual-Reporting-and-Political-Bias-Web-Interactions/blob/main/data/mbfc.csv)), opgezocht op het domein van de bron. Sinds bouwblok 16 zijn de 25 afwijkende labels daarop gelijkgetrokken. 9 bronnen staan niet in die dataset; hun label blijft een inschatting op basis van AllSides en Media Bias Fact Check: CalMatters, Daily Sabah, DutchNews.nl, Hong Kong Free Press, Mexico News Daily, Premium Times, The Free Press, The Hindu, The Local.

Verdeling: 15 `left`, 57 `left-center`, 21 `neutral`, 12 `right-center`, 19 `right` (RT staat in de dataset als `right-center`, Tehran Times als `right`; beide zijn staatsmedia).

VS: politiek:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The Nation | `https://www.thenation.com/feed/?post_type=article` | left |
| The Intercept | `https://theintercept.com/feed/?rss` | left |
| The New Republic | `https://newrepublic.com/rss.xml` | left |
| Rolling Stone: Politics | `https://www.rollingstone.com/politics/feed/` | left |
| Talking Points Memo | `https://talkingpointsmemo.com/feed` | left |
| Mediaite | `https://www.mediaite.com/feed/` | left |
| Mother Jones | `https://www.motherjones.com/feed/` | left-center |
| The New York Times: Politics | `https://rss.nytimes.com/services/xml/rss/nyt/Politics.xml` | left-center |
| The Washington Post: Politics | `https://feeds.washingtonpost.com/rss/politics` | left-center |
| NPR: Politics | `https://feeds.npr.org/1014/rss.xml` | left-center |
| ABC News (VS): Politics | `https://abcnews.go.com/abcnews/politicsheadlines` | left-center |
| CBS News: Politics | `https://www.cbsnews.com/latest/rss/politics` | left-center |
| NBC News: Politics | `https://feeds.nbcnews.com/nbcnews/public/politics` | left-center |
| LA Times: Politics | `https://www.latimes.com/politics/rss2.0.xml` | left-center |
| Bloomberg: Politics | `https://feeds.bloomberg.com/politics/news.rss` | left-center |
| Axios | `https://api.axios.com/feed/` | left-center |
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
| The 19th | `https://19thnews.org/feed/` | left |
| The New York Times | `https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml` | left-center |
| The Washington Post: National | `https://feeds.washingtonpost.com/rss/national` | left-center |
| NBC News | `https://feeds.nbcnews.com/nbcnews/public/news` | left-center |
| CBS News | `https://www.cbsnews.com/feeds/rss/main.rss` | left-center |
| ABC News (VS) | `https://abcnews.go.com/abcnews/topstories` | left-center |
| PBS NewsHour | `https://www.pbs.org/newshour/feeds/rss/headlines` | left-center |
| The Christian Science Monitor | `https://rss.csmonitor.com/feeds/all` | neutral |
| The Hill: Policy | `https://thehill.com/policy/feed/` | neutral |
| Military Times | `https://www.militarytimes.com/arc/outboundfeeds/rss/?outputType=xml` | neutral |
| Newsweek | `https://www.newsweek.com/rss` | right-center |
| New York Post | `https://nypost.com/feed` | right-center |
| The Washington Times | `https://www.washingtontimes.com/rss/headlines/news/` | right-center |
| Fox Business | `https://moxie.foxbusiness.com/google-publisher/latest.xml` | right-center |
| Fox News: US | `https://moxie.foxnews.com/google-publisher/us.xml` | right |
| Breitbart | `https://feeds.feedburner.com/breitbart` | right |
| The Daily Wire | `https://www.dailywire.com/feeds/rss.xml` | right |
| TheBlaze | `https://www.theblaze.com/feeds/feed.rss` | right |
| Newsmax | `https://www.newsmax.com/rss/Newsfront/16/` | right |

VS: lokaal en regionaal:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| LA Times | `https://www.latimes.com/local/rss2.0.xml` | left-center |
| CalMatters | `https://calmatters.org/feed/` | left-center |
| The Texas Tribune | `https://www.texastribune.org/feeds/main/` | left-center |

VS: wereldnieuws:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The New York Times: World | `https://rss.nytimes.com/services/xml/rss/nyt/World.xml` | left-center |
| The Washington Post: World | `https://feeds.washingtonpost.com/rss/world` | left-center |
| NPR: World | `https://feeds.npr.org/1004/rss.xml` | left-center |
| NBC News: World | `https://feeds.nbcnews.com/nbcnews/public/world` | left-center |
| CBS News: World | `https://www.cbsnews.com/latest/rss/world` | left-center |
| LA Times: World & Nation | `https://www.latimes.com/world-nation/rss2.0.xml` | left-center |
| CNBC: World | `https://www.cnbc.com/id/100727362/device/rss/rss.html` | left-center |
| The Hill: International | `https://thehill.com/policy/international/feed/` | neutral |
| Fox News: World | `https://moxie.foxnews.com/google-publisher/world.xml` | right |

VK en Ierland:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| The Mirror | `https://www.mirror.co.uk/news/world-news/?service=rss` | left-center |
| BBC News: World | `https://feeds.bbci.co.uk/news/world/rss.xml` | left-center |
| BBC News: US & Canada | `https://feeds.bbci.co.uk/news/world/us_and_canada/rss.xml` | left-center |
| The Guardian: World | `https://www.theguardian.com/world/rss` | left-center |
| The Guardian: UK | `https://www.theguardian.com/uk-news/rss` | left-center |
| The Guardian: US | `https://www.theguardian.com/us-news/rss` | left-center |
| RTÉ News | `https://www.rte.ie/feeds/rss/?index=/news/` | neutral |
| Sky News: World | `https://feeds.skynews.com/feeds/rss/world.xml` | neutral |
| Sky News: UK | `https://feeds.skynews.com/feeds/rss/uk.xml` | neutral |
| Daily Mail: World | `https://www.dailymail.co.uk/news/worldnews/index.rss` | right |
| The Sun | `https://www.thesun.co.uk/news/feed/` | right |
| GB News | `https://www.gbnews.com/feeds/news.rss` | right |

Europa:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| Meduza (English) | `https://meduza.io/rss/en/all` | left |
| Der Spiegel International | `https://www.spiegel.de/international/index.rss` | left-center |
| Le Monde (English) | `https://www.lemonde.fr/en/rss/une.xml` | left-center |
| Kyiv Independent | `https://kyivindependent.com/news-archive/rss/` | left-center |
| The Moscow Times | `https://www.themoscowtimes.com/rss/news` | left-center |
| Deutsche Welle | `https://rss.dw.com/xml/rss-en-world` | left-center |
| Euronews | `https://www.euronews.com/rss` | left-center |
| Ukrainska Pravda (English) | `https://www.pravda.com.ua/eng/rss/` | left-center |
| Balkan Insight | `https://balkaninsight.com/feed/` | left-center |
| RFI (English) | `https://www.rfi.fr/en/rss` | neutral |
| France 24 | `https://www.france24.com/en/rss` | neutral |
| The Local (Europe) | `https://www.thelocal.com/feeds/rss.php` | neutral |
| DutchNews.nl | `https://www.dutchnews.nl/feed/` | neutral |

Midden-Oosten:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| Haaretz | `https://www.haaretz.com/srv/haaretz-latest-headlines` | left |
| Al Jazeera English | `https://www.aljazeera.com/xml/rss/all.xml` | left-center |
| The Times of Israel | `https://www.timesofisrael.com/feed/` | left-center |
| Al-Monitor | `https://www.al-monitor.com/rss` | left-center |
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
| Hindustan Times: World | `https://www.hindustantimes.com/feeds/rss/world-news/rssfeed.xml` | left-center |
| The Japan Times | `https://www.japantimes.co.jp/feed/` | neutral |
| The Diplomat | `https://thediplomat.com/feed/` | neutral |
| Asia Times | `https://asiatimes.com/feed/` | neutral |
| Bangkok Post: World | `https://www.bangkokpost.com/rss/data/world.xml` | right-center |
| The Times of India | `https://timesofindia.indiatimes.com/rssfeedstopstories.cms` | right-center |
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
| MercoPress | `https://en.mercopress.com/rss/` | left-center |
| Mexico News Daily | `https://mexiconewsdaily.com/feed/` | neutral |
| Buenos Aires Times | `https://www.batimes.com.ar/feed` | right-center |
| The Globe and Mail: World | `https://www.theglobeandmail.com/arc/outboundfeeds/rss/category/world/` | right-center |

Afrika:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| Punch | `https://punchng.com/feed/` | left-center |
| Africanews | `https://www.africanews.com/feed/rss` | left-center |
| Daily Maverick | `https://www.dailymaverick.co.za/dmrss/` | neutral |
| Premium Times | `https://www.premiumtimesng.com/feed` | neutral |
| AllAfrica | `https://allafrica.com/tools/headlines/rdf/latest/headlines.rdf` | neutral |

Staatsmedia:

| Bron | Feed URL | Oriëntatie |
|---|---|---|
| RT | `https://www.rt.com/rss/news/` | right-center |
| Tehran Times | `https://www.tehrantimes.com/rss` | right |

Staatsmedia staan in een eigen groep. Een links-rechts-label (RT `right-center`, Tehran Times `right`, beide uit de dataset) zegt weinig over staatspropaganda.

## Hoe de nieuwe bronnen getest zijn
Elke kandidaat-feed (212 stuks) is opgehaald met dezelfde `Http::get`, `simplexml_load_string`, `$xml->channel->item` en tekstfilter als `artikelen:ophalen`, zonder iets in de database te schrijven. Een feed telde als bruikbaar als:
- er minstens 5 items in staan;
- minstens 70% van de items na filtering minimaal 100 tekens en 15 woorden echte tekst heeft, zonder HTML-resten en niet gelijk aan de titel;
- elk item een geldige `pubDate` heeft (anders wordt de datum `1970-01-01`);
- het nieuwste item hooguit 7 dagen oud is.

112 feeds slaagden. Dubbele feeds (CNBC Top/Politics/World, NPR News/World, Euronews/Euronews World geven dezelfde artikelen) en Politico Europe (deels Franstalig) zijn eruit gehaald. Daarna is gezocht naar herhaalde zinnen aan het eind van de tekst. Die staan nu in het tekstfilter, zie [Ontwerpkeuzes: Feeds](ONTWERPKEUZES.md#feeds).

Let op: Bij een nieuwe feed altijd de `volledige_tekst` van een paar items openen, niet alleen de titel of de HTTP-status.

## Onbruikbaar gebleken, niet gebruiken
- **Atom of RDF in plaats van RSS 2.0** (geen `channel->item`, dus 0 artikelen): Vox, The Atlantic, Jacobin, Business Insider, HuffPost, Nikkei Asia, Taipei Times, RFE/RL, Hürriyet Daily News
- **HTTP 403 voor de standaard user-agent**: UPI, OAN, The Post Millennial, The Western Journal, Daily Signal: Politics, Euractiv, Al Arabiya, The Telegraph: World, The East African, en de MediaNews-kranten (Denver Post, Chicago Tribune, Mercury News, Boston Herald, Orange County Register, Seattle Times)
- **Alleen een korte teaser of lege `description`**: Slate, Time, The Telegraph, The Economist, Financial Times, Politico, National Review, Daily Caller, Townhall, PJ Media, RedState, Hot Air, The American Conservative, ProPublica, Deseret News, RealClearPolitics, Foreign Policy, The Dispatch, Reason, Indian Express, The National (UAE), NZ Herald, UnHerd
- **Verouderd**: CNN (`rss.cnn.com`, bevroren data uit 2023), The Wall Street Journal (`feeds.a.dj.com`, ruim anderhalf jaar oud), Voice of America, The Jerusalem Post
- **Ongeldige XML of geen verbinding**: MSNBC, The Epoch Times, CNS News, Sky News Australia, The Korea Herald, The Daily Beast (verlopen certificaat), CBC, Miami Herald, News24
- Drudge Report (`feedpress.me/drudgereportfeed`): `description` is pagina-HTML (Patreon, andere sites, feedpress-GIF), niet de artikeltekst. Matching op die kolom groepeert dan alle Drudge-items op de boilerplate
- Reuters en AP hebben geen officiële RSS-feed meer

The Nation stond eerder op die onbruikbaar-lijst (layout-HTML plus “appeared first on The Nation”). Sinds het tekstfilter staat hij terug in de seeder. Guardian-promo's (`Get our …` en `Listen to the podcast …`) zitten in `<li><p>…</p></li>` en gaan eraf vóór tags naar spaties. De podcasttitel wisselt per artikel, dus het hele `<li>` gaat mee. Daarna blijft van `</a>,` een spatie over (`email ,`) en een tekstfilter op `email,` matcht niet. Die footer zat bij korte Nation-omschrijvingen in de eerste 40 woorden van de matcher (`post`, `appeared`, `first`, `nation`, plus de titel nog eens). De Guardian-promo (tussen de standfirst en de alinea: `get`, `our`, `email`, `free`, `app`, `daily`, `news`, `podcast`) gaf 8 gedeelde trefwoorden, boven de toenmalige drempel 7. Daardoor werden losse Guardian-AU-stukken (kolenmijn, immigratie, moordzaken) één gebeurtenis. RAKE (`en_US`) knipt nu woorden als `get` en `our` weg; de `<li>`-strip blijft de echte fix. Bestaande rijen blijven de oude tekst houden tot je ze zelf bijwerkt: de duplicaatcheck op URL slaat ze over. Daarna samenvattingen weg, `gebeurtenis_id` leegmaken, `gebeurtenissen` legen, opnieuw `koppel()`.

## Seeder opnieuw draaien
`BronSeeder` gebruikt `Bron::insert`. Opnieuw draaien op een bestaande database zet alle bronnen er dubbel in. `php artisan migrate:fresh --seed` begint schoon, maar gooit ook alle artikelen, gebeurtenissen en samenvattingen weg. Daarna `php artisan artikelen:ophalen` en `php artisan samenvattingen:genereren`. Met 124 bronnen duurt het ophalen merkbaar langer dan met 22.
