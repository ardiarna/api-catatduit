<?php

namespace App\Repositories;

interface TabunganDetilRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function updateTransaksiId($id, $transaksi_id);
    public function delete($id);
    public function deletesByTabunganId($tabungan_id);
}
