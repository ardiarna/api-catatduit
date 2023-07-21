<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ceklist extends Model
{

    protected $fillable = [
        'nama', 'parent_id'
    ];

    public function detils(): HasMany {
        return $this->hasMany(CeklistDetil::class);
    }

}
