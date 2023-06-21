<?php

namespace App\Repositories\Elo;

use App\Repositories\KategoriRepository;
use App\Models\Kategori;

class KategoriImplement implements KategoriRepository {

    protected $model;

    function __construct(Kategori $model) {
        $this->model = $model;
    }

    public function findById($id) {
        $model = $this->model->find($id);
        if($model != null) {
            $model->rekening;
        }
        return $model;
    }

    public function findAll(array $inputs) {
        $parent_id = $inputs['parent_id'];
        $jenis = $inputs['jenis'];
        $rekening_id = $inputs['rekening_id'];
        $tahun = $inputs['tahun'];
        $bulan = $inputs['bulan'];

        $models = $this->model->where('parent_id', $parent_id);
        if($jenis) {
            $models = $models->where('jenis', $jenis);
        }
        if($rekening_id) {
            $models = $models->where('rekening_id', $rekening_id);
        }
        $models = $models->orderBy('nama')->get();
        foreach ($models as $model) {
            $model->rekening;
        }
        if($tahun && $bulan) {
            foreach ($models as $model) {
                $model->anggaran = $this->anggaranPeriode($model->id, $tahun, $bulan);
                $model->total_transaksi = $this->transaksiPeriode($model->id, $tahun, $bulan);
            }
        }
        return $models;
    }

    public function create(array $inputs) {
        $model = $this->model->create($inputs);
        $model->rekening;
        return $model;
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->nama = $inputs['nama'];
        $model->ikon = $inputs['ikon'];
        $model->rekening_id = $inputs['rekening_id'];
        $model->save();
        $model->rekening;
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

    public function anggaranPeriode($id, $tahun, $bulan) {
        return $this->model->find($id)->anggarans()
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();
    }

    public function transaksiPeriode($id, $tahun, $bulan) {
        return $this->model->find($id)->transaksis()
            ->whereRaw('YEAR(tanggal) = ?', [$tahun])
            ->whereRaw('MONTH(tanggal) = ?', [$bulan])
            ->sum('jumlah');
    }

}
