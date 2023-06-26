<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{

    protected $fillable = [
        'nama', 'tanggal', 'iskeluar', 'jumlah', 'kategori_id', 'rekening_id', 'parent_id'
    ];

    public function kategori(): BelongsTo {
        return $this->belongsTo(Kategori::class);
    }

    public function rekening(): BelongsTo {
        return $this->belongsTo(Rekening::class);
    }

    public function fotos(): HasMany {
        return $this->hasMany(TransaksiFoto::class);
    }

}
