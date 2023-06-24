<?php

namespace App\Http\Controllers;

use App\Repositories\KategoriRepository;
use App\Repositories\RekeningRepository;
use App\Repositories\TransaksiRepository;
use App\Traits\AFhelper;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransaksiController extends Controller
{
    use ApiResponser, AFhelper;

    protected $user, $parentId, $repo;

    public function __construct(TransaksiRepository $repo) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
        }
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        if($data == null) {
            return $this->failRespNotFound('Transaksi tidak ditemukan');
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
            'iskeluar' => $req->query('iskeluar'),
            'kategori_id' => $req->query('kategori_id'),
            'rekening_id' => $req->query('rekening_id'),
            'nama' => $req->query('nama'),
            'direction' => $req->query('direction', 'desc'),
        ]);
        return $this->successResponse($datas);
    }

    public function create(Request $req, RekeningRepository $rekeningRepo, KategoriRepository $kategoriRepo) {
        $this->validate($req, [
            'nama' => 'required',
            'tanggal' => 'required',
            'iskeluar' => 'required',
            'jumlah' => 'required',
            'kategori_id' => 'required',
            'rekening_id' => 'required',
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'iskeluar', 'jumlah', 'kategori_id', 'rekening_id']);
        $inputs['parent_id'] = $this->parentId;
        $this->afSetYearMonth($inputs['tanggal']);
        $kategori = $kategoriRepo->findById($inputs['kategori_id']);
        if($kategori == null) {
            return $this->failRespNotFound('Kategori tidak ditemukan');
        }
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $jumlah_transaksi = intval($inputs['jumlah']);
        $sisa_saldo = 0;
        if($inputs['iskeluar'] == 'Y') {
            $sisa_saldo = $rekening->saldo - $jumlah_transaksi;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Saldo $rekening->nama tidak cukup [sisa saldo : Rp. ".number_format($rekening->saldo)."]");
            }
            $anggaran = $kategoriRepo->anggaranPeriode($kategori->id, $this->afYear, $this->afMonth);
            if($anggaran == null) {
                return $this->failRespNotFound("Anggaran $kategori->nama untuk periode $this->afMonthLabel $this->afYear belum dibuat");
            }
            if($anggaran->jumlah == 0) {
                return $this->failRespUnProcess("Anggaran $kategori->nama untuk periode $this->afMonthLabel $this->afYear masih 0");
            }
            $total_transaksi_before = $kategoriRepo->transaksiPeriode($kategori->id, $this->afYear, $this->afMonth);
            $total_transaksi = $total_transaksi_before + $jumlah_transaksi;
            if($anggaran->jumlah < $total_transaksi) {
                return $this->failRespUnProcess("Transaksi melebihi anggaran $kategori->nama untuk periode $this->afMonthLabel $this->afYear [anggaran : Rp. ".number_format($anggaran->jumlah)." , total transaksi sebelumnya : Rp. ".number_format($total_transaksi_before)." , transaksi sekarang : Rp. ".number_format($jumlah_transaksi)."]");
            }
        } else {
            $sisa_saldo = $rekening->saldo + $jumlah_transaksi;
        }
        $transaksi = $this->repo->create($inputs);
        $rekeningRepo->editSaldo($transaksi->rekening_id, $sisa_saldo);
        $transaksi->refresh();
        return $this->createdResponse($transaksi, 'Transaksi berhasil dibuat');
    }

    public function update(Request $req, RekeningRepository $rekeningRepo, KategoriRepository $kategoriRepo, $id) {
        $this->validate($req, [
            'nama' => 'required',
            'tanggal' => 'required',
            'jumlah' => 'required',
            'kategori_id' => 'required',
            'rekening_id' => 'required',
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'jumlah', 'kategori_id', 'rekening_id']);
        $transaksiBefore = $this->repo->findById($id);
        if($transaksiBefore == null) {
            return $this->failRespNotFound('Transaksi tidak ditemukan');
        }
        $this->afSetYearMonth($transaksiBefore->tanggal);
        $transaksiBefore->year = $this->afYear;
        $transaksiBefore->month = $this->afMonth;
        $transaksiBefore->monthLabel = $this->afMonthLabel;
        $this->afSetYearMonth($inputs['tanggal']);
        if($transaksiBefore->year != $this->afYear || $transaksiBefore->month != $this->afMonth) {
            return $this->failRespUnProcess("Periode tidak boleh berbeda [periode harus $transaksiBefore->monthLabel $transaksiBefore->year]");
        }
        $kategori = $kategoriRepo->findById($inputs['kategori_id']);
        if($kategori == null) {
            return $this->failRespNotFound('Kategori tidak ditemukan');
        }
        $rekening = $rekeningRepo->findById($inputs['rekening_id']);
        if($rekening == null) {
            return $this->failRespNotFound('Rekening tidak ditemukan');
        }
        $jumlah_transaksi = intval($inputs['jumlah']);
        if($transaksiBefore->iskeluar == 'Y') {
            $sisa_saldo = $rekening->id == $transaksiBefore->rekening_id
                ? ($rekening->saldo + $transaksiBefore->jumlah) - $jumlah_transaksi
                : $rekening->saldo - $jumlah_transaksi;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Saldo $rekening->nama tidak cukup [sisa saldo : Rp. ".number_format($rekening->saldo)."] Jumlah transaksi yang bisa diinput maksimal Rp. ".number_format(($sisa_saldo+$jumlah_transaksi)));
            }
            $anggaran = $kategoriRepo->anggaranPeriode($kategori->id, $this->afYear, $this->afMonth);
            if($anggaran == null) {
                return $this->failRespNotFound("Anggaran $kategori->nama untuk periode $this->afMonthLabel $this->afYear belum dibuat");
            }
            if($anggaran->jumlah == 0) {
                return $this->failRespUnProcess("Anggaran $kategori->nama untuk periode $this->afMonthLabel $this->afYear masih 0");
            }
            $total_transaksi_before = $kategoriRepo->transaksiPeriode($kategori->id, $this->afYear, $this->afMonth);
            $total_transaksi = $kategori->id == $transaksiBefore->kategori_id
                ? ($total_transaksi_before - $transaksiBefore->jumlah) + $jumlah_transaksi
                : $total_transaksi_before + $jumlah_transaksi;
            if($anggaran->jumlah < $total_transaksi) {
                return $this->failRespUnProcess("Transaksi melebihi anggaran $kategori->nama untuk periode $this->afMonthLabel $this->afYear [anggaran : Rp. ".number_format($anggaran->jumlah)." , total transaksi sebelumnya : Rp. ".number_format($total_transaksi-$jumlah_transaksi)."] Jumlah transaksi yang bisa diinput maksimal Rp. ".number_format($anggaran->jumlah-($total_transaksi-$jumlah_transaksi))."]");
            }
            if($rekening->id != $transaksiBefore->rekening_id) {
                $rekeningBefore = $rekeningRepo->findById($transaksiBefore->rekening_id);
                if($rekeningBefore != null) {
                    $sisa_saldoBefore = $rekeningBefore->saldo + $transaksiBefore->jumlah;
                }
            }
        } else {
            $sisa_saldo = $rekening->id == $transaksiBefore->rekening_id
                ? ($rekening->saldo - $transaksiBefore->jumlah) + $jumlah_transaksi
                : $rekening->saldo + $jumlah_transaksi;
            if($sisa_saldo < 0 ) {
                return $this->failRespUnProcess("Tidak bisa diubah saldo $rekening->nama akan menjadi minus. Jumlah yang bisa diinput minimal Rp. ".number_format(($transaksiBefore->jumlah-$rekening->saldo)));
            }
            if($rekening->id != $transaksiBefore->rekening_id) {
                $rekeningBefore = $rekeningRepo->findById($transaksiBefore->rekening_id);
                if($rekeningBefore != null) {
                    $sisa_saldoBefore = $rekeningBefore->saldo - $transaksiBefore->jumlah;
                    if(($sisa_saldoBefore) < 0) {
                        return $this->failRespUnProcess("Tidak bisa diubah saldo $rekeningBefore->nama akan menjadi minus");
                    }
                }
            }
        }
        $transaksi = $this->repo->update($id, $inputs);
        $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        if($rekening->id != $transaksiBefore->rekening_id) {
            if($rekeningBefore != null) {
                $rekeningRepo->editSaldo($transaksiBefore->rekening_id, $sisa_saldoBefore);
            }
        }
        $transaksi->refresh();
        return $this->successResponse($transaksi, 'Transaksi berhasil diubah');
    }

    public function delete(RekeningRepository $rekeningRepo, $id) {
        $transaksiBefore = $this->repo->findById($id);
        if($transaksiBefore == null) {
            return $this->failRespNotFound('Transaksi tidak ditemukan');
        }
        $rekening = $rekeningRepo->findById($transaksiBefore->rekening_id);
        if($rekening != null) {
            if($transaksiBefore->iskeluar == 'Y') {
                $sisa_saldo = $rekening->saldo + $transaksiBefore->jumlah;
            } else {
                $sisa_saldo = $rekening->saldo - $transaksiBefore->jumlah;
                if($sisa_saldo < 0 ) {
                    return $this->failRespUnProcess("$transaksiBefore->nama tidak bisa dihapus, saldo $rekening->nama akan menjadi minus");
                }
            }
        }
        $data = $this->repo->delete($id);
        if($data == 0) {
            return $this->failRespNotFound('Transaksi tidak ditemukan');
        }
        if($rekening != null) {
            $rekeningRepo->editSaldo($rekening->id, $sisa_saldo);
        }
        return $this->successResponse($data, 'Transaksi berhasil dihapus');
    }

}
