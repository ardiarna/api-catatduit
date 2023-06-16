<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    use ApiResponser;

    public function view($id) {
        $user = User::findOrFail($id);
        return $this->successResponse($user);
    }

    public function children($id) {
        $users = User::where('parent_id', $id)->get();
        return $this->successResponse($users);
    }

    public function add(Request $req) {
        $this->validate($req, [
            'email' => 'required|email',
            'password' => 'required',
            'nama' => 'required',
            'hp_kode' => 'required',
            'hp_nomor' => 'required',
            'parent_id' => 'required',
        ]);
        $email = $req->input('email');
        $this->cekExistingEmail($email);
        $inputs = $req->all();
        $inputs['password'] = Crypt::encrypt($inputs['password']);
        $user = User::create($inputs);
        return $this->createdResponse($user, 'Akun berhasil dibuat');
    }

    public function edit(Request $req, $id) {
        $this->validate($req, [
            'email' => 'required|email',
            'nama' => 'required',
            'hp_kode' => 'required',
            'hp_nomor' => 'required',
        ]);
        $user = User::findOrFail($id);
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

    public function changePassword(Request $req, $id) {
        $this->validate($req, [
            'old_password' => 'required',
            'new_password' => 'required|different:old_password',
            'confirm_password' => 'required|same:new_password'
        ]);
        $user = User::findOrFail($id);
        if($req->input('old_password') != Crypt::decrypt($user->password)) {
            throw new HttpException(400, "password lama anda tidak sesuai");
        };
        $user->password = Crypt::encrypt($req->input('new_password'));
        $user->save();
        return $this->successResponse($user, "Password berhasil diubah");
    }

    public function resetPassword(Request $req, $id) {
        $this->validate($req, [
            'new_password' => 'required',
            'confirm_password' => 'required|same:new_password'
        ]);
        $user = User::findOrFail($id);
        $user->password = Crypt::encrypt($req->input('new_password'));
        $user->save();
        return $this->successResponse($user, "Password berhasil direset");
    }

    public function tokenPush(Request $req, $id) {
        $this->validate($req, [
            'token_push' => 'required',
        ]);
        $user = User::findOrFail($id);
        $user->token_push = $req->input('token_push');
        $user->save();
        return $this->successResponse($user, "Token push notification berhasil disimpan");
    }

    public function delete($id) {
        $user = User::destroy($id);
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
