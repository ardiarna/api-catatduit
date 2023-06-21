<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{

    protected $fillable = [
        'nama', 'tanggal', 'jumlah', 'rekasal_id', 'rektuju_id', 'parent_id'
    ];

    public function rekasal(): BelongsTo {
        return $this->belongsTo(Rekening::class, 'rekasal_id');
    }

    public function rektuju(): BelongsTo {
        return $this->belongsTo(Rekening::class, 'rektuju_id');
    }

}
