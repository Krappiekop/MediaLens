<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bron extends Model
{
    protected $table = 'bronnen';

    protected $fillable = [
        'naam',
        'orientatie',
        'betrouwbaarheid',
    ];

    public function artikelen()
    {
        return $this->hasMany(Artikel::class);
    }

    /**
     * Weergavegroep van het MBFC-label: Links, Midden, Rechts of Onbekend.
     */
    public function bakje(): string
    {
        return match ($this->orientatie) {
            'left', 'left-center' => 'Links',
            'neutral' => 'Midden',
            'right-center', 'right' => 'Rechts',
            default => 'Onbekend',
        };
    }

    /**
     * Nederlandse weergave van MBFC factual_reporting (high/mixed/low).
     */
    public function betrouwbaarheidLabel(): string
    {
        return match ($this->betrouwbaarheid) {
            'high' => 'Hoog',
            'mixed' => 'Gemengd',
            'low' => 'Laag',
            default => 'Onbekend',
        };
    }
}
