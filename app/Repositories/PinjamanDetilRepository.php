<?php

namespace App\Repositories;

interface PinjamanDetilRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
    public function deletesByPinjamanId($pinjaman_id);
}