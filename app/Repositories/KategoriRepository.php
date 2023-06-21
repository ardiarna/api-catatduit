<?php

namespace App\Repositories;

interface KategoriRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
    public function anggaranPeriode($id, $tahun, $bulan);
    public function transaksiPeriode($id, $tahun, $bulan);
}
