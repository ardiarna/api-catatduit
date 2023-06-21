<?php

namespace App\Http\Controllers;

use App\Repositories\BankRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class BankController extends Controller
{
    use ApiResponser;

    protected $repo;

    public function __construct(BankRepository $repo) {
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        return $this->successResponse($data);
    }

    public function findAll() {
        $data = $this->repo->findAll();
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'nama' => 'required'
        ]);
        $inputs = $req->only(['nama']);
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Bank berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama']);
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, 'Bank berhasil diubah');
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Bank tidak ditemukan');
        }
        return $this->successResponse($data, 'Bank berhasil dihapus');
    }

}
