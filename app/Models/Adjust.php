<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjust extends Model
{

    protected $fillable = [
        'nama', 'tanggal', 'iskeluar', 'jumlah', 'rekening_id', 'parent_id'
    ];

}
