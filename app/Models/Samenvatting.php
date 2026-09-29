<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Samenvatting extends Model
{
    protected $table = 'samenvattingen';
    public function gebeurtenis()
    {
        return $this->belongsTo(Gebeurtenis::class);
    }
}
