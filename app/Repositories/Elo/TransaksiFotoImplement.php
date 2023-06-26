<?php

namespace App\Repositories\Elo;

use App\Repositories\TransaksiFotoRepository;
use App\Models\TransaksiFoto;

class TransaksiFotoImplement implements TransaksiFotoRepository {

    protected $model;

    function __construct(TransaksiFoto $model) {
        $this->model = $model;
    }

    public function findAll($transaksi_id) {
        return $this->model->where('transaksi_id', $transaksi_id)->orderBy('created_at')->get();
    }

    public function upsert($nama, $transaksi_id) {
        return $this->model->updateOrCreate(
            ['nama' => $nama],
            ['transaksi_id' => $transaksi_id]
        );
    }

    public function delete($nama) {
        return $this->model->destroy($nama);
    }

}
