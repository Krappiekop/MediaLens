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
            ['naam' => 'CNN', 'orientatie' => 'left'],
            ['naam' => 'HuffPost', 'orientatie' => 'left'],
            ['naam' => 'MSNBC', 'orientatie' => 'left'],
            ['naam' => 'The New York Times', 'orientatie' => 'left-center'],
            ['naam' => 'The Washington Post', 'orientatie' => 'left-center'],
            ['naam' => 'The Guardian', 'orientatie' => 'left-center'],
            ['naam' => 'NPR', 'orientatie' => 'left-center'],
            ['naam' => 'BBC', 'orientatie' => 'left-center'],
            ['naam' => 'Reuters', 'orientatie' => 'neutral'],
            ['naam' => 'The Economist', 'orientatie' => 'neutral'],
            ['naam' => 'The Wall Street Journal', 'orientatie' => 'right-center'],
            ['naam' => 'New York Post', 'orientatie' => 'right-center'],
            ['naam' => 'The Times', 'orientatie' => 'right-center'],
            ['naam' => 'Fox News', 'orientatie' => 'right'],
            ['naam' => 'Breitbart', 'orientatie' => 'right'],
        ]);
    }
}
