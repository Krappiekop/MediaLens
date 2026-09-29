<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gebeurtenis extends Model
{
    protected $table = 'gebeurtenissen';
    public function artikelen()
    {
        return $this->hasMany(Artikel::class);
    }

    public function samenvatting()
    {
        return $this->hasOne(Samenvatting::class);
    }
}
