<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Artikel extends Model
{
    protected $table = 'artikelen';

    protected $fillable = [
        'titel',
        'publicatiedatum',
        'volledige_tekst',
        'url',
        'afbeelding_url',
        'afbeelding_credit',
        'bron_id',
        'gebeurtenis_id',
        'embedding',
        'ai_orientatie',
        'ai_politiek',
        'ai_zekerheid',
        'ai_onderbouwing',
        'ai_model',
        'ai_geschat_op',
    ];

    protected function casts(): array
    {
        return [
            'publicatiedatum' => 'datetime',
            'embedding' => 'array',
            'ai_politiek' => 'boolean',
            'ai_zekerheid' => 'integer',
            'ai_geschat_op' => 'datetime',
        ];
    }

    /**
     * De URL om naar te linken, alleen als het een http- of https-adres is. Een feed kan in <link> ook
     * bijvoorbeeld javascript:… zetten; Blade escapet HTML, maar niet het schema van een URL.
     */
    public function veiligeUrl(): ?string
    {
        $url = trim((string) $this->url);
        $schema = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($schema, ['http', 'https'], true) ? $url : null;
    }

    /**
     * Weergavegroep van de AI-schatting: Links, Midden, Rechts, Geen lading, of null als er nog geen schatting is.
     * Schattingen van vóór de kolom ai_politiek (null) tellen als politiek geladen.
     */
    public function aiBakje(): ?string
    {
        if ($this->ai_orientatie === null) {
            return null;
        }

        return $this->ai_politiek === false ? 'Geen lading' : Bron::bakjeVan($this->ai_orientatie);
    }

    public function bron(): BelongsTo
    {
        return $this->belongsTo(Bron::class);
    }

    public function gebeurtenis(): BelongsTo
    {
        return $this->belongsTo(Gebeurtenis::class);
    }
}
