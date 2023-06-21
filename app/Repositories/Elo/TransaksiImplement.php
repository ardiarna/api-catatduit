<?php

namespace App\Repositories\Elo;

use App\Repositories\TransaksiRepository;
use App\Models\Transaksi;

class TransaksiImplement implements TransaksiRepository {

    protected $model;

    function __construct(Transaksi $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $model->kategori;
            $model->rekening;
        }
        return $model;
    }

    public function findAll(array $inputs) {
        $parent_id = $inputs['parent_id'];
        $tgl_awal = $inputs['tgl_awal'];
        $tgl_akhir = $inputs['tgl_akhir'];
        $iskeluar = $inputs['iskeluar'];
        $kategori_id = $inputs['kategori_id'];
        $rekening_id = $inputs['rekening_id'];
        $nama = $inputs['nama'];
        $direction = $inputs['direction'];

        $models = $this->model->where('parent_id', $parent_id)
            ->where('tanggal', '>=', $tgl_awal)
            ->where('tanggal', '<=', $tgl_akhir);
        if($iskeluar) {
            $models = $models->where('iskeluar', $iskeluar);
        }
        if($kategori_id) {
            $models = $models->where('kategori_id', $kategori_id);
        }
        if($rekening_id) {
            $models = $models->where('rekening_id', $rekening_id);
        }
        if($nama) {
            $models = $models->where('nama', 'LIKE', '%'.$nama.'%');
        }
        $models = $models->orderBy('tanggal', $direction)->get();
        foreach ($models as $model) {
            $model->kategori;
            $model->rekening;
        }
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->kategori;
        $model->rekening;
        return $model;
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->nama = $inputs['nama'];
        $model->tanggal = $inputs['tanggal'];
        $model->jumlah = $inputs['jumlah'];
        $model->kategori_id = $inputs['kategori_id'];
        $model->rekening_id = $inputs['rekening_id'];
        $model->save();
        $model->kategori;
        $model->rekening;
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
