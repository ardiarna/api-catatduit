<?php

namespace App\Http\Controllers;

use App\Repositories\CeklistRepository;
use App\Repositories\CeklistDetilRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CeklistController extends Controller
{
    use ApiResponser;

    protected $user, $parentId, $repo, $repoDetil;

    public function __construct(CeklistRepository $repo, CeklistDetilRepository $repoDetil) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
        }
        $this->repo = $repo;
        $this->repoDetil = $repoDetil;
    }

    public function findById($id) {
        $data = $this->cekOtorisasiData($id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $datas = $this->repo->findAll([
            'parent_id' => $this->parentId,
            'nama' => $req->query('nama'),
        ]);
        return $this->successResponse($datas);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama']);
        $inputs['parent_id'] = $this->parentId;
        $ceklist = $this->repo->create($inputs);
        return $this->createdResponse($ceklist, 'Ceklist berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->cekOtorisasiData($id);
        $this->validate($req, [
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama']);
        $ceklist = $this->repo->update($id, $inputs);
        return $this->successResponse($ceklist, 'Ceklist berhasil diubah');
    }

    public function delete($id) {
        $this->cekOtorisasiData($id);
        $detils = $this->repoDetil->deletesByCeklistId($id);
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Ceklist tidak ditemukan');
        }
        return $this->successResponse([$data, $detils], 'Ceklist berhasil dihapus');
    }

    public function findDetilById($id) {
        $data = $this->repoDetil->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Detil ceklist tidak ditemukan');
        }
        $this->cekOtorisasiData($data->ceklist_id);
        return $this->successResponse($data);
    }

    public function createDetil(Request $req, $id) {
        $this->cekOtorisasiData($id);
        $this->validate($req, [
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama']);
        $inputs['ceklist_id'] = $id;
        $inputs['isceklist'] = 'N';
        $inputs['isaktif'] = 'Y';
        $ceklistDetil = $this->repoDetil->create($inputs);
        return $this->createdResponse($ceklistDetil, 'Detil ceklist berhasil disimpan');
    }

    public function updateDetil(Request $req, $id) {
        $this->validate($req, [
            'nama' => 'required',
        ]);
        $nama = $req->input('nama');
        $ceklistDetilBefore = $this->repoDetil->findById($id);
        if($ceklistDetilBefore == null) {
            return $this->failRespNotFound('Detil ceklist tidak ditemukan');
        }
        $this->cekOtorisasiData($ceklistDetilBefore->ceklist_id);
        $ceklistDetil = $this->repoDetil->updateNama($id, $nama);
        return $this->createdResponse($ceklistDetil, 'Detil ceklist berhasil disimpan');
    }

    public function updateIsceklist(Request $req, $id) {
        $this->validate($req, [
            'isceklist' => 'required',
        ]);
        $isceklist = $req->input('isceklist');
        $ceklistDetilBefore = $this->repoDetil->findById($id);
        if($ceklistDetilBefore == null) {
            return $this->failRespNotFound('Detil ceklist tidak ditemukan');
        }
        $this->cekOtorisasiData($ceklistDetilBefore->ceklist_id);
        $ceklistDetil = $this->repoDetil->updateIsceklist($id, $isceklist);
        return $this->createdResponse($ceklistDetil, 'Detil ceklist berhasil disimpan');
    }

    public function updateIsaktif(Request $req, $id) {
        $this->validate($req, [
            'isaktif' => 'required',
        ]);
        $isaktif = $req->input('isaktif');
        $ceklistDetilBefore = $this->repoDetil->findById($id);
        if($ceklistDetilBefore == null) {
            return $this->failRespNotFound('Detil ceklist tidak ditemukan');
        }
        $this->cekOtorisasiData($ceklistDetilBefore->ceklist_id);
        $ceklistDetil = $this->repoDetil->updateIsaktif($id, $isaktif);
        return $this->createdResponse($ceklistDetil, 'Detil ceklist berhasil disimpan');
    }

    public function deleteDetil($id) {
        $ceklistDetilBefore = $this->repoDetil->findById($id);
        if($ceklistDetilBefore == null) {
            return $this->failRespNotFound('Detil ceklist tidak ditemukan');
        }
        $this->cekOtorisasiData($ceklistDetilBefore->ceklist_id);
        $data = $this->repoDetil->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Detil ceklist tidak ditemukan');
        }
        return $this->createdResponse($data, 'Detil ceklist berhasil dihapus');
    }

    public function cekOtorisasiData($id) {
        $cek = $this->repo->findById($id);
        if($cek == null) {
            throw new HttpException(404, 'Ceklist tidak ditemukan');
        }
        if($cek->parent_id != $this->parentId) {
            throw new HttpException(403, 'Tidak berwenang untuk melakukan tindakan ini');
        }
        return $cek;
    }

}
