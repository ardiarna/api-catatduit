<?php

namespace App\Http\Controllers;

use App\Repositories\RekeningRepository;
use App\Repositories\TransaksiRepository;
use App\Repositories\TabunganRepository;
use App\Repositories\TabunganDetilRepository;
use App\Repositories\UserRepository;
use App\Traits\AFhelper;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TabunganController extends Controller
{
    use ApiResponser, AFhelper;

    protected $user, $parentId, $repo, $repoDetil, $userRepo;

    public function __construct(TabunganRepository $repo, TabunganDetilRepository $repoDetil, UserRepository $userRepo) {
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
        $inputs['ambil'] = '0';
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
        $tabungan = $this->repo->create($inputs);
        $tabunganDetil = $this->repoDetil->create([
            'nama' => 'Tabungan Awal',
            'tanggal' => $inputs['tanggal'],
            'isambil' => 'N',
            'jumlah' => $inputs['jumlah'],
            'tabungan_id' => $tabungan->id,
            'rekening_id' => $rekening->id
        ]);
        $transaksi = $transaksiRepo->create([
            'nama' => "$tabungan->nama - $tabunganDetil->nama",
            'tanggal' => $tabunganDetil->tanggal,
            'iskeluar' => 'Y',
            'jumlah' => $tabunganDetil->jumlah,
            'kategori_id' => '3',
            'rekening_id' => $tabunganDetil->rekening_id,
            'parent_id' => $tabungan->parent_id
        ]);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        $this->repoDetil->updateTransaksiId($tabunganDetil->id, $transaksi->id);
        $tabungan->refresh();
        $this->sendFCM('menambah', $tabunganDetil);
        return $this->createdResponse($tabungan, 'Tabungan berhasil dibuat');
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
        $tabungan = $this->repo->update($id, $inputs);
        return $this->successResponse($tabungan, 'Tabungan berhasil diubah');
    }

    public function delete($id) {
        $tabunganBefore = $this->cekOtorisasiData($id);
        $sisa_tabungan = $tabunganBefore->jumlah - $tabunganBefore->ambil;
        if($sisa_tabungan > 0) {
            return $this->failRespUnProcess("Tabungan $tabunganBefore->nama tidak bisa dihapus, masih terdapat tabungan sebesar Rp. ".number_format($sisa_tabungan));
        }
        $detils = $this->repoDetil->deletesByTabunganId($id);
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Tabungan tidak ditemukan');
        }
        return $this->successResponse([$data, $detils], 'Tabungan berhasil dihapus');
    }

    public function findDetilById($id) {
        $data = $this->repoDetil->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Detil tabungan tidak ditemukan');
        }
        $this->cekOtorisasiData($data->tabungan_id);
        return $this->successResponse($data);
    }

    public function createDetil(Request $req, RekeningRepository $rekeningRepo, TransaksiRepository $transaksiRepo, $id) {
        $this->validate($req, [
            'tanggal' => 'required',
            'isambil' => 'required',
            'jumlah' => 'required',
            'rekening_id' => 'required',
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'isambil', 'jumlah', 'rekening_id']);
        $inputs['tabungan_id'] = $id;
        $tabungan = $this->cekOtorisasiData($id);
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $jumlah = intval($inputs['jumlah']);
        $sisa_saldo = 0;
        if($inputs['isambil'] == 'Y') {
            $sisa_tabungan = $tabungan->jumlah - $tabungan->ambil;
            if($jumlah > $sisa_tabungan) {
                return $this->failRespUnProcess("Jumlah ambil tidak boleh melebihi sisa tabungan [Rp. ".number_format($sisa_tabungan)."]");
            }
            $sisa_saldo = $rekening->saldo + $jumlah;
        } else {
            $sisa_saldo = $rekening->saldo - $jumlah;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Saldo $rekening->nama tidak cukup [sisa saldo : Rp. ".number_format($rekening->saldo)."]");
            }
        }
        $tabunganDetil = $this->repoDetil->create($inputs);
        if($inputs['isambil'] == 'Y') {
            $this->repo->editAmbil($tabungan->id, ($tabungan->ambil + $jumlah));
        } else {
            $this->repo->editJumlah($tabungan->id, ($tabungan->jumlah + $jumlah));
        }
        $transaksi = $transaksiRepo->create([
            'nama' => "$tabungan->nama - $tabunganDetil->nama",
            'tanggal' => $tabunganDetil->tanggal,
            'iskeluar' => $tabunganDetil->isambil == 'Y'? 'N' : 'Y',
            'jumlah' => $tabunganDetil->jumlah,
            'kategori_id' => $tabunganDetil->isambil == 'Y'? '4' : '3',
            'rekening_id' => $tabunganDetil->rekening_id,
            'parent_id' => $tabungan->parent_id
        ]);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        $this->repoDetil->updateTransaksiId($tabunganDetil->id, $transaksi->id);
        $tabunganDetil->refresh();
        $this->sendFCM('menambah', $tabunganDetil);
        return $this->createdResponse($tabunganDetil, 'Detil tabungan berhasil disimpan');
    }

    public function updateDetil(Request $req, RekeningRepository $rekeningRepo, TransaksiRepository $transaksiRepo, $id) {
        $this->validate($req, [
            'tanggal' => 'required',
            'jumlah' => 'required',
            'rekening_id' => 'required',
            'nama' => 'required',
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'jumlah', 'rekening_id']);
        $tabunganDetilBefore = $this->repoDetil->findById($id);
        if($tabunganDetilBefore == null) {
            return $this->failRespNotFound('Detil tabungan tidak ditemukan');
        }
        $tabungan = $this->cekOtorisasiData($tabunganDetilBefore->tabungan_id);
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $jumlah = intval($inputs['jumlah']);
        $sisa_saldo = 0;
        if($tabunganDetilBefore->isambil == 'Y') {
            $sisa_tabungan = ($tabungan->jumlah - $tabungan->ambil) + $tabunganDetilBefore->jumlah;
            if($jumlah > $sisa_tabungan) {
                return $this->failRespUnProcess("Jumlah ambil tidak boleh melebihi sisa tabungan [Rp. ".number_format($sisa_tabungan)."]");
            }
            $sisa_saldo = $rekening->id == $tabunganDetilBefore->rekening_id
                ? ($rekening->saldo - $tabunganDetilBefore->jumlah) + $jumlah
                : $rekening->saldo + $jumlah;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Tidak bisa diubah, saldo $rekening->nama akan menjadi minus. Jumlah yang bisa diinput minimal Rp. ".number_format(($tabunganDetilBefore->jumlah-$rekening->saldo)));
            }
            if($rekening->id != $tabunganDetilBefore->rekening_id) {
                $rekeningBefore = $rekeningRepo->findById($tabunganDetilBefore->rekening_id);
                if($rekeningBefore != null) {
                    $sisa_saldoBefore = $rekeningBefore->saldo - $tabunganDetilBefore->jumlah;
                    if(($sisa_saldoBefore) < 0) {
                        return $this->failRespUnProcess("Tidak bisa diubah, saldo $rekeningBefore->nama akan menjadi minus");
                    }
                }
            }
        } else {
            $sisa_saldo = $rekening->id == $tabunganDetilBefore->rekening_id
                ? ($rekening->saldo + $tabunganDetilBefore->jumlah) - $jumlah
                : $rekening->saldo - $jumlah;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Tidak bisa diubah, saldo $rekening->nama akan menjadi minus. Jumlah yang bisa diinput maksimal Rp. ".number_format(($sisa_saldo+$jumlah)));
            }
            if($rekening->id != $tabunganDetilBefore->rekening_id) {
                $rekeningBefore = $rekeningRepo->findById($tabunganDetilBefore->rekening_id);
                if($rekeningBefore != null) {
                    $sisa_saldoBefore = $rekeningBefore->saldo + $tabunganDetilBefore->jumlah;
                }
            }
        }
        $tabunganDetil = $this->repoDetil->update($id, $inputs);
        if($tabunganDetilBefore->isambil == 'Y') {
            $this->repo->editAmbil($tabungan->id, ($tabungan->ambil - $tabunganDetilBefore->jumlah) + $jumlah);
        } else {
            $this->repo->editJumlah($tabungan->id, ($tabungan->jumlah - $tabunganDetilBefore->jumlah) + $jumlah);
        }
        $transaksiRepo->update($tabunganDetil->transaksi_id, [
            'nama' => "$tabungan->nama - $tabunganDetil->nama",
            'tanggal' => $tabunganDetil->tanggal,
            'jumlah' => $tabunganDetil->jumlah,
            'kategori_id' => $tabunganDetil->isambil == 'Y'? '4' : '3',
            'rekening_id' => $tabunganDetil->rekening_id
        ]);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        if($rekening->id != $tabunganDetilBefore->rekening_id) {
            if($rekeningBefore != null) {
                $rekeningRepo->editSaldo($tabunganDetilBefore->rekening_id, $sisa_saldoBefore);
            }
        }
        $tabunganDetil->refresh();
        $this->sendFCM('mengubah', $tabunganDetil);
        return $this->createdResponse($tabunganDetil, 'Detil tabungan berhasil disimpan');
    }

    public function deleteDetil(RekeningRepository $rekeningRepo, TransaksiRepository $transaksiRepo, $id) {
        $tabunganDetilBefore = $this->repoDetil->findById($id);
        if($tabunganDetilBefore == null) {
            return $this->failRespNotFound('Detil tabungan tidak ditemukan');
        }
        $tabungan = $this->cekOtorisasiData($tabunganDetilBefore->tabungan_id);
        if($tabunganDetilBefore->isambil == 'N') {
            if(($tabungan->jumlah - $tabunganDetilBefore->jumlah) < $tabungan->ambil) {
                return $this->failRespUnProcess("Tidak bisa dihapus, jumlah tabungan akan lebih kecil dari jumlah ambil.");
            }
        }
        $rekening = $tabunganDetilBefore->rekening;
        if($rekening != null) {
            if($tabunganDetilBefore->isambil == 'Y') {
                $sisa_saldo = $rekening->saldo - $tabunganDetilBefore->jumlah;
                if($sisa_saldo < 0 ) {
                    return $this->failRespUnProcess("Tidak bisa dihapus, saldo $rekening->nama akan menjadi minus.");
                }
            } else {
                $sisa_saldo = $rekening->saldo + $tabunganDetilBefore->jumlah;
            }
        }
        $data = $this->repoDetil->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Detil tabungan tidak ditemukan');
        }
        if($tabunganDetilBefore->isambil == 'Y') {
            $this->repo->editAmbil($tabungan->id, ($tabungan->ambil - $tabunganDetilBefore->jumlah));
        } else {
            $this->repo->editJumlah($tabungan->id, ($tabungan->jumlah - $tabunganDetilBefore->jumlah));
        }
        $transaksiRepo->delete($tabunganDetilBefore->transaksi_id);
        if($rekening != null) {
            $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        }
        $this->sendFCM('menghapus', $tabunganDetilBefore);
        return $this->createdResponse($data, 'Detil tabungan berhasil dihapus');
    }

    public function cekOtorisasiData($id) {
        $cek = $this->repo->findById($id);
        if($cek == null) {
            throw new HttpException(404, 'Tabungan tidak ditemukan');
        }
        if($cek->parent_id != $this->parentId) {
            throw new HttpException(403, 'Tidak berwenang untuk melakukan tindakan ini');
        }
        return $cek;
    }

    public function sendFCM(string $title, $model) {
        $token = $this->userRepo->getTokenPushOthers($this->user->id, $this->parentId);
        $this->afSendFCMessaging($token,
            $this->user->nama.' '.$title.' tabungan'.($model->isambil == 'Y' ? ' (ambil)' : ''),
            $model->tabungan->nama.' '.$model->nama.' '.$this->matCurrency($model->jumlah).' -; '.$model->rekening->nama.' '.$this->matDMYtime($model->tanggal),
            'tabungan', $model->tabungan->id
        );
    }

}
