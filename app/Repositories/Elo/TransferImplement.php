<?php

namespace App\Repositories\Elo;

use App\Repositories\TransferRepository;
use App\Models\Transfer;

class TransferImplement implements TransferRepository {

    protected $model;

    function __construct(Transfer $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $model->rekasal;
            $model->rektuju;
        }
        return $model;
    }

    public function findAll(array $inputs) {
        $parent_id = $inputs['parent_id'];
        $tgl_awal = $inputs['tgl_awal'];
        $tgl_akhir = $inputs['tgl_akhir'];
        $rekasal_id = $inputs['rekasal_id'];
        $rektuju_id = $inputs['rektuju_id'];
        $nama = $inputs['nama'];
        $direction = $inputs['direction'];

        $models = $this->model->where('parent_id', $parent_id)
            ->where('tanggal', '>=', $tgl_awal)
            ->where('tanggal', '<=', $tgl_akhir);
        if($rekasal_id) {
            $models = $models->where('rekasal_id', $rekasal_id);
        }
        if($rektuju_id) {
            $models = $models->where('rektuju_id', $rektuju_id);
        }
        if($nama) {
            $models = $models->where('nama', 'LIKE', '%'.$nama.'%');
        }
        $models = $models->orderBy('tanggal', $direction)->get();
        foreach ($models as $model) {
            $model->rekasal;
            $model->rektuju;
        }
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->rekasal;
        $model->rektuju;
        return $model;
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->nama = $inputs['nama'];
        $model->tanggal = $inputs['tanggal'];
        $model->jumlah = $inputs['jumlah'];
        $model->rekasal_id = $inputs['rekasal_id'];
        $model->rektuju_id = $inputs['rektuju_id'];
        $model->save();
        $model->rekasal;
        $model->rektuju;
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
