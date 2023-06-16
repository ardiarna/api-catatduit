<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    use ApiResponser;

    public function view() {
        return $this->successResponse(Auth::user());
    }

    public function children() {
        $children = User::where('parent_id', Auth::user()->id)->get();
        return $this->successResponse($children);
    }

    public function parent() {
        $parent = User::where('id', Auth::user()->parent_id)->first();
        return $this->successResponse($parent);
    }

    public function add(Request $req) {
        $this->validate($req, [
            'email' => 'required|email',
            'password' => 'required|confirmed',
            'nama' => 'required',
            'hp_kode' => 'required',
            'hp_nomor' => 'required',
        ]);
        $email = $req->input('email');
        $this->cekExistingEmail($email);
        $inputs = $req->all();
        $parent = Auth::user();
        $inputs['parent_id'] = $parent == null ? '0' : $parent->id;
        $inputs['password'] = Hash::make($req->input('password'));
        $user = User::create($inputs);
        return $this->createdResponse($user, 'Akun berhasil dibuat');
    }

    public function edit(Request $req) {
        $this->validate($req, [
            'email' => 'required|email',
            'nama' => 'required',
            'hp_kode' => 'required',
            'hp_nomor' => 'required',
        ]);
        $user = User::findOrFail(Auth::user()->id);
        $email = $req->input('email');
        if($email != $user->email) {
            $this->cekExistingEmail($email);
        }
        $user->email = $email;
        $user->nama = $req->input('nama');
        $user->hp_kode = $req->input('hp_kode');
        $user->hp_nomor = $req->input('hp_nomor');
        $user->save();
        return $this->successResponse($user, "Perubahan akun berhasil disimpan");
    }

    public function changePassword(Request $req) {
        $this->validate($req, [
            'old_password' => 'required',
            'password' => 'required|different:old_password|confirmed',
        ]);
        $user = User::findOrFail(Auth::user()->id);
        if(!Hash::check($req->input('old_password'), $user->password)) {
            throw new HttpException(400, "password lama anda tidak sesuai");
        }
        $user->password = Hash::make($req->input('password'));
        $user->save();
        return $this->successResponse($user, "Password berhasil diubah");
    }

    public function resetPassword(Request $req) {
        $this->validate($req, [
            'password' => 'required|confirmed',
        ]);
        $user = User::findOrFail(Auth::user()->id);
        $user->password = Hash::make($req->input('password'));
        $user->save();
        return $this->successResponse($user, "Password berhasil direset");
    }

    public function tokenPush(Request $req) {
        $this->validate($req, [
            'token_push' => 'required',
        ]);
        $user = User::findOrFail(Auth::user()->id);
        $user->token_push = $req->input('token_push');
        $user->save();
        return $this->successResponse($user, "Token push notification berhasil disimpan");
    }

    public function photo(Request $req) {
        if($req->hasFile('foto')) {
            $user = User::findOrFail(Auth::user()->id);
            $foto = $req->file('foto');
            if($foto->isValid()) {
                $namafoto = $user->id.'_'.$foto->getClientOriginalName();
                $foto->move(storage_path('images'), $namafoto);
                $user->foto = $namafoto;
                $user->save();
                return $this->successResponse($user, "Foto berhasil disimpan");
            } else {
                throw new HttpException(500, "foto gagal diupload");
            }
        } else {
            throw new HttpException(400, "file foto tidak ada");
        }
    }

    public function delete() {
        $user = User::destroy(Auth::user()->id);
        if($user == 0) {
            throw new HttpException(404, "Akun tidak ditemukan");
        }
        return $this->successResponse($user, "Akun berhasil dihapus");
    }

    public function cekExistingEmail($email) {
        $cek = User::where('email', $email)->count();
        if($cek > 0) {
            throw new HttpException(400, "email $email sudah dipakai, silakan menggunakan email yang lain");
        }
    }

}
