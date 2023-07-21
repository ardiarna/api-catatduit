<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CeklistDetil extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'nama', 'isceklist', 'isaktif', 'ceklist_id'
    ];

    public function ceklist(): BelongsTo {
        return $this->belongsTo(Ceklist::class);
    }

}
