<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends Controller
{
    use ApiResponser;

    public function login(Request $req) {
        $this->validate($req, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $req->only(['email', 'password']);
        if(! $token = Auth::attempt($credentials)) {
            throw new HttpException(401, "email atau password yang anda masukan salah");
        }
        return $this->successResponse([
            'user' => Auth::user(),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL()
        ]);
    }

    public function logout() {
        Auth::logout();
        return $this->successResponse(null, "logout berhasil");
    }

    public function refresh() {
        $token = Auth::refresh();
        return $this->successResponse([
            'user' => Auth::user(),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL()
        ]);
    }

}
