<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rekening extends Model
{

    protected $fillable = [
        'jenis', 'nama', 'saldo', 'bank_id', 'saldo_endap', 'keterangan', 'parent_id'
    ];

    public function bank(): BelongsTo {
        return $this->belongsTo(Bank::class);
    }

    public function adjusts(): HasMany {
        return $this->hasMany(Adjust::class);
    }

}
