<?php

namespace App\Repositories\Elo;

use App\Repositories\CeklistDetilRepository;
use App\Models\CeklistDetil;

class CeklistDetilImplement implements CeklistDetilRepository {

    protected $model;

    function __construct(CeklistDetil $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $model->ceklist;
        }
        return $model;
    }

    public function findAll(array $inputs) {
        $ceklist_id = $inputs['ceklist_id'];
        $isceklist = $inputs['isceklist'];
        $isaktif = $inputs['isaktif'];
        $nama = $inputs['nama'];

        $models = $this->model->where('ceklist_id', $ceklist_id);
        if($isceklist) {
            $models = $models->where('isceklist', $isceklist);
        }
        if($isaktif) {
            $models = $models->where('isaktif', $isaktif);
        }
        if($nama) {
            $models = $models->where('nama', 'LIKE', '%'.$nama.'%');
        }
        $models = $models->orderBy('nama')->get();
        foreach ($models as $model) {
            $model->ceklist;
        }
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->ceklist;
        return $model;
    }

    public function updateNama($id, $nama) {
        $model = $this->model->findOrFail($id);
        $model->nama = $nama;
        $model->save();
        $model->ceklist;
        return $model;
    }

    public function updateIsceklist($id, $isceklist) {
        $model = $this->model->findOrFail($id);
        $model->isceklist = $isceklist;
        $model->save();
        $model->ceklist;
        return $model;
    }

    public function updateIsaktif($id, $isaktif) {
        $model = $this->model->findOrFail($id);
        $model->isaktif = $isaktif;
        $model->save();
        $model->ceklist;
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function deletesByCeklistId($ceklist_id) {
        return $this->model->where('ceklist_id', $ceklist_id)->delete();
    }

}
