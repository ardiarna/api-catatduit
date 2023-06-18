<?php

namespace App\Http\Controllers;

use App\Repositories\AdjustRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdjustController extends Controller
{
    use ApiResponser;

    protected $user, $parentId, $repo;

    public function __construct(AdjustRepository $repo) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->parentId = $this->user->parent_id != '0' ? $this->user->parent_id : $this->user->id;
        }
        $this->repo = $repo;
    }

    public function findById($id) {
        $data = $this->repo->findById($id);
        return $this->successResponse($data);
    }

    public function findAll(Request $req) {
        $this->validate($req, [
            'tgl_awal' => 'required',
            'tgl_akhir' => 'required',
        ]);
        $inputs = [
            'parent_id' => $this->parentId,
            'tgl_awal' => $req->query('tgl_awal').' 00:00:00',
            'tgl_akhir' => $req->query('tgl_akhir').' 23:59:59',
            'iskeluar' => $req->query('iskeluar'),
            'rekening_id' => $req->query('rekening_id')
        ];
        $data = $this->repo->findAll($inputs);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'nama' => 'required',
            'tanggal' => 'required',
            'iskeluar' => 'required',
            'jumlah' => 'required',
            'rekening_id' => 'required',
        ]);
        $inputs = $req->only(['nama', 'tanggal', 'iskeluar', 'jumlah', 'rekening_id']);
        $inputs['parent_id'] = $this->parentId;
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Adjust berhasil dibuat');
    }

}
