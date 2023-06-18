<?php

namespace App\Repositories\Elo;

use App\Repositories\AdjustRepository;
use App\Models\Adjust;

class AdjustImplement implements AdjustRepository {

    protected $model;

    function __construct(Adjust $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll(array $inputs) {
        $parent_id = $inputs['parent_id'];
        $tgl_awal = $inputs['tgl_awal'];
        $tgl_akhir = $inputs['tgl_akhir'];
        $iskeluar = $inputs['iskeluar'];
        $rekening_id = $inputs['rekening_id'];
        $models = $this->model->where('parent_id', $parent_id)
            ->where('tanggal', '>=', $tgl_awal)
            ->where('tanggal', '<=', $tgl_akhir);
        if($iskeluar) {
            $models = $models->where('iskeluar', $iskeluar);
        }
        if($rekening_id) {
            $models = $models->where('rekening_id', $rekening_id);
        }
        $models = $models->get();
        return $models;
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

}
