<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // Import Str facade untuk str::lower

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Mengubah nama field input utama dari 'email' menjadi 'login'.
     */
    public function username()
    {
        return 'login';
    }

    /**
     * Memodifikasi proses pengambilan kredensial.
     */
    protected function credentials(Request $request)
    {
        $login = $request->input($this->username());

        // Cek apakah input adalah email atau username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Jika loginnya menggunakan username, ubah input menjadi huruf kecil
        $loginValue = ($field === 'username') ? Str::lower($login) : $login;

        return [
            $field => $loginValue,
            'password' => $request->password,
        ];
    }

    /**
     * Menyesuaikan pesan validasi untuk field 'login'.
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Kolom Username atau Email wajib diisi.',
            'password.required' => 'Kolom Password wajib diisi.',
        ]);
    }

    /**
     * Menyesuaikan pesan error jika login gagal.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                $this->username() => 'Kredensial yang Anda masukkan (Username/Email atau Password) tidak cocok.',
            ]);
    }
}
