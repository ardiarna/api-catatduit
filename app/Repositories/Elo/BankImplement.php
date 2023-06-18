<?php

namespace App\Repositories\Elo;

use App\Repositories\BankRepository;
use App\Models\Bank;

class BankImplement implements BankRepository {

    protected $model;

    function __construct(Bank $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findAll($inputs = []) {
        return $this->model->all();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->model->findOrFail($id);
        $model->nama = $inputs['nama'];
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }

}
