<?php

namespace App\Repositories;

interface AdjustRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
}
