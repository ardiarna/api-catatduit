<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabunganDetil extends Model
{

    protected $fillable = [
        'nama', 'tanggal', 'isambil', 'jumlah', 'tabungan_id', 'rekening_id', 'transaksi_id'
    ];

    public function tabungan(): BelongsTo {
        return $this->belongsTo(Tabungan::class);
    }

    public function rekening(): BelongsTo {
        return $this->belongsTo(Rekening::class);
    }

}
