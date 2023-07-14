<?php

namespace App\Http\Controllers;

use App\Repositories\RekeningRepository;
use App\Repositories\TransaksiRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RekeningController extends Controller
{
    use ApiResponser;

    protected $user, $parentId, $repo, $transaksiRepo;

    public function __construct(RekeningRepository $repo, TransaksiRepository $transaksiRepo) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
        }
        $this->repo = $repo;
        $this->transaksiRepo = $transaksiRepo;
    }

    public function findById($id) {
        $data = $this->cekOtorisasiData($id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $datas = $this->repo->findAll([
            'parent_id' => $this->parentId,
            'jenis' => $req->query('jenis'),
            'bank_id' => $req->query('bank_id'),
        ]);
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
            $inputs['bank_id'] = $req->input('bank_id');
            $inputs['saldo_endap'] = $req->input('saldo_endap');
        }
        $inputs['keterangan'] = $req->input('keterangan');
        $inputs['parent_id'] = $this->parentId;
        $rekening = $this->repo->create($inputs);
        if($rekening != null) {
            $this->insertTransaksiTable('Saldo awal', 'N', $rekening->saldo, '1', $rekening->id, $rekening->parent_id);
        }
        return $this->createdResponse($rekening, 'Rekening berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->cekOtorisasiData($id);
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
        $inputs['bank_id'] = $req->input('bank_id') == '' ? null : $req->input('bank_id');
        $inputs['saldo_endap'] = $req->input('saldo_endap') == '' ? null : $req->input('saldo_endap');
        $inputs['keterangan'] = $req->input('keterangan');
        $data = $this->repo->update($id, $inputs);
        return $this->successResponse($data, "Rekening berhasil diubah");
    }

    public function adjust(Request $req, $id) {
        $this->validate($req, [
            'saldo' => 'required',
        ]);
        $saldo = intval($req->input('saldo'));
        $lama = $this->cekOtorisasiData($id);
        $rekening = $this->repo->editSaldo($id, $saldo);
        if($rekening != null) {
            $jumlah = intval($lama->saldo) - $saldo;
            $iskeluar = $jumlah >= 1 ? "Y" : "N";
            $this->insertTransaksiTable('Penyesuaian saldo', $iskeluar, abs($jumlah), '2', $rekening->id, $rekening->parent_id);
        }
        return $this->successResponse($rekening, "Saldo berhasil diubah");
    }

    public function delete($id) {
        $this->cekOtorisasiData($id);
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        return $this->successResponse($data, "Rekening berhasil dihapus");
    }

    protected function insertTransaksiTable($nama, $iskeluar, $jumlah, $kategori_id,  $rekening_id, $parent_id) {
        $this->transaksiRepo->create([
            'nama' => $nama,
            'tanggal' => date('Y-m-d H:i:s'),
            'iskeluar' => $iskeluar,
            'jumlah' => $jumlah,
            'kategori_id' => $kategori_id,
            'rekening_id' => $rekening_id,
            'parent_id' => $parent_id
        ]);
    }

    public function cekOtorisasiData($id) {
        $cek = $this->repo->findById($id);
        if($cek == null) {
            throw new HttpException(404, 'Rekening tidak ditemukan');
        }
        if($cek->parent_id != $this->parentId) {
            throw new HttpException(403, 'Tidak berwenang untuk melakukan tindakan ini');
        }
        return $cek;
    }

    public function getTotalSaldo() {
        $data = $this->repo->getTotalSaldo($this->parentId);
        return $this->successResponse(['saldo' => $data]);
    }

}
