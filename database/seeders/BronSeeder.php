<?php

namespace Database\Seeders;

use App\Models\Bron;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BronSeeder extends Seeder
{
    public function run(): void
{
    Bron::insert([
        // --- Politiek (VS) ---
        ['naam' => 'The New York Times: Politics', 'orientatie' => 'left-center', 'feed_url' => 'https://rss.nytimes.com/services/xml/rss/nyt/Politics.xml'], // VS - politiek
        ['naam' => 'The Washington Post: Politics', 'orientatie' => 'left-center', 'feed_url' => 'https://feeds.washingtonpost.com/rss/politics'], // VS - politiek
        ['naam' => 'The Hill', 'orientatie' => 'neutral', 'feed_url' => 'https://thehill.com/news/feed/'], // VS - politiek
        ['naam' => 'The Daily Signal', 'orientatie' => 'right', 'feed_url' => 'https://www.dailysignal.com/feed/'], // VS - politiek
        ['naam' => 'Washington Examiner', 'orientatie' => 'right', 'feed_url' => 'https://www.washingtonexaminer.com/feed/'], // VS - politiek
        ['naam' => 'Fox News: Politics', 'orientatie' => 'right', 'feed_url' => 'https://moxie.foxnews.com/google-publisher/politics.xml'], // VS - politiek

        // --- Wereldwijd ---
        ['naam' => 'BBC News: World', 'orientatie' => 'left-center', 'feed_url' => 'https://feeds.bbci.co.uk/news/world/rss.xml'], // VK - wereldwijd
        ['naam' => 'The Guardian: World', 'orientatie' => 'left-center', 'feed_url' => 'https://www.theguardian.com/world/rss'], // VK - wereldwijd
        ['naam' => 'Al Jazeera English', 'orientatie' => 'left-center', 'feed_url' => 'https://www.aljazeera.com/xml/rss/all.xml'], // Qatar - wereldwijd
        ['naam' => 'The New York Times: World', 'orientatie' => 'left-center', 'feed_url' => 'https://rss.nytimes.com/services/xml/rss/nyt/World.xml'], // VS - wereldwijd
        ['naam' => 'The Washington Post: World', 'orientatie' => 'left-center', 'feed_url' => 'https://feeds.washingtonpost.com/rss/world'], // VS - wereldwijd
        ['naam' => 'South China Morning Post', 'orientatie' => 'left-center', 'feed_url' => 'https://www.scmp.com/rss/91/feed/'], // Hongkong - wereldwijd
        ['naam' => 'ABC News (Australia)', 'orientatie' => 'left-center', 'feed_url' => 'https://www.abc.net.au/news/feed/45910/rss.xml'], // Australie - wereldwijd
        ['naam' => 'NDTV: Top stories', 'orientatie' => 'left-center', 'feed_url' => 'https://feeds.feedburner.com/ndtvnews-top-stories'], // India - wereldwijd
        ['naam' => 'Sky News: World', 'orientatie' => 'neutral', 'feed_url' => 'https://feeds.skynews.com/feeds/rss/world.xml'], // VK - wereldwijd
        ['naam' => 'France 24', 'orientatie' => 'neutral', 'feed_url' => 'https://www.france24.com/en/rss'], // Frankrijk - wereldwijd

        // --- Lokaal (VS) ---
        ['naam' => 'NBC News', 'orientatie' => 'left-center', 'feed_url' => 'https://feeds.nbcnews.com/nbcnews/public/news'], // VS - lokaal
        ['naam' => 'CBS News', 'orientatie' => 'left-center', 'feed_url' => 'https://www.cbsnews.com/feeds/rss/main.rss'], // VS - lokaal
        ['naam' => 'The New York Times', 'orientatie' => 'left-center', 'feed_url' => 'https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml'], // VS - lokaal
        ['naam' => 'LA Times', 'orientatie' => 'left-center', 'feed_url' => 'https://www.latimes.com/local/rss2.0.xml'], // VS - lokaal
        ['naam' => 'New York Post', 'orientatie' => 'right-center', 'feed_url' => 'https://nypost.com/feed'], // VS - lokaal
    ]);
}
}
