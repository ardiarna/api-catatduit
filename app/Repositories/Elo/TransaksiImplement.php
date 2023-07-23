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
            $model->fotos;
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
        $isall = $inputs['isall'];
        $direction = $inputs['direction'];

        $models = $this->model->where('parent_id', $parent_id)
            ->whereBetween('tanggal', [$tgl_awal, $tgl_akhir]);
        if($isall != 'Y') {
            $models = $models->whereNotIn('kategori_id', [3, 4, 5, 6]);
        }
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

    public function findByPeriode(array $inputs) {
        $parent_id = $inputs['parent_id'];
        $tahun = $inputs['tahun'];
        $bulan = $inputs['bulan'];
        $kategori_id = $inputs['kategori_id'];
        $rekening_id = $inputs['rekening_id'];

        $models = $this->model->where('parent_id', $parent_id)
            ->whereRaw('YEAR(tanggal) = ?', [$tahun])
            ->whereRaw('MONTH(tanggal) = ?', [$bulan]);
        if($kategori_id) {
            $models = $models->where('kategori_id', $kategori_id);
        }
        if($rekening_id) {
            $models = $models->where('rekening_id', $rekening_id);
        }
        $models = $models->orderBy('tanggal', 'desc')->get();
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

    public function summaryPeriode($parent_id, $tahun, $bulan) {
        $model = $this->model
            ->selectRaw('SUM(CASE WHEN iskeluar = ? THEN jumlah ELSE 0 END) AS total_masuk', ['N'])
            ->selectRaw('SUM(CASE WHEN iskeluar = ? THEN jumlah ELSE 0 END) AS total_keluar', ['Y'])
            ->selectRaw('SUM(CASE WHEN kategori_id = 3 THEN jumlah ELSE 0 END) AS tambah_piutang')
            ->selectRaw('SUM(CASE WHEN kategori_id = 4 THEN jumlah ELSE 0 END) AS bayar_piutang')
            ->selectRaw('SUM(CASE WHEN kategori_id = 5 THEN jumlah ELSE 0 END) AS tambah_pinjaman')
            ->selectRaw('SUM(CASE WHEN kategori_id = 6 THEN jumlah ELSE 0 END) AS bayar_pinjaman')
            ->selectRaw('SUM(CASE WHEN kategori_id NOT IN (3, 4, 5, 6) AND iskeluar = ? THEN jumlah ELSE 0 END) AS transaksi_masuk', ['N'])
            ->selectRaw('SUM(CASE WHEN kategori_id NOT IN (3, 4, 5, 6) AND iskeluar = ? THEN jumlah ELSE 0 END) AS transaksi_keluar', ['Y'])
            ->where('parent_id', $parent_id)
            ->whereRaw('YEAR(tanggal) = ?', [$tahun])
            ->whereRaw('MONTH(tanggal) = ?', [$bulan])
            ->first();
        return $model;
    }

}
