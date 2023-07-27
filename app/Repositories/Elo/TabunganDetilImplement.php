<?php

namespace App\Repositories\Elo;

use App\Repositories\TabunganDetilRepository;
use App\Models\TabunganDetil;

class TabunganDetilImplement implements TabunganDetilRepository {

    protected $model;

    function __construct(TabunganDetil $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $model->tabungan;
            $model->rekening;
        }
        return $model;
    }

    public function findAll(array $inputs) {
        $tabungan_id = $inputs['tabungan_id'];
        $isambil = $inputs['isambil'];
        $rekening_id = $inputs['rekening_id'];
        $nama = $inputs['nama'];
        $direction = $inputs['direction'];

        $models = $this->model->where('tabungan_id', $tabungan_id);
        if($isambil) {
            $models = $models->where('isambil', $isambil);
        }
        if($rekening_id) {
            $models = $models->where('rekening_id', $rekening_id);
        }
        if($nama) {
            $models = $models->where('nama', 'LIKE', '%'.$nama.'%');
        }
        $models = $models->orderBy('tanggal', $direction)->get();
        foreach ($models as $model) {
            $model->tabungan;
            $model->rekening;
        }
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->tabungan;
        $model->rekening;
        return $model;
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->nama = $inputs['nama'];
        $model->tanggal = $inputs['tanggal'];
        $model->jumlah = $inputs['jumlah'];
        $model->rekening_id = $inputs['rekening_id'];
        $model->save();
        $model->tabungan;
        $model->rekening;
        return $model;
    }

    public function updateTransaksiId($id, $transaksi_id) {
        $model = $this->model->findOrFail($id);
        $model->transaksi_id = $transaksi_id;
        $model->save();
        $model->tabungan;
        $model->rekening;
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function deletesByTabunganId($tabungan_id) {
        return $this->model->where('tabungan_id', $tabungan_id)->delete();
    }

}
