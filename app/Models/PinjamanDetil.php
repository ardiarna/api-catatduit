<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinjamanDetil extends Model
{

    protected $fillable = [
        'nama', 'tanggal', 'isbayar', 'jumlah', 'pinjaman_id', 'rekening_id'
    ];

    public function pinjaman(): BelongsTo {
        return $this->belongsTo(Pinjaman::class);
    }

    public function rekening(): BelongsTo {
        return $this->belongsTo(Rekening::class);
    }

}
