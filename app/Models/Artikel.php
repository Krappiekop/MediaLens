<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Artikel extends Model
{
    protected $table = 'artikelen';
    protected $fillable = [
        'titel',
        'publicatiedatum',
        'volledige_tekst',
        'url',
        'bron_id',
        'gebeurtenis_id',
    ];
    
    public function bron()
    {
        return $this->belongsTo(Bron::class);
    }

    public function gebeurtenis()
    {
        return $this->belongsTo(Gebeurtenis::class);
    }
}
