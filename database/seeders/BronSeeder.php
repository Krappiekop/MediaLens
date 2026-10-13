<?php

namespace Database\Seeders;

use App\Models\Bron;
use Illuminate\Database\Seeder;

class BronSeeder extends Seeder
{
    /**
     * Bronnen per regio/categorie, binnen elke groep gesorteerd van links naar rechts.
     */
    public function run(): void
    {
        Bron::insert([
            // --- VS: politiek ---
            ['naam' => 'The Nation', 'orientatie' => 'left', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.thenation.com/feed/?post_type=article'],
            ['naam' => 'The Intercept', 'orientatie' => 'left', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://theintercept.com/feed/?rss'],
            ['naam' => 'The New Republic', 'orientatie' => 'left', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://newrepublic.com/rss.xml'],
            ['naam' => 'Rolling Stone: Politics', 'orientatie' => 'left', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.rollingstone.com/politics/feed/'],
            ['naam' => 'Talking Points Memo', 'orientatie' => 'left', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://talkingpointsmemo.com/feed'],
            ['naam' => 'Mediaite', 'orientatie' => 'left', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.mediaite.com/feed/'],
            ['naam' => 'Mother Jones', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.motherjones.com/feed/'],
            ['naam' => 'The New York Times: Politics', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://rss.nytimes.com/services/xml/rss/nyt/Politics.xml'],
            ['naam' => 'The Washington Post: Politics', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://feeds.washingtonpost.com/rss/politics'],
            ['naam' => 'NPR: Politics', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.npr.org/1014/rss.xml'],
            ['naam' => 'ABC News (VS): Politics', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://abcnews.go.com/abcnews/politicsheadlines'],
            ['naam' => 'CBS News: Politics', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.cbsnews.com/latest/rss/politics'],
            ['naam' => 'NBC News: Politics', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.nbcnews.com/nbcnews/public/politics'],
            ['naam' => 'LA Times: Politics', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.latimes.com/politics/rss2.0.xml'],
            ['naam' => 'Bloomberg: Politics', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.bloomberg.com/politics/news.rss'],
            ['naam' => 'Axios', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://api.axios.com/feed/'],
            ['naam' => 'The Hill', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://thehill.com/news/feed/'],
            ['naam' => 'Roll Call', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://rollcall.com/feed/'],
            ['naam' => 'Semafor', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.semafor.com/rss.xml'],
            ['naam' => 'The Free Press', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.thefp.com/feed'],
            ['naam' => 'The Daily Signal', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.dailysignal.com/feed/'],
            ['naam' => 'Washington Examiner', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.washingtonexaminer.com/feed/'],
            ['naam' => 'Washington Examiner: Policy', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.washingtonexaminer.com/section/policy/feed/'],
            ['naam' => 'Fox News: Politics', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://moxie.foxnews.com/google-publisher/politics.xml'],
            ['naam' => 'The Federalist', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://thefederalist.com/feed/'],
            ['naam' => 'Washington Free Beacon', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://freebeacon.com/feed/'],
            ['naam' => 'The American Spectator', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://spectator.org/feed/'],
            ['naam' => 'Just the News', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://justthenews.com/rss.xml'],

            // --- VS: algemeen nieuws ---
            ['naam' => 'Democracy Now!', 'orientatie' => 'left', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.democracynow.org/democracynow.rss'],
            ['naam' => 'Common Dreams', 'orientatie' => 'left', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.commondreams.org/feeds/feed.rss'],
            ['naam' => 'Truthout', 'orientatie' => 'left', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://truthout.org/feed/'],
            ['naam' => 'Raw Story', 'orientatie' => 'left', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.rawstory.com/feeds/feed.rss'],
            ['naam' => 'AlterNet', 'orientatie' => 'left', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.alternet.org/feeds/feed.rss'],
            ['naam' => 'The New Yorker: News', 'orientatie' => 'left', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.newyorker.com/feed/news'],
            ['naam' => 'The 19th', 'orientatie' => 'left', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://19thnews.org/feed/'],
            ['naam' => 'The New York Times', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml'],
            ['naam' => 'The Washington Post: National', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://feeds.washingtonpost.com/rss/national'],
            ['naam' => 'NBC News', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.nbcnews.com/nbcnews/public/news'],
            ['naam' => 'CBS News', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.cbsnews.com/feeds/rss/main.rss'],
            ['naam' => 'ABC News (VS)', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://abcnews.go.com/abcnews/topstories'],
            ['naam' => 'PBS NewsHour', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.pbs.org/newshour/feeds/rss/headlines'],
            ['naam' => 'The Christian Science Monitor', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://rss.csmonitor.com/feeds/all'],
            ['naam' => 'The Hill: Policy', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://thehill.com/policy/feed/'],
            ['naam' => 'Military Times', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.militarytimes.com/arc/outboundfeeds/rss/?outputType=xml'],
            ['naam' => 'Newsweek', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.newsweek.com/rss'],
            ['naam' => 'New York Post', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://nypost.com/feed'],
            ['naam' => 'The Washington Times', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.washingtontimes.com/rss/headlines/news/'],
            ['naam' => 'Fox Business', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://moxie.foxbusiness.com/google-publisher/latest.xml'],
            ['naam' => 'Fox News: US', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://moxie.foxnews.com/google-publisher/us.xml'],
            ['naam' => 'Breitbart', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://feeds.feedburner.com/breitbart'],
            ['naam' => 'The Daily Wire', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.dailywire.com/feeds/rss.xml'],
            ['naam' => 'TheBlaze', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.theblaze.com/feeds/feed.rss'],
            ['naam' => 'Newsmax', 'orientatie' => 'right', 'betrouwbaarheid' => 'low', 'feed_url' => 'https://www.newsmax.com/rss/Newsfront/16/'],

            // --- VS: lokaal en regionaal ---
            ['naam' => 'LA Times', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.latimes.com/local/rss2.0.xml'], // Californie
            ['naam' => 'CalMatters', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://calmatters.org/feed/'], // Californie
            ['naam' => 'The Texas Tribune', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.texastribune.org/feeds/main/'], // Texas

            // --- VS: wereldnieuws ---
            ['naam' => 'The New York Times: World', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://rss.nytimes.com/services/xml/rss/nyt/World.xml'],
            ['naam' => 'The Washington Post: World', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://feeds.washingtonpost.com/rss/world'],
            ['naam' => 'NPR: World', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.npr.org/1004/rss.xml'],
            ['naam' => 'NBC News: World', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.nbcnews.com/nbcnews/public/world'],
            ['naam' => 'CBS News: World', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.cbsnews.com/latest/rss/world'],
            ['naam' => 'LA Times: World & Nation', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.latimes.com/world-nation/rss2.0.xml'],
            ['naam' => 'CNBC: World', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.cnbc.com/id/100727362/device/rss/rss.html'],
            ['naam' => 'The Hill: International', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://thehill.com/policy/international/feed/'],
            ['naam' => 'Fox News: World', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://moxie.foxnews.com/google-publisher/world.xml'],

            // --- VK en Ierland ---
            ['naam' => 'The Mirror', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.mirror.co.uk/news/world-news/?service=rss'],
            ['naam' => 'BBC News: World', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.bbci.co.uk/news/world/rss.xml'],
            ['naam' => 'BBC News: US & Canada', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.bbci.co.uk/news/world/us_and_canada/rss.xml'],
            ['naam' => 'The Guardian: World', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.theguardian.com/world/rss'],
            ['naam' => 'The Guardian: UK', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.theguardian.com/uk-news/rss'],
            ['naam' => 'The Guardian: US', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.theguardian.com/us-news/rss'],
            ['naam' => 'RTÉ News', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.rte.ie/feeds/rss/?index=/news/'], // Ierland
            ['naam' => 'Sky News: World', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.skynews.com/feeds/rss/world.xml'],
            ['naam' => 'Sky News: UK', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://feeds.skynews.com/feeds/rss/uk.xml'],
            ['naam' => 'Daily Mail: World', 'orientatie' => 'right', 'betrouwbaarheid' => 'low', 'feed_url' => 'https://www.dailymail.co.uk/news/worldnews/index.rss'],
            ['naam' => 'The Sun', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.thesun.co.uk/news/feed/'],
            ['naam' => 'GB News', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.gbnews.com/feeds/news.rss'],

            // --- Europa ---
            ['naam' => 'Meduza (English)', 'orientatie' => 'left', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://meduza.io/rss/en/all'], // Rusland (onafhankelijk)
            ['naam' => 'Der Spiegel International', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.spiegel.de/international/index.rss'], // Duitsland
            ['naam' => 'Le Monde (English)', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.lemonde.fr/en/rss/une.xml'], // Frankrijk
            ['naam' => 'Kyiv Independent', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://kyivindependent.com/news-archive/rss/'], // Oekraine
            ['naam' => 'The Moscow Times', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.themoscowtimes.com/rss/news'], // Rusland (onafhankelijk)
            ['naam' => 'Deutsche Welle', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://rss.dw.com/xml/rss-en-world'], // Duitsland
            ['naam' => 'Euronews', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.euronews.com/rss'], // EU
            ['naam' => 'Ukrainska Pravda (English)', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.pravda.com.ua/eng/rss/'], // Oekraine
            ['naam' => 'Balkan Insight', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://balkaninsight.com/feed/'], // Balkan
            ['naam' => 'The Local (Europe)', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.thelocal.com/feeds/rss.php'], // EU (MBFC per landeditie)
            ['naam' => 'RFI (English)', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.rfi.fr/en/rss'], // Frankrijk
            ['naam' => 'France 24', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.france24.com/en/rss'], // Frankrijk
            ['naam' => 'DutchNews.nl', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.dutchnews.nl/feed/'], // Nederland

            // --- Midden-Oosten ---
            ['naam' => 'Haaretz', 'orientatie' => 'left', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.haaretz.com/srv/haaretz-latest-headlines'], // Israel
            ['naam' => 'Al Jazeera English', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.aljazeera.com/xml/rss/all.xml'], // Qatar
            ['naam' => 'The Times of Israel', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.timesofisrael.com/feed/'], // Israel
            ['naam' => 'Al-Monitor', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.al-monitor.com/rss'],
            ['naam' => 'Arab News', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.arabnews.com/rss.xml'], // Saoedi-Arabie
            ['naam' => 'Daily Sabah', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.dailysabah.com/rssFeed/10'], // Turkije (pro-regering)

            // --- Azie ---
            ['naam' => 'South China Morning Post', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.scmp.com/rss/91/feed/'], // Hongkong
            ['naam' => 'Hong Kong Free Press', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://hongkongfp.com/feed/'], // Hongkong
            ['naam' => 'NDTV: Top stories', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://feeds.feedburner.com/ndtvnews-top-stories'], // India
            ['naam' => 'The Hindu: International', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.thehindu.com/news/international/feeder/default.rss'], // India
            ['naam' => 'Dawn', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.dawn.com/feeds/home'], // Pakistan
            ['naam' => 'Rappler', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.rappler.com/feed/'], // Filipijnen
            ['naam' => 'Hindustan Times: World', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.hindustantimes.com/feeds/rss/world-news/rssfeed.xml'], // India
            ['naam' => 'The Japan Times', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.japantimes.co.jp/feed/'], // Japan
            ['naam' => 'The Diplomat', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://thediplomat.com/feed/'],
            ['naam' => 'Asia Times', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://asiatimes.com/feed/'],
            ['naam' => 'Bangkok Post: World', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.bangkokpost.com/rss/data/world.xml'], // Thailand
            ['naam' => 'The Times of India', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://timesofindia.indiatimes.com/rssfeedstopstories.cms'], // India
            ['naam' => 'The Straits Times: World', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.straitstimes.com/news/world/rss.xml'], // Singapore

            // --- Oceanie ---
            ['naam' => 'ABC News (Australia)', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.abc.net.au/news/feed/45910/rss.xml'],
            ['naam' => 'The Guardian Australia', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.theguardian.com/australia-news/rss'],
            ['naam' => 'The Sydney Morning Herald', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.smh.com.au/rss/feed.xml'],

            // --- Amerika (buiten de VS) ---
            ['naam' => 'Global News', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://globalnews.ca/feed/'], // Canada
            ['naam' => 'MercoPress', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://en.mercopress.com/rss/'], // Zuid-Amerika
            ['naam' => 'Mexico News Daily', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://mexiconewsdaily.com/feed/'], // Mexico
            ['naam' => 'Buenos Aires Times', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.batimes.com.ar/feed'], // Argentinie
            ['naam' => 'The Globe and Mail: World', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.theglobeandmail.com/arc/outboundfeeds/rss/category/world/'], // Canada

            // --- Afrika ---
            ['naam' => 'Punch', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://punchng.com/feed/'], // Nigeria
            ['naam' => 'Africanews', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.africanews.com/feed/rss'],
            ['naam' => 'Premium Times', 'orientatie' => 'left-center', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.premiumtimesng.com/feed'], // Nigeria
            ['naam' => 'Daily Maverick', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://www.dailymaverick.co.za/dmrss/'], // Zuid-Afrika
            ['naam' => 'AllAfrica', 'orientatie' => 'neutral', 'betrouwbaarheid' => 'high', 'feed_url' => 'https://allafrica.com/tools/headlines/rdf/latest/headlines.rdf'],

            // --- Staatsmedia ---
            ['naam' => 'RT', 'orientatie' => 'right-center', 'betrouwbaarheid' => 'low', 'feed_url' => 'https://www.rt.com/rss/news/'], // Rusland
            ['naam' => 'Tehran Times', 'orientatie' => 'right', 'betrouwbaarheid' => 'mixed', 'feed_url' => 'https://www.tehrantimes.com/rss'], // Iran
        ]);
    }
}
