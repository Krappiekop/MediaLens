<?php

namespace Database\Seeders;

use App\Models\Bron;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BronSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Bron::insert([
            ['naam' => 'The Hill', 'orientatie' => 'neutral', 'feed_url' => 'https://thehill.com/news/feed/'],
            ['naam' => 'The Nation', 'orientatie' => 'left', 'feed_url' => 'https://www.thenation.com/feed/?post_type=article'],
            // ['naam' => 'The New York Times: Politics', 'orientatie' => 'left-center', 'feed_url' => 'https://rss.nytimes.com/services/xml/rss/nyt/Politics.xml'],
            // ['naam' => 'Politico', 'orientatie' => 'left-center', 'feed_url' => 'https://rss.politico.com/politics-news.xml'],
            // ['naam' => 'The Washington Post: Politics', 'orientatie' => 'left-center', 'feed_url' => 'https://feeds.washingtonpost.com/rss/politics'],
            // ['naam' => 'Drudge Report', 'orientatie' => 'right-center', 'feed_url' => 'https://feedpress.me/drudgereportfeed'],
            // ['naam' => 'The Daily Signal', 'orientatie' => 'right', 'feed_url' => 'https://www.dailysignal.com/feed/'],
            // ['naam' => 'Washington Examiner', 'orientatie' => 'right', 'feed_url' => 'https://www.washingtonexaminer.com/feed/'],
            ['naam' => 'Fox News: Politics', 'orientatie' => 'right', 'feed_url' => 'https://moxie.foxnews.com/google-publisher/politics.xml'],
            // ['naam' => 'National Review', 'orientatie' => 'right', 'feed_url' => 'https://www.nationalreview.com/feed/'],
        ]);
    }
}
