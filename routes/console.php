<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Elk uur nieuwe artikelen ophalen en daarna samenvatten. Taken op hetzelfde moment draaien
 * na elkaar in deze volgorde, dus de samenvattingen zien de net opgehaalde artikelen.
 * withoutOverlapping: loopt een run uit (trage feeds of AI), dan start het volgende uur geen tweede.
 * Lokaal draaien met `php artisan schedule:work`.
 */
Schedule::command('artikelen:ophalen')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('samenvattingen:genereren')
    ->hourly()
    ->withoutOverlapping();
