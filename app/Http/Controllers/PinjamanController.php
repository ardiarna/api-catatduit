<?php

namespace App\Http\Controllers;

use App\Repositories\RekeningRepository;
use App\Repositories\TransaksiRepository;
use App\Repositories\PinjamanRepository;
use App\Repositories\PinjamanDetilRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PinjamanController extends Controller
{
    use ApiResponser;

    protected $user, $parentId, $repo, $repoDetil;

    public function __construct(PinjamanRepository $repo, PinjamanDetilRepository $repoDetil) {
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
            'keterangan' => $req->query('keterangan'),
            'direction' => $req->query('direction', 'asc'),
        ]);
        return $this->successResponse($datas);
    }

    public function create(Request $req, RekeningRepository $rekeningRepo, TransaksiRepository $transaksiRepo) {
        $this->validate($req, [
            'nama' => 'required',
            'tanggal' => 'required',
            'tempo' => 'required',
            'jumlah' => 'required',
            'rekening_id' => 'required',
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'tempo', 'jumlah', 'rekening_id']);
        $inputs['keterangan'] = $req->input('keterangan');
        $inputs['bayar'] = '0';
        $inputs['parent_id'] = $this->parentId;
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $jumlah = intval($inputs['jumlah']);
        $sisa_saldo = $rekening->saldo + $jumlah;
        if($sisa_saldo < 0 ) {
            return $this->failRespUnProcess("Saldo $rekening->nama tidak cukup [sisa saldo : Rp. ".number_format($rekening->saldo)."]");
        }
        $pinjaman = $this->repo->create($inputs);
        $pinjamanDetil = $this->repoDetil->create([
            'nama' => 'Pinjaman Awal',
            'tanggal' => $inputs['tanggal'],
            'isbayar' => 'N',
            'jumlah' => $inputs['jumlah'],
            'pinjaman_id' => $pinjaman->id,
            'rekening_id' => $rekening->id
        ]);
        $transaksi = $transaksiRepo->create([
            'nama' => "$pinjaman->nama - $pinjamanDetil->nama",
            'tanggal' => $pinjamanDetil->tanggal,
            'iskeluar' => 'N',
            'jumlah' => $pinjamanDetil->jumlah,
            'kategori_id' => '5',
            'rekening_id' => $pinjamanDetil->rekening_id,
            'parent_id' => $pinjaman->parent_id
        ]);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        $this->repoDetil->updateTransaksiId($pinjamanDetil->id, $transaksi->id);
        $pinjaman->refresh();
        return $this->createdResponse($pinjaman, 'Pinjaman berhasil dibuat');
    }

    public function update(Request $req, $id) {
        $this->cekOtorisasiData($id);
        $this->validate($req, [
            'nama' => 'required',
            'tanggal' => 'required',
            'tempo' => 'required'
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'tempo']);
        $inputs['keterangan'] = $req->input('keterangan');
        $pinjaman = $this->repo->update($id, $inputs);
        return $this->successResponse($pinjaman, 'Pinjaman berhasil diubah');
    }

    public function delete($id) {
        $pinjamanBefore = $this->cekOtorisasiData($id);
        $sisa_pinjaman = $pinjamanBefore->jumlah - $pinjamanBefore->bayar;
        if($sisa_pinjaman > 0) {
            return $this->failRespUnProcess("Pinjaman $pinjamanBefore->nama tidak bisa dihapus, masih terdapat pinjaman sebesar Rp. ".number_format($sisa_pinjaman));
        }
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Pinjaman tidak ditemukan');
        }
        $detils = $this->repoDetil->deletesByPinjamanId($id);
        return $this->successResponse([$data, $detils], 'Pinjaman berhasil dihapus');
    }

    public function findDetilById($id) {
        $data = $this->repoDetil->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Detil pinjaman tidak ditemukan');
        }
        $this->cekOtorisasiData($data->pinjaman_id);
        return $this->successResponse($data);
    }

    public function createDetil(Request $req, RekeningRepository $rekeningRepo, TransaksiRepository $transaksiRepo, $id) {
        $this->validate($req, [
            'tanggal' => 'required',
            'isbayar' => 'required',
            'jumlah' => 'required',
            'rekening_id' => 'required',
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'isbayar', 'jumlah', 'rekening_id']);
        $inputs['pinjaman_id'] = $id;
        $pinjaman = $this->cekOtorisasiData($id);
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $jumlah = intval($inputs['jumlah']);
        $sisa_saldo = 0;
        if($inputs['isbayar'] == 'Y') {
            $sisa_pinjaman = $pinjaman->jumlah - $pinjaman->bayar;
            if($jumlah > $sisa_pinjaman) {
                return $this->failRespUnProcess("Jumlah bayar tidak boleh melebihi sisa pinjaman [Rp. ".number_format($sisa_pinjaman)."]");
            }
            $sisa_saldo = $rekening->saldo - $jumlah;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Saldo $rekening->nama tidak cukup [sisa saldo : Rp. ".number_format($rekening->saldo)."]");
            }
        } else {
            $sisa_saldo = $rekening->saldo + $jumlah;
        }
        $pinjamanDetil = $this->repoDetil->create($inputs);
        if($inputs['isbayar'] == 'Y') {
            $this->repo->editBayar($pinjaman->id, ($pinjaman->bayar + $jumlah));
        } else {
            $this->repo->editJumlah($pinjaman->id, ($pinjaman->jumlah + $jumlah));
        }
        $transaksi = $transaksiRepo->create([
            'nama' => "$pinjaman->nama - $pinjamanDetil->nama",
            'tanggal' => $pinjamanDetil->tanggal,
            'iskeluar' => $pinjamanDetil->isbayar,
            'jumlah' => $pinjamanDetil->jumlah,
            'kategori_id' => $pinjamanDetil->isbayar == 'Y'? '6' : '5',
            'rekening_id' => $pinjamanDetil->rekening_id,
            'parent_id' => $pinjaman->parent_id
        ]);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        $this->repoDetil->updateTransaksiId($pinjamanDetil->id, $transaksi->id);
        $pinjamanDetil->refresh();
        return $this->createdResponse($pinjamanDetil, 'Detil pinjaman berhasil disimpan');
    }

    public function updateDetil(Request $req, RekeningRepository $rekeningRepo, TransaksiRepository $transaksiRepo, $id) {
        $this->validate($req, [
            'tanggal' => 'required',
            'jumlah' => 'required',
            'rekening_id' => 'required',
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'jumlah', 'rekening_id']);
        $pinjamanDetilBefore = $this->repoDetil->findById($id);
        if($pinjamanDetilBefore == null) {
            return $this->failRespNotFound('Detil pinjaman tidak ditemukan');
        }
        $pinjaman = $this->cekOtorisasiData($pinjamanDetilBefore->pinjaman_id);
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $jumlah = intval($inputs['jumlah']);
        $sisa_saldo = 0;
        if($pinjamanDetilBefore->isbayar == 'Y') {
            $sisa_pinjaman = ($pinjaman->jumlah - $pinjaman->bayar) + $pinjamanDetilBefore->jumlah;
            if($jumlah > $sisa_pinjaman) {
                return $this->failRespUnProcess("Jumlah bayar tidak boleh melebihi sisa pinjaman [Rp. ".number_format($sisa_pinjaman)."]");
            }
            $sisa_saldo = $rekening->id == $pinjamanDetilBefore->rekening_id
                ? ($rekening->saldo + $pinjamanDetilBefore->jumlah) - $jumlah
                : $rekening->saldo - $jumlah;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Tidak bisa diubah, saldo $rekening->nama akan menjadi minus. Jumlah yang bisa diinput maksimal Rp. ".number_format(($sisa_saldo+$jumlah)));
            }
            if($rekening->id != $pinjamanDetilBefore->rekening_id) {
                $rekeningBefore = $rekeningRepo->findById($pinjamanDetilBefore->rekening_id);
                if($rekeningBefore != null) {
                    $sisa_saldoBefore = $rekeningBefore->saldo + $pinjamanDetilBefore->jumlah;
                }
            }
        } else {
            $sisa_saldo = $rekening->id == $pinjamanDetilBefore->rekening_id
                ? ($rekening->saldo - $pinjamanDetilBefore->jumlah) + $jumlah
                : $rekening->saldo + $jumlah;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Tidak bisa diubah, saldo $rekening->nama akan menjadi minus. Jumlah yang bisa diinput minimal Rp. ".number_format(($pinjamanDetilBefore->jumlah-$rekening->saldo)));
            }
            if($rekening->id != $pinjamanDetilBefore->rekening_id) {
                $rekeningBefore = $rekeningRepo->findById($pinjamanDetilBefore->rekening_id);
                if($rekeningBefore != null) {
                    $sisa_saldoBefore = $rekeningBefore->saldo - $pinjamanDetilBefore->jumlah;
                    if(($sisa_saldoBefore) < 0) {
                        return $this->failRespUnProcess("Tidak bisa diubah, saldo $rekeningBefore->nama akan menjadi minus.");
                    }
                }
            }
        }
        $pinjamanDetil = $this->repoDetil->update($id, $inputs);
        if($pinjamanDetilBefore->isbayar == 'Y') {
            $this->repo->editBayar($pinjaman->id, ($pinjaman->bayar - $pinjamanDetilBefore->jumlah) + $jumlah);
        } else {
            $this->repo->editJumlah($pinjaman->id, ($pinjaman->jumlah - $pinjamanDetilBefore->jumlah) + $jumlah);
        }
        $transaksiRepo->update($pinjamanDetil->transaksi_id, [
            'nama' => "$pinjaman->nama - $pinjamanDetil->nama",
            'tanggal' => $pinjamanDetil->tanggal,
            'jumlah' => $pinjamanDetil->jumlah,
            'rekening_id' => $pinjamanDetil->rekening_id
        ]);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        if($rekening->id != $pinjamanDetilBefore->rekening_id) {
            if($rekeningBefore != null) {
                $rekeningRepo->editSaldo($pinjamanDetilBefore->rekening_id, $sisa_saldoBefore);
            }
        }
        $pinjamanDetil->refresh();
        return $this->createdResponse($pinjamanDetil, 'Detil pinjaman berhasil disimpan');
    }

    public function deleteDetil(RekeningRepository $rekeningRepo, TransaksiRepository $transaksiRepo, $id) {
        $pinjamanDetilBefore = $this->repoDetil->findById($id);
        if($pinjamanDetilBefore == null) {
            return $this->failRespNotFound('Detil pinjaman tidak ditemukan');
        }
        $pinjaman = $this->cekOtorisasiData($pinjamanDetilBefore->pinjaman_id);
        if($pinjamanDetilBefore->isbayar == 'N') {
            if(($pinjaman->jumlah - $pinjamanDetilBefore->jumlah) < $pinjaman->bayar) {
                return $this->failRespUnProcess("Tidak bisa dihapus, jumlah pinjaman akan lebih kecil dari jumlah bayar.");
            }
        }
        $rekening = $pinjamanDetilBefore->rekening;
        if($rekening != null) {
            if($pinjamanDetilBefore->isbayar == 'Y') {
                $sisa_saldo = $rekening->saldo + $pinjamanDetilBefore->jumlah;
            } else {
                $sisa_saldo = $rekening->saldo - $pinjamanDetilBefore->jumlah;
                if($sisa_saldo < 0 ) {
                    return $this->failRespUnProcess("Tidak bisa dihapus, saldo $rekening->nama akan menjadi minus.");
                }
            }
        }
        $data = $this->repoDetil->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Detil pinjaman tidak ditemukan');
        }
        if($pinjamanDetilBefore->isbayar == 'Y') {
            $this->repo->editBayar($pinjaman->id, ($pinjaman->bayar - $pinjamanDetilBefore->jumlah));
        } else {
            $this->repo->editJumlah($pinjaman->id, ($pinjaman->jumlah - $pinjamanDetilBefore->jumlah));
        }
        $transaksiRepo->delete($pinjamanDetilBefore->transaksi_id);
        if($rekening != null) {
            $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        }
        return $this->createdResponse($data, 'Detil pinjaman berhasil dihapus');
    }

    public function cekOtorisasiData($id) {
        $cek = $this->repo->findById($id);
        if($cek == null) {
            throw new HttpException(404, 'Pinjaman tidak ditemukan');
        }
        if($cek->parent_id != $this->parentId) {
            throw new HttpException(403, 'Tidak berwenang untuk melakukan tindakan ini');
        }
        return $cek;
    }

}
