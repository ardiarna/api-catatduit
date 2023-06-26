<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    use ApiResponser;

    protected $user, $userId, $repo;

    public function __construct(UserRepository $repo) {
        $this->user = Auth::user();
        if($this->user != null) {
            $this->userId = $this->user->id;
        }
        $this->repo = $repo;
    }

    public function view() {
        $data = $this->user;
        $data->foto = config('image.user').$data->foto;
        if($this->user != null) {
            $data->parent;
            $data->children;
        }
        return $this->successResponse($data);
    }

    public function children() {
        $data = $this->repo->children($this->userId);
        return $this->successResponse($data);
    }

    public function parent() {
        $data = $this->repo->parent($this->user->parent_id);
        return $this->successResponse($data);
    }

    public function create(Request $req) {
        $this->validate($req, [
            'email' => 'required|email',
            'password' => 'required|confirmed',
            'nama' => 'required',
            'hp_kode' => 'required',
            'hp_nomor' => 'required',
        ]);
        $inputs = $req->only(['email', 'password', 'nama', 'hp_kode', 'hp_nomor']);
        $this->cekExistingEmail($inputs['email']);
        $inputs['parent_id'] = $this->user == null ? '0' : $this->userId;
        $inputs['password'] = Hash::make($inputs['password']);
        $data = $this->repo->create($inputs);
        return $this->createdResponse($data, 'Akun berhasil dibuat');
    }

    public function update(Request $req) {
        $this->validate($req, [
            'email' => 'required|email',
            'nama' => 'required',
            'hp_kode' => 'required',
            'hp_nomor' => 'required',
        ]);
        $inputs = $req->only(['email', 'nama', 'hp_kode', 'hp_nomor']);
        if($inputs['email'] != $this->user->email) {
            $this->cekExistingEmail($inputs['email']);
        }
        $data = $this->repo->update($this->userId, $inputs);
        return $this->successResponse($data, "Perubahan akun berhasil disimpan");
    }

    public function editPassword(Request $req) {
        $this->validate($req, [
            'old_password' => 'required',
            'password' => 'required|different:old_password|confirmed',
        ]);
        $inputs = $req->only(['old_password', 'password']);
        if(!Hash::check($inputs['old_password'], $this->user->password)) {
            throw new HttpException(400, "password lama anda tidak sesuai");
        }
        $data = $this->repo->editPassword($this->userId, Hash::make($inputs['password']));
        return $this->successResponse($data, "Password berhasil diubah");
    }

    public function resetPassword(Request $req) {
        $this->validate($req, [
            'id' => 'required',
            'key' => 'required',
            'password' => 'required|confirmed',
        ]);
        $inputs = $req->only(['id', 'key', 'password']);
        $cekuser = $this->repo->findById($inputs['id']);
        if($cekuser == null) {
            throw new HttpException(404, "Akun tidak ditemukan");
        }
        if($inputs['key'] != $cekuser->password) {
            throw new HttpException(400, "autentikasi anda tidak sesuai");
        }
        $data = $this->repo->editPassword($inputs['id'], Hash::make($inputs['password']));
        return $this->successResponse($data, "Password berhasil direset");
    }

    public function tokenPush(Request $req) {
        $this->validate($req, [
            'token_push' => 'required',
        ]);
        $data = $this->repo->tokenPush($this->userId, $req->input('token_push'));
        return $this->successResponse($data, "Token push notification berhasil disimpan");
    }

    public function photo(Request $req) {
        if($req->hasFile('foto')) {
            $foto = $req->file('foto');
            if($foto->isValid()) {
                $namafoto = $this->userId.'_'.$foto->getClientOriginalName();
                $foto->move(Storage::path('images/user'), $namafoto);
                if($this->user->foto != $namafoto) {
                    if(Storage::exists('images/user/'.$this->user->foto)) {
                        Storage::delete('images/user/'.$this->user->foto);
                    }
                }
                $data = $this->repo->photo($this->userId, $namafoto);
                return $this->successResponse($data, "Foto berhasil disimpan");
            } else {
                throw new HttpException(500, "foto gagal diupload");
            }
        } else {
            throw new HttpException(400, "file foto tidak ada");
        }
    }

    public function delete() {
        $data = $this->repo->delete($this->userId);
        if($data == 0) {
            throw new HttpException(404, "Akun tidak ditemukan");
        }
        return $this->successResponse($data, "Akun berhasil dihapus");
    }

    public function cekExistingEmail($email) {
        $data = $this->repo->findByEmail($email);
        if($data != null) {
            throw new HttpException(400, "email $email sudah dipakai, silakan menggunakan email yang lain");
        }
    }

}
