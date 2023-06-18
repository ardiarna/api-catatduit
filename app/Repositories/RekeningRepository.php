<?php

namespace App\Repositories;

interface RekeningRepository {
    public function findById($id);
    public function findAll(array $inputs);
    public function create(array $inputs);
    public function update($id, array $inputs);
    public function editSaldo($id, $saldo);
    public function delete($id);
}
