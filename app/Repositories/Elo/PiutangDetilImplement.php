<?php

namespace App\Repositories\Elo;

use App\Repositories\PiutangDetilRepository;
use App\Models\PiutangDetil;

class PiutangDetilImplement implements PiutangDetilRepository {

    protected $model;

    function __construct(PiutangDetil $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $model->piutang;
            $model->rekening;
        }
        return $model;
    }

    public function findAll(array $inputs) {
        $piutang_id = $inputs['piutang_id'];
        $isbayar = $inputs['isbayar'];
        $rekening_id = $inputs['rekening_id'];
        $nama = $inputs['nama'];
        $direction = $inputs['direction'];

        $models = $this->model->where('piutang_id', $piutang_id);
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
            $model->piutang;
            $model->rekening;
        }
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->piutang;
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
        $model->piutang;
        $model->rekening;
        return $model;
    }

    public function updateTransaksiId($id, $transaksi_id) {
        $model = $this->model->findOrFail($id);
        $model->transaksi_id = $transaksi_id;
        $model->save();
        $model->piutang;
        $model->rekening;
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function deletesByPiutangId($piutang_id) {
        return $this->model->where('piutang_id', $piutang_id)->delete();
    }

}
