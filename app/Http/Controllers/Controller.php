<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Controller
{
    /**
     * De zoekterm uit ?q=, getrimd en ingekort tot 100 tekens. Geen string (bijv. ?q[]=x) wordt een lege zoekterm.
     */
    protected function zoekterm(Request $request): string
    {
        $zoekterm = $request->query('q');

        return is_string($zoekterm) ? Str::limit(trim($zoekterm), 100, '') : '';
    }

    /**
     * LIKE '%zoekterm%' waarin % en _ uit de zoekterm letterlijk tellen; anders vindt ?q=% alles.
     * Escape-teken ! in plaats van \, omdat MySQL en SQLite een backslash verschillend lezen.
     */
    protected function bevat(Builder $query, string $kolom, string $zoekterm, string $boolean = 'and'): Builder
    {
        $patroon = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $zoekterm).'%';

        return $query->whereRaw("{$kolom} like ? escape '!'", [$patroon], $boolean);
    }
}
