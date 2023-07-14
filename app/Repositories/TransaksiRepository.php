<?php

namespace App\Repositories;

interface TransaksiRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
    public function summaryPeriode($parent_id, $tahun, $bulan);
}
