<?php

namespace App\Http\Controllers;

use App\Repositories\KategoriRepository;
use App\Repositories\RekeningRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KategoriController extends Controller
{
    use ApiResponser;

    protected $user, $parentId, $repo;

    public function __construct(KategoriRepository $repo) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
        }
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Kategori tidak ditemukan');
        }
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $datas = $this->repo->findAll([
            'parent_id' => $this->parentId,
            'jenis' => $req->query('jenis'),
            'rekening_id' => $req->query('rekening_id'),
            'tahun' => $req->query('tahun'),
            'bulan' => $req->query('bulan'),
        ]);
        return $this->successResponse($datas);
    }

    public function create(Request $req, RekeningRepository $rekeningRepo) {
        $this->validate($req, [
            'jenis' => 'required',
            'nama' => 'required',
            'ikon' => 'required',
            'rekening_id' => 'required',
        ]);
        $inputs = $req->only(['jenis', 'nama', 'ikon', 'rekening_id']);
        $inputs['parent_id'] = $this->parentId;
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Kategori berhasil dibuat');
    }

    public function update(Request $req, RekeningRepository $rekeningRepo, $id) {
        $this->validate($req, [
            'nama' => 'required',
            'ikon' => 'required',
            'rekening_id' => 'required',
        ]);
        $inputs = $req->only(['nama', 'ikon', 'rekening_id']);
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, "Kategori berhasil diubah");
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Kategori tidak ditemukan');
        }
        return $this->successResponse($data, "Kategori berhasil dihapus");
    }

}
