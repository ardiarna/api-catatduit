<?php

namespace App\Repositories;

interface AnggaranRepository {
    public function findById($id);
    public function findByPeriode($kategori_id, $tahun, $bulan);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function update($id, $jumlah);
    public function delete($id);
}
