<?php

namespace App\Http\Controllers;

use App\Repositories\RekeningRepository;
use App\Repositories\TransaksiRepository;
use App\Repositories\PiutangRepository;
use App\Repositories\PiutangDetilRepository;
use App\Repositories\UserRepository;
use App\Traits\AFhelper;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PiutangController extends Controller
{
    use ApiResponser, AFhelper;

    protected $user, $parentId, $repo, $repoDetil, $userRepo;

    public function __construct(PiutangRepository $repo, PiutangDetilRepository $repoDetil, UserRepository $userRepo) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
        }
        $this->repo = $repo;
        $this->repoDetil = $repoDetil;
        $this->userRepo = $userRepo;
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
            'direction' => $req->query('direction', 'desc'),
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
        $sisa_saldo = $rekening->saldo - $jumlah;
        if($sisa_saldo < 0 ) {
            return $this->failRespUnProcess("Saldo $rekening->nama tidak cukup [sisa saldo : Rp. ".number_format($rekening->saldo)."]");
        }
        $piutang = $this->repo->create($inputs);
        $piutangDetil = $this->repoDetil->create([
            'nama' => 'Piutang Awal',
            'tanggal' => $inputs['tanggal'],
            'isbayar' => 'N',
            'jumlah' => $inputs['jumlah'],
            'piutang_id' => $piutang->id,
            'rekening_id' => $rekening->id
        ]);
        $transaksi = $transaksiRepo->create([
            'nama' => "$piutang->nama - $piutangDetil->nama",
            'tanggal' => $piutangDetil->tanggal,
            'iskeluar' => 'Y',
            'jumlah' => $piutangDetil->jumlah,
            'kategori_id' => '3',
            'rekening_id' => $piutangDetil->rekening_id,
            'parent_id' => $piutang->parent_id
        ]);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        $this->repoDetil->updateTransaksiId($piutangDetil->id, $transaksi->id);
        $piutang->refresh();
        $this->sendFCM('menambah', $piutangDetil);
        return $this->createdResponse($piutang, 'Piutang berhasil dibuat');
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
        $piutang = $this->repo->update($id, $inputs);
        return $this->successResponse($piutang, 'Piutang berhasil diubah');
    }

    public function delete($id) {
        $piutangBefore = $this->cekOtorisasiData($id);
        $sisa_piutang = $piutangBefore->jumlah - $piutangBefore->bayar;
        if($sisa_piutang > 0) {
            return $this->failRespUnProcess("Piutang $piutangBefore->nama tidak bisa dihapus, masih terdapat piutang sebesar Rp. ".number_format($sisa_piutang));
        }
        $detils = $this->repoDetil->deletesByPiutangId($id);
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Piutang tidak ditemukan');
        }
        return $this->successResponse([$data, $detils], 'Piutang berhasil dihapus');
    }

    public function findDetilById($id) {
        $data = $this->repoDetil->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Detil piutang tidak ditemukan');
        }
        $this->cekOtorisasiData($data->piutang_id);
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
        $inputs['piutang_id'] = $id;
        $piutang = $this->cekOtorisasiData($id);
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $jumlah = intval($inputs['jumlah']);
        $sisa_saldo = 0;
        if($inputs['isbayar'] == 'Y') {
            $sisa_piutang = $piutang->jumlah - $piutang->bayar;
            if($jumlah > $sisa_piutang) {
                return $this->failRespUnProcess("Jumlah bayar tidak boleh melebihi sisa piutang [Rp. ".number_format($sisa_piutang)."]");
            }
            $sisa_saldo = $rekening->saldo + $jumlah;
        } else {
            $sisa_saldo = $rekening->saldo - $jumlah;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Saldo $rekening->nama tidak cukup [sisa saldo : Rp. ".number_format($rekening->saldo)."]");
            }
        }
        $piutangDetil = $this->repoDetil->create($inputs);
        if($inputs['isbayar'] == 'Y') {
            $this->repo->editBayar($piutang->id, ($piutang->bayar + $jumlah));
        } else {
            $this->repo->editJumlah($piutang->id, ($piutang->jumlah + $jumlah));
        }
        $transaksi = $transaksiRepo->create([
            'nama' => "$piutang->nama - $piutangDetil->nama",
            'tanggal' => $piutangDetil->tanggal,
            'iskeluar' => $piutangDetil->isbayar == 'Y'? 'N' : 'Y',
            'jumlah' => $piutangDetil->jumlah,
            'kategori_id' => $piutangDetil->isbayar == 'Y'? '4' : '3',
            'rekening_id' => $piutangDetil->rekening_id,
            'parent_id' => $piutang->parent_id
        ]);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        $this->repoDetil->updateTransaksiId($piutangDetil->id, $transaksi->id);
        $piutangDetil->refresh();
        $this->sendFCM('menambah', $piutangDetil);
        return $this->createdResponse($piutangDetil, 'Detil piutang berhasil disimpan');
    }

    public function updateDetil(Request $req, RekeningRepository $rekeningRepo, TransaksiRepository $transaksiRepo, $id) {
        $this->validate($req, [
            'tanggal' => 'required',
            'jumlah' => 'required',
            'rekening_id' => 'required',
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'jumlah', 'rekening_id']);
        $piutangDetilBefore = $this->repoDetil->findById($id);
        if($piutangDetilBefore == null) {
            return $this->failRespNotFound('Detil piutang tidak ditemukan');
        }
        $piutang = $this->cekOtorisasiData($piutangDetilBefore->piutang_id);
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $jumlah = intval($inputs['jumlah']);
        $sisa_saldo = 0;
        if($piutangDetilBefore->isbayar == 'Y') {
            $sisa_piutang = ($piutang->jumlah - $piutang->bayar) + $piutangDetilBefore->jumlah;
            if($jumlah > $sisa_piutang) {
                return $this->failRespUnProcess("Jumlah bayar tidak boleh melebihi sisa piutang [Rp. ".number_format($sisa_piutang)."]");
            }
            $sisa_saldo = $rekening->id == $piutangDetilBefore->rekening_id
                ? ($rekening->saldo - $piutangDetilBefore->jumlah) + $jumlah
                : $rekening->saldo + $jumlah;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Tidak bisa diubah, saldo $rekening->nama akan menjadi minus. Jumlah yang bisa diinput minimal Rp. ".number_format(($piutangDetilBefore->jumlah-$rekening->saldo)));
            }
            if($rekening->id != $piutangDetilBefore->rekening_id) {
                $rekeningBefore = $rekeningRepo->findById($piutangDetilBefore->rekening_id);
                if($rekeningBefore != null) {
                    $sisa_saldoBefore = $rekeningBefore->saldo - $piutangDetilBefore->jumlah;
                    if(($sisa_saldoBefore) < 0) {
                        return $this->failRespUnProcess("Tidak bisa diubah, saldo $rekeningBefore->nama akan menjadi minus");
                    }
                }
            }
        } else {
            $sisa_saldo = $rekening->id == $piutangDetilBefore->rekening_id
                ? ($rekening->saldo + $piutangDetilBefore->jumlah) - $jumlah
                : $rekening->saldo - $jumlah;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Tidak bisa diubah, saldo $rekening->nama akan menjadi minus. Jumlah yang bisa diinput maksimal Rp. ".number_format(($sisa_saldo+$jumlah)));
            }
            if($rekening->id != $piutangDetilBefore->rekening_id) {
                $rekeningBefore = $rekeningRepo->findById($piutangDetilBefore->rekening_id);
                if($rekeningBefore != null) {
                    $sisa_saldoBefore = $rekeningBefore->saldo + $piutangDetilBefore->jumlah;
                }
            }
        }
        $piutangDetil = $this->repoDetil->update($id, $inputs);
        if($piutangDetilBefore->isbayar == 'Y') {
            $this->repo->editBayar($piutang->id, ($piutang->bayar - $piutangDetilBefore->jumlah) + $jumlah);
        } else {
            $this->repo->editJumlah($piutang->id, ($piutang->jumlah - $piutangDetilBefore->jumlah) + $jumlah);
        }
        $transaksiRepo->update($piutangDetil->transaksi_id, [
            'nama' => "$piutang->nama - $piutangDetil->nama",
            'tanggal' => $piutangDetil->tanggal,
            'jumlah' => $piutangDetil->jumlah,
            'kategori_id' => $piutangDetil->isbayar == 'Y'? '4' : '3',
            'rekening_id' => $piutangDetil->rekening_id
        ]);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        if($rekening->id != $piutangDetilBefore->rekening_id) {
            if($rekeningBefore != null) {
                $rekeningRepo->editSaldo($piutangDetilBefore->rekening_id, $sisa_saldoBefore);
            }
        }
        $piutangDetil->refresh();
        $this->sendFCM('mengubah', $piutangDetil);
        return $this->createdResponse($piutangDetil, 'Detil piutang berhasil disimpan');
    }

    public function deleteDetil(RekeningRepository $rekeningRepo, TransaksiRepository $transaksiRepo, $id) {
        $piutangDetilBefore = $this->repoDetil->findById($id);
        if($piutangDetilBefore == null) {
            return $this->failRespNotFound('Detil piutang tidak ditemukan');
        }
        $piutang = $this->cekOtorisasiData($piutangDetilBefore->piutang_id);
        if($piutangDetilBefore->isbayar == 'N') {
            if(($piutang->jumlah - $piutangDetilBefore->jumlah) < $piutang->bayar) {
                return $this->failRespUnProcess("Tidak bisa dihapus, jumlah piutang akan lebih kecil dari jumlah bayar.");
            }
        }
        $rekening = $piutangDetilBefore->rekening;
        if($rekening != null) {
            if($piutangDetilBefore->isbayar == 'Y') {
                $sisa_saldo = $rekening->saldo - $piutangDetilBefore->jumlah;
                if($sisa_saldo < 0 ) {
                    return $this->failRespUnProcess("Tidak bisa dihapus, saldo $rekening->nama akan menjadi minus.");
                }
            } else {
                $sisa_saldo = $rekening->saldo + $piutangDetilBefore->jumlah;
            }
        }
        $data = $this->repoDetil->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Detil piutang tidak ditemukan');
        }
        if($piutangDetilBefore->isbayar == 'Y') {
            $this->repo->editBayar($piutang->id, ($piutang->bayar - $piutangDetilBefore->jumlah));
        } else {
            $this->repo->editJumlah($piutang->id, ($piutang->jumlah - $piutangDetilBefore->jumlah));
        }
        $transaksiRepo->delete($piutangDetilBefore->transaksi_id);
        if($rekening != null) {
            $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        }
        $this->sendFCM('menghapus', $piutangDetilBefore);
        return $this->createdResponse($data, 'Detil piutang berhasil dihapus');
    }

    public function cekOtorisasiData($id) {
        $cek = $this->repo->findById($id);
        if($cek == null) {
            throw new HttpException(404, 'Piutang tidak ditemukan');
        }
        if($cek->parent_id != $this->parentId) {
            throw new HttpException(403, 'Tidak berwenang untuk melakukan tindakan ini');
        }
        return $cek;
    }

    public function sendFCM(string $title, $model) {
        $token = $this->userRepo->getTokenPushOthers($this->user->id, $this->parentId);
        $this->afSendFCMessaging($token,
            $this->user->nama.' '.$title.' piutang'.($model->isbayar == 'Y' ? ' (bayar)' : ''),
            $model->piutang->nama.' '.$model->nama.' '.$this->matCurrency($model->jumlah).' -; '.$model->rekening->nama.' '.$this->matDMYtime($model->tanggal),
            'piutang', $model->piutang->id
        );
    }

}
