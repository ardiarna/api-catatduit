<?php

namespace App\Http\Controllers;

use App\Repositories\KategoriRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            throw new HttpException(404, "Kategori tidak ditemukan");
        }
        $data->rekening;
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $tahun = $req->query('tahun');
        $bulan = $req->query('bulan');

        $datas = $this->repo->findAll([
            'parent_id' => $this->parentId,
            'jenis' => $req->query('jenis'),
            'rekening_id' => $req->query('rekening_id'),
        ]);
        foreach ($datas as $data) {
            $data->rekening;
        }
        if($tahun && $bulan) {
            foreach ($datas as $data) {
                $data->anggaran = $this->repo->anggaranPeriode($data->id, $tahun, $bulan);
            }
        }
        return $this->successResponse($datas);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'jenis' => 'required',
            'nama' => 'required',
            'ikon' => 'required',
            'rekening_id' => 'required',
        ]);
        $inputs = $req->only(['jenis', 'nama', 'ikon', 'rekening_id']);
        $inputs['parent_id'] = $this->parentId;
        $data = $this->repo->create($inputs);
        $data->rekening;
        return $this->createdResponse($data, 'Kategori berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'nama' => 'required',
            'ikon' => 'required',
            'rekening_id' => 'required',
        ]);
        $inputs = $req->only(['nama', 'ikon', 'rekening_id']);
        $data = $this->repo->update($id, $inputs);
        $data->rekening;
        return $this->successResponse($data, "Kategori berhasil diubah");
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            throw new HttpException(404, "Kategori tidak ditemukan");
        }
        return $this->successResponse($data, "Kategori berhasil dihapus");
    }

}
