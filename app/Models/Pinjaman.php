<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pinjaman extends Model
{

    protected $table = 'pinjamans';

    protected $fillable = [
        'nama', 'tanggal', 'tempo', 'keterangan', 'jumlah', 'bayar', 'parent_id'
    ];

    public function detils(): HasMany {
        return $this->hasMany(PinjamanDetil::class);
    }

}
