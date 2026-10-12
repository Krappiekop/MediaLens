<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bron extends Model
{
    protected $table = 'bronnen';

    protected $fillable = [
        'naam',
        'orientatie',
    ];

    public function artikelen()
    {
        return $this->hasMany(Artikel::class);
    }
}
