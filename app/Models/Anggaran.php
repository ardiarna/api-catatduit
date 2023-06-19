<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anggaran extends Model
{

    protected $fillable = [
        'tahun', 'bulan', 'kategori_id', 'jumlah', 'parent_id'
    ];

    public function kategori(): BelongsTo {
        return $this->belongsTo(Kategori::class);
    }

}
