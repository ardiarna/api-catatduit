<?php

namespace App\Repositories\Elo;

use App\Repositories\PinjamanRepository;
use App\Models\Pinjaman;

class PinjamanImplement implements PinjamanRepository {

    protected $model;

    function __construct(Pinjaman $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $model->detils;
            foreach($model->detils as $detil) {
                $detil->rekening;
            }
        }
        return $model;
    }

    public function findAll(array $inputs) {
        $parent_id = $inputs['parent_id'];
        $nama = $inputs['nama'];
        $keterangan = $inputs['keterangan'];
        $direction = $inputs['direction'];

        $models = $this->model->where('parent_id', $parent_id);
        if($nama) {
            $models = $models->where('nama', 'LIKE', '%'.$nama.'%');
        }
        if($keterangan) {
            $models = $models->where('keterangan', 'LIKE', '%'.$keterangan.'%');
        }
        $models = $models->orderBy('tanggal', $direction)->get();
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->detils;
        foreach($model->detils as $detil) {
            $detil->rekening;
        }
        return $model;
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->nama = $inputs['nama'];
        $model->tanggal = $inputs['tanggal'];
        $model->tempo = $inputs['tempo'];
        $model->keterangan = $inputs['keterangan'];
        $model->save();
        $model->detils;
        foreach($model->detils as $detil) {
            $detil->rekening;
        }
        return $model;
    }

    public function editJumlah($id, $jumlah) {
        $model = $this->model->findOrFail($id);
        $model->jumlah = $jumlah;
        $model->save();
        return $model;
    }

    public function editBayar($id, $bayar) {
        $model = $this->model->findOrFail($id);
        $model->bayar = $bayar;
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
