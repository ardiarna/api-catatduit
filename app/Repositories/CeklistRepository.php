<?php

namespace App\Repositories;

interface CeklistRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function delete($id);
}
