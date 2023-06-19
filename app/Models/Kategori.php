<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kategori extends Model
{

    protected $fillable = [
        'jenis', 'nama', 'ikon', 'rekening_id', 'parent_id'
    ];

    public function rekening(): BelongsTo {
        return $this->belongsTo(Rekening::class);
    }

}
