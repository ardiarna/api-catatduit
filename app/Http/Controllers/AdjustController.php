<?php

namespace App\Http\Controllers;

use App\Models\Adjust;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdjustController extends Controller
{
    use ApiResponser;

    protected $user, $parentId;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
    }

    public function views(Request $req) {
        $this->validate($req, [
            'tgl_awal' => 'required',
            'tgl_akhir' => 'required',
        ]);
        $tgl_awal = $req->query('tgl_awal').' 00:00:00';
        $tgl_akhir = $req->query('tgl_akhir').' 23:59:59';
        $iskeluar = $req->query('iskeluar');
        $rekening_id = $req->query('rekening_id');
        $data = Adjust::where('parent_id', $this->parentId)
            ->where('tanggal', '>=', $tgl_awal)
            ->where('tanggal', '<=', $tgl_akhir);
        if($iskeluar) {
            $data = $data->where('iskeluar', $iskeluar);
        }
        if($rekening_id) {
            $data = $data->where('rekening_id', $rekening_id);
        }
        $data = $data->get();
        return $this->successResponse($data);
    }

    public function view($id) {
        $data = Adjust::findOrFail($id);
        return $this->successResponse($data);
    }

    public function add(Request $req) {
        $this->validate($req, [
            'nama' => 'required',
            'tanggal' => 'required',
            'iskeluar' => 'required',
            'jumlah' => 'required',
            'rekening_id' => 'required',
        ]);
        $inputs = $req->all();
        $inputs['parent_id'] = $this->parentId;
        $data = Adjust::create($inputs);
        return $this->createdResponse($data, 'Adjust berhasil dibuat');
    }

}
