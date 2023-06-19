<?php

namespace App\Http\Controllers;

use App\Repositories\AnggaranRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AnggaranController extends Controller
{
    use ApiResponser;

    protected $user, $parentId, $repo;

    public function __construct(AnggaranRepository $repo) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
        }
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            throw new HttpException(404, "Anggaran tidak ditemukan");
        }
        $data->kategori;
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $datas = $this->repo->findAll([
            'parent_id' => $this->parentId,
            'tahun' => $req->query('tahun'),
            'bulan' => $req->query('bulan'),
            'kategori_id' => $req->query('kategori_id'),
        ]);
        foreach ($datas as $data) {
            $data->kategori;
        }
        return $this->successResponse($datas);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'tahun' => 'required',
            'bulan' => 'required',
            'kategori_id' => 'required',
            'jumlah' => 'required',
        ]);
        $inputs = $req->only(['tahun', 'bulan', 'kategori_id', 'jumlah']);
        $inputs['parent_id'] = $this->parentId;
        $data = $this->repo->create($inputs);
        $data->kategori;
        return $this->createdResponse($data, 'Anggaran berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'jumlah' => 'required',
        ]);
        $jumlah = $req->input('jumlah');
        $data = $this->repo->update($id, $jumlah);
        $data->kategori;
        return $this->successResponse($data, "Anggaran berhasil diubah");
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            throw new HttpException(404, "Anggaran tidak ditemukan");
        }
        return $this->successResponse($data, "Anggaran berhasil dihapus");
    }

}
