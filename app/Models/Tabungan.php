<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tabungan extends Model
{

    protected $fillable = [
        'nama', 'tanggal', 'tempo', 'keterangan', 'jumlah', 'ambil', 'parent_id'
    ];

    public function detils(): HasMany {
        return $this->hasMany(TabunganDetil::class);
    }

}
