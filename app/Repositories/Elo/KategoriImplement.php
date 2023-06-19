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
        return $this->model->find($id);
    }

    public function findAll(array $inputs) {
        $parent_id = $inputs['parent_id'];
        $jenis = $inputs['jenis'];
        $rekening_id = $inputs['rekening_id'];

        $models = $this->model->where('parent_id', $parent_id);
        if($jenis) {
            $models = $models->where('jenis', $jenis);
        }
        if($rekening_id) {
            $models = $models->where('rekening_id', $rekening_id);
        }
        $models = $models->get();
        return $models;
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->nama = $inputs['nama'];
        $model->ikon = $inputs['ikon'];
        $model->rekening_id = $inputs['rekening_id'];
        $model->save();
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

}
