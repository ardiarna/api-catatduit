<?php

namespace App\Repositories\Elo;

use App\Repositories\PinjamanDetilRepository;
use App\Models\PinjamanDetil;

class PinjamanDetilImplement implements PinjamanDetilRepository {

    protected $model;

    function __construct(PinjamanDetil $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $model->pinjaman;
            $model->rekening;
        }
        return $model;
    }

    public function findAll(array $inputs) {
        $pinjaman_id = $inputs['pinjaman_id'];
        $isbayar = $inputs['isbayar'];
        $rekening_id = $inputs['rekening_id'];
        $nama = $inputs['nama'];
        $direction = $inputs['direction'];

        $models = $this->model->where('pinjaman_id', $pinjaman_id);
        if($isbayar) {
            $models = $models->where('isbayar', $isbayar);
        }
        if($rekening_id) {
            $models = $models->where('rekening_id', $rekening_id);
        }
        if($nama) {
            $models = $models->where('nama', 'LIKE', '%'.$nama.'%');
        }
        $models = $models->orderBy('tanggal', $direction)->get();
        foreach ($models as $model) {
            $model->pinjaman;
            $model->rekening;
        }
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->pinjaman;
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
        $model->pinjaman;
        $model->rekening;
        return $model;
    }

    public function updateTransaksiId($id, $transaksi_id) {
        $model = $this->model->findOrFail($id);
        $model->transaksi_id = $transaksi_id;
        $model->save();
        $model->pinjaman;
        $model->rekening;
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function deletesByPinjamanId($pinjaman_id) {
        return $this->model->where('pinjaman_id', $pinjaman_id)->delete();
    }

}
