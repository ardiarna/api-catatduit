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
        return $this->model->find($id);
    }

    public function findAll($inputs) {
        $parent_id = $inputs['parent_id'];
        $models = $this->model->where('parent_id', $parent_id)->get();
        return $models;
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->jenis = $inputs['jenis'];
        $model->nama = $inputs['nama'];
        $model->bank_id = $inputs['bank_id'];
        $model->saldo_endap = $inputs['saldo_endap'];
        $model->keterangan = $inputs['keterangan'];
        $model->save();
        return $model;
    }

    public function editSaldo($id, $saldo) {
        $model = $this->model->findOrFail($id);
        $model->saldo = $saldo;
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
