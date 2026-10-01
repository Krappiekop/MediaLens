<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Samenvatting extends Model
{
    protected $table = 'samenvattingen';
    protected $fillable = [
        'kernfeiten',
        'betrokkenen',
        'overeenstemming',
        'verschil',
        'gebeurtenis_id',
    ];
    
    public function gebeurtenis()
    {
        return $this->belongsTo(Gebeurtenis::class);
    }
}
