<?php

namespace App\Repositories;

interface PiutangRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function editJumlah($id, $jumlah);
    public function editBayar($id, $bayar);
    public function delete($id);
}
