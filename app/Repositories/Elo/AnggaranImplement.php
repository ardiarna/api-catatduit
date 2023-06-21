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
        $model = $this->model->find($id);
        if($model != null) {
            $model->kategori;
        }
        return $model;
    }

    public function findByPeriode($kategori_id, $tahun, $bulan) {
        $model = $this->model
            ->where('kategori_id', $kategori_id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();
        if($model != null) {
            $model->kategori;
        }
        return $model;
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
        foreach ($models as $model) {
            $model->kategori;
        }
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->kategori;
        return $model;
    }

    public function update($id, $jumlah) {
        $model = $this->model->findOrFail($id);
        $model->jumlah = $jumlah;
        $model->save();
        $model->kategori;
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
