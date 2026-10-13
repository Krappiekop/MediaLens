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
    ];

    protected function casts(): array
    {
        return [
            'publicatiedatum' => 'datetime',
            'embedding' => 'array',
        ];
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
