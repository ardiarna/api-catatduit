<?php

namespace App\Repositories;

interface TabunganRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function editJumlah($id, $jumlah);
    public function editAmbil($id, $ambil);
    public function delete($id);
}
