<?php

namespace App\Http\Controllers;

use App\Repositories\AdjustRepository;
use App\Repositories\RekeningRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RekeningController extends Controller
{
    use ApiResponser;

    protected $user, $parentId, $repo, $adjustRepo;

    public function __construct(RekeningRepository $repo, AdjustRepository $adjustRepo) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
        }
        $this->repo = $repo;
        $this->adjustRepo = $adjustRepo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            throw new HttpException(404, "Rekening tidak ditemukan");
        }
        $data->bank;
        $data->adjusts;
        return $this->successResponse($data);
    }

    public function findAll() {
        $datas = $this->repo->findAll(['parent_id' => $this->parentId]);
        foreach ($datas as $data) {
            $data->bank;
        }
        return $this->successResponse($datas);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'jenis' => 'required',
            'nama' => 'required',
            'saldo' => 'required',
        ]);
        $inputs = $req->only(['jenis', 'nama', 'saldo']);
        if($inputs['jenis'] == 'C' || $inputs['jenis'] == 'D') {
            $this->validate($req, [
                'bank_id' => 'required',
            ]);
        }
        $inputs['bank_id'] = $req->input('bank_id');
        $inputs['saldo_endap'] = $req->input('saldo_endap');
        $inputs['keterangan'] = $req->input('keterangan');
        $inputs['parent_id'] = $this->parentId;
        $rekening = $this->repo->create($inputs);
        if($rekening != null) {
            $this->insertAdjustTable('Saldo Awal', 'N', $rekening->saldo, $rekening->id, $rekening->parent_id);
        }
        return $this->createdResponse($rekening, 'Rekening berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->validate($req, [
            'jenis' => 'required',
            'nama' => 'required',
        ]);
        $inputs = $req->only(['jenis', 'nama']);
        if($inputs['jenis'] == 'C' || $inputs['jenis'] == 'D') {
            $this->validate($req, [
                'bank_id' => 'required',
            ]);
        }
        $inputs['bank_id'] = $req->input('bank_id');
        $inputs['saldo_endap'] = $req->input('saldo_endap');
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, "Rekening berhasil diubah");
    }

    public function adjust(Request $req, $id) {
        $this->validate($req, [
            'saldo' => 'required',
        ]);
        $saldo = intval($req->input('saldo'));
        $lama = $this->repo->findById($id);
        $rekening = $this->repo->editSaldo($id, $saldo);
        if($rekening != null) {
            $jumlah = intval($lama->saldo) - $saldo;
            $iskeluar = $jumlah >= 1 ? "Y" : "N";
            $this->insertAdjustTable('Penyesuaian', $iskeluar, abs($jumlah), $rekening->id, $rekening->parent_id);
        }
        return $this->successResponse($rekening, "Saldo berhasil diubah");
    }

    public function delete($id) {
        $data = $this->repo->delete($id);
        if($data == 0) {
            throw new HttpException(404, "Rekening tidak ditemukan");
        }
        return $this->successResponse($data, "Rekening berhasil dihapus");
    }

    protected function insertAdjustTable($nama, $iskeluar, $jumlah, $rekening_id, $parent_id) {
        $this->adjustRepo->create([
            'nama' => $nama,
            'tanggal' => date('Y-m-d H:i:s'),
            'iskeluar' => $iskeluar,
            'jumlah' => $jumlah,
            'rekening_id' => $rekening_id,
            'parent_id' => $parent_id
        ]);
    }

}
