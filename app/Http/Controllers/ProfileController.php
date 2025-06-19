<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Mengambil data user saat ini menggunakan Query Builder
        $user = DB::table('users')->where('id', Auth::id())->first();
        return view('profile', compact('user'));
    }

    public function update(Request $request)
    {
        $userId = Auth::id();

        // Mengubah input username menjadi lowercase sebelum validasi
        $request->merge([
            'username' => Str::lower($request->input('username')),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Validasi unique dengan mengabaikan user saat ini
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_.-]+$/', Rule::unique('users')->ignore($userId)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => 'nullable|min:4|string',
            'password_confirmation' => 'nullable|required_with:new_password|same:new_password'
        ], [
            'username.unique' => 'Username ini sudah digunakan oleh user lain.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, underscore, atau strip.',
            'email.unique' => 'Email ini sudah digunakan oleh user lain.',
            'current_password.required_with' => 'Password saat ini wajib diisi jika ingin mengubah password.',
            'new_password.required_with' => 'Password baru wajib diisi jika ingin mengubah password.',
            'password_confirmation.same' => 'Konfirmasi password baru tidak cocok.',
        ]);

        // Cek validasi password saat ini secara manual
        $validator->after(function ($validator) use ($request) {
            if ($request->filled('current_password')) {
                if (!Hash::check($request->input('current_password'), Auth::user()->password)) {
                    $validator->errors()->add('current_password', 'Password saat ini yang Anda masukkan salah.');
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->route('profile')->withErrors($validator)->withInput();
        }

        // Siapkan data untuk diupdate
        $updateData = [
            'name' => $request->input('name'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'updated_at' => now(),
        ];

        // Hanya update password jika field password baru diisi
        if ($request->filled('new_password')) {
            $updateData['password'] = Hash::make($request->input('new_password'));
        }

        // Update data menggunakan Query Builder
        DB::table('users')->where('id', $userId)->update($updateData);

        return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui.');
    }
}
