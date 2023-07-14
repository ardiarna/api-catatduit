<?php

namespace App\Repositories\Elo;

use App\Repositories\RekeningRepository;
use App\Models\Rekening;

class RekeningImplement implements RekeningRepository {

    protected $model;

    function __construct(Rekening $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $model->bank;
            // $model->adjusts;
        }
        return $model;
    }

    public function findAll($inputs) {
        $parent_id = $inputs['parent_id'];
        $jenis = $inputs['jenis'];
        $bank_id = $inputs['bank_id'];

        $models = $this->model->where('parent_id', $parent_id);
        if($jenis) {
            $models = $models->where('jenis', $jenis);
        }
        if($bank_id) {
            $models = $models->where('bank_id', $bank_id);
        }
        $models = $models->orderBy('nama')->get();
        foreach ($models as $model) {
            $model->bank;
            // $model->adjusts;
        }
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->bank;
        // $model->adjusts;
        return $model;
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->jenis = $inputs['jenis'];
        $model->nama = $inputs['nama'];
        $model->bank_id = $inputs['bank_id'];
        $model->saldo_endap = $inputs['saldo_endap'];
        $model->keterangan = $inputs['keterangan'];
        $model->save();
        $model->bank;
        // $model->adjusts;
        return $model;
    }

    public function editSaldo($id, $saldo) {
        $model = $this->model->findOrFail($id);
        $model->saldo = $saldo;
        $model->save();
        $model->bank;
        // $model->adjusts;
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function getTotalSaldo($parent_id) {
        return $this->model->where('parent_id', $parent_id)->sum('saldo');;
    }

}
