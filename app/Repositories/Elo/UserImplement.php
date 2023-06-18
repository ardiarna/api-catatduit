<?php

namespace App\Repositories\Elo;

use App\Repositories\UserRepository;
use App\Models\User;

class UserImplement implements UserRepository {

    protected $model;

    function __construct(User $model) {
        $this->model = $model;
    }

    public function findById($id) {
        return $this->model->find($id);
    }

    public function findByEmail(string $email) {
        return $this->model->where('email', $email)->first();
    }

    public function children($id) {
        return $this->model->where('parent_id', $id)->get();
    }

    public function parent($parent_id) {
        return $this->model->where('id', $parent_id)->first();
    }

    public function create(array $inputs) {
        return $this->model->create($inputs);
    }

    public function update($id, array $inputs) {
        $model = $this->findById($id);
        $model->email = $inputs['email'];
        $model->nama = $inputs['nama'];
        $model->hp_kode = $inputs['hp_kode'];
        $model->hp_nomor = $inputs['hp_nomor'];
        $model->save();
        return $model;
    }

    public function editPassword($id, string $password) {
        $model = $this->findById($id);
        $model->password = $password;
        $model->save();
        return $model;
    }

    public function tokenPush($id, string $token_push) {
        $model = $this->findById($id);
        $model->token_push = $token_push;
        $model->save();
        return $model;
    }

    public function photo($id, string $namafoto) {
        $model = $this->findById($id);
        $model->foto = $namafoto;
        $model->save();
        return $model;
    }

    public function delete($id) {
        return $this->model->destroy($id);
    }
}
