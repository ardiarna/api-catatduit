<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiFoto extends Model
{

    protected $primaryKey = 'nama';

    protected $keyType = 'string';

    public $incrementing = false;


    protected $fillable = [
        'nama', 'transaksi_id'
    ];

}
