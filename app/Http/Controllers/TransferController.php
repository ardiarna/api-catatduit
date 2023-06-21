<?php

namespace App\Http\Controllers;

use App\Repositories\RekeningRepository;
use App\Repositories\TransferRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    use ApiResponser;

    protected $user, $parentId, $repo;

    public function __construct(TransferRepository $repo) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
        }
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Transfer tidak ditemukan');
        }
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $this->validate($req, [
            'tgl_awal' => 'required',
            'tgl_akhir' => 'required',
        ]);
        $datas = $this->repo->findAll([
            'parent_id' => $this->parentId,
            'tgl_awal' => $req->query('tgl_awal').' 00:00:00',
            'tgl_akhir' => $req->query('tgl_akhir').' 23:59:59',
            'rekasal_id' => $req->query('rekasal_id'),
            'rektuju_id' => $req->query('rektuju_id'),
            'nama' => $req->query('nama'),
            'direction' => $req->query('direction', 'desc'),
        ]);
        return $this->successResponse($datas);
    }

    public function create(Request $req, RekeningRepository $rekeningRepo) {
        $this->validate($req, [
            'tanggal' => 'required',
            'jumlah' => 'required',
            'rekasal_id' => 'required',
            'rektuju_id' => 'required|different:rekasal_id',
        ]);
        $inputs = $req->only(['tanggal', 'jumlah', 'rekasal_id', 'rektuju_id']);
        $inputs['parent_id'] = $this->parentId;
        $inputs['nama'] = $req->input('nama');
        $rekasal = $rekeningRepo->findById($inputs['rekasal_id']);
        if($rekasal == null) {
            return $this->failRespNotFound('Rekening asal tidak ditemukan');
        }
        $rektuju = $rekeningRepo->findById($inputs['rektuju_id']);
        if($rektuju == null) {
            return $this->failRespNotFound('Rekening tujuan tidak ditemukan');
        }
        $jumlah_transfer = intval($inputs['jumlah']);
        $rekasal_sisa = $rekasal->saldo - $jumlah_transfer;
        $rektuju_sisa = $rektuju->saldo + $jumlah_transfer;
        if($rekasal_sisa < 0 ) {
            return $this->failRespUnProcess("Saldo $rekasal->nama tidak cukup [sisa saldo : Rp. ".number_format($rekasal->saldo)."]");
        }
        $transfer = $this->repo->create($inputs);
        $rekeningRepo->editSaldo($rekasal->id, $rekasal_sisa);
        $rekeningRepo->editSaldo($rektuju->id, $rektuju_sisa);
        $transfer->refresh();
        return $this->createdResponse($transfer, 'Transfer berhasil dibuat');
    }

    public function update(Request $req, RekeningRepository $rekeningRepo, $id) {
        $this->validate($req, [
            'tanggal' => 'required',
            'jumlah' => 'required',
            'rekasal_id' => 'required',
            'rektuju_id' => 'required|different:rekasal_id',
        ]);
        $inputs = $req->only(['tanggal', 'jumlah', 'rekasal_id', 'rektuju_id']);
        $inputs['nama'] = $req->input('nama');
        $transferBefore = $this->repo->findById($id);
        if($transferBefore == null) {
            return $this->failRespNotFound('Transfer tidak ditemukan');
        }
        $rekasal = $rekeningRepo->findById($inputs['rekasal_id']);
        if($rekasal == null) {
            return $this->failRespNotFound('Rekening asal tidak ditemukan');
        }
        $rektuju = $rekeningRepo->findById($inputs['rektuju_id']);
        if($rektuju == null) {
            return $this->failRespNotFound('Rekening tujuan tidak ditemukan');
        }
        $jumlah_transfer = intval($inputs['jumlah']);
        $rekasal_sisa = $rekasal->id == $transferBefore->rekasal_id
            ? ($rekasal->saldo + $transferBefore->jumlah) - $jumlah_transfer
            : $rekasal->saldo - $jumlah_transfer;
        $rektuju_sisa = $rektuju->id == $transferBefore->rektuju_id
            ? ($rektuju->saldo - $transferBefore->jumlah) + $jumlah_transfer
            : $rektuju->saldo + $jumlah_transfer;
        if($rekasal_sisa < 0 ) {
            return $this->failRespUnProcess("Saldo $rekasal->nama tidak cukup [sisa saldo : Rp. ".number_format($rekasal->saldo)."] Jumlah transfer yang bisa diinput maksimal Rp. ".number_format(($rekasal_sisa+$jumlah_transfer)));
        }
        if($rektuju_sisa < 0 ) {
            return $this->failRespUnProcess("Tidak bisa diubah, saldo $rektuju->nama akan menjadi minus");
        }
        if($rektuju->id != $transferBefore->rektuju_id) {
            $rektujuBefore = $rekeningRepo->findById($transferBefore->rektuju_id);
            if($rektujuBefore != null) {
                $rektujuBefore_sisa = $rektujuBefore->saldo - $transferBefore->jumlah;
                if(($rektujuBefore_sisa) < 0) {
                    return $this->failRespUnProcess("Tidak bisa diubah, saldo $rektujuBefore->nama akan menjadi minus");
                }
            }
        }
        $transfer = $this->repo->update($id, $inputs);
        $rekeningRepo->editSaldo($rekasal->id, $rekasal_sisa);
        $rekeningRepo->editSaldo($rektuju->id, $rektuju_sisa);

        if($rekasal->id != $transferBefore->rekasal_id) {
            $rekasalBefore = $rekeningRepo->findById($transferBefore->rekasal_id);
            if($rekasalBefore != null) {
                $rekasalBefore_sisa = $rekasalBefore->saldo + $transferBefore->jumlah;
                $rekeningRepo->editSaldo($rekasalBefore->id, $rekasalBefore_sisa);
            }
        }
        if($rektuju->id != $transferBefore->rektuju_id) {
            if($rektujuBefore != null) {
                $rekeningRepo->editSaldo($rektujuBefore->id, $rektujuBefore_sisa);
            }
        }
        $transfer->refresh();
        return $this->successResponse($transfer, 'Transfer berhasil diubah');
    }

    public function delete(RekeningRepository $rekeningRepo, $id) {
        $transferBefore = $this->repo->findById($id);
        if($transferBefore == null) {
            return $this->failRespNotFound('Transfer tidak ditemukan');
        }
        $rekasal = $rekeningRepo->findById($transferBefore->rekasal_id);
        if($rekasal != null) {
            $rekasal_sisa = $rekasal->saldo + $transferBefore->jumlah;
        }
        $rektuju = $rekeningRepo->findById($transferBefore->rektuju_id);
        if($rektuju != null) {
            $rektuju_sisa = $rektuju->saldo - $transferBefore->jumlah;
            if($rektuju_sisa < 0 ) {
                return $this->failRespUnProcess("Tidak bisa dihapus, saldo $rektuju->nama akan menjadi minus");
            }
        }
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Transfer tidak ditemukan');
        }
        if($rekasal != null) {
            $rekeningRepo->editSaldo($rekasal->id, $rekasal_sisa);
        }
        if($rektuju != null) {
            $rekeningRepo->editSaldo($rektuju->id, $rektuju_sisa);
        }
        return $this->successResponse($data, 'Transfer berhasil dihapus');
    }

}
