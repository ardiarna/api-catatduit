<?php

namespace App\Repositories\Elo;

use App\Repositories\AnggaranRepository;
use App\Models\Anggaran;

class AnggaranImplement implements AnggaranRepository {

    protected $model;

    function __construct(Anggaran $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findByPeriode($kategori_id, $tahun, $bulan) {
        return $this->model
            ->where('kategori_id', $kategori_id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();
    }

    public function findAll(array $inputs) {
        $parent_id = $inputs['parent_id'];
        $tahun = $inputs['tahun'];
        $bulan = $inputs['bulan'];
        $kategori_id = $inputs['kategori_id'];

        $models = $this->model->where('parent_id', $parent_id);
        if($tahun) {
            $models = $models->where('tahun', $tahun);
        }
        if($bulan) {
            $models = $models->where('bulan', $bulan);
        }
        if($kategori_id) {
            $models = $models->where('kategori_id', $kategori_id);
        }
        $models = $models->get();
        return $models;
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, $jumlah) {
        $model = $this->model->findOrFail($id);
        $model->jumlah = $jumlah;
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
