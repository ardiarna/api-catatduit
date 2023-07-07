<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiutangDetil extends Model
{

    protected $fillable = [
        'nama', 'tanggal', 'isbayar', 'jumlah', 'piutang_id', 'rekening_id', 'transaksi_id'
    ];

    public function piutang(): BelongsTo {
        return $this->belongsTo(Piutang::class);
    }

    public function rekening(): BelongsTo {
        return $this->belongsTo(Rekening::class);
    }

}
