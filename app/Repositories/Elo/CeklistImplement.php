<?php

namespace App\Repositories\Elo;

use App\Repositories\CeklistRepository;
use App\Models\Ceklist;

class CeklistImplement implements CeklistRepository {

    protected $model;

    function __construct(Ceklist $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $detils = $this->model->find($id)->detils()->orderBy('nama')->get();
            $model->detils = $detils;
        }
        return $model;
    }

    public function findAll(array $inputs) {
        $parent_id = $inputs['parent_id'];
        $nama = $inputs['nama'];

        $models = $this->model->where('parent_id', $parent_id);
        if($nama) {
            $models = $models->where('nama', 'LIKE', '%'.$nama.'%');
        }
        $models = $models->orderBy('nama')->get();
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        return $model;
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->nama = $inputs['nama'];
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
