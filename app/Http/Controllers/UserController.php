<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $roles = DB::table('roles')->select('id', 'nm_roles')->get();
        return view('users.index', compact('roles'));
    }

    public function getUserData()
    {
        $users = DB::table('users')
            ->join('roles', 'users.type', '=', 'roles.id')
            ->select('users.id', 'users.name', 'users.username', 'users.email', 'roles.nm_roles');

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $btn = '<button class="editButton btn btn-sm btn-warning mr-1" data-id="' . $row->id . '"><i class="fas fa-edit fa-xs"></i> Edit</button>';
                $btn .= '<button class="deleteButton btn btn-sm btn-danger" data-id="' . $row->id . '"><i class="fas fa-trash fa-xs"></i> Delete</button>';
                return $btn;
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    protected function sanitizeAndValidate(Request $request, $userId = null)
    {
        $request->merge([
            'username' => Str::lower($request->input('username')),
        ]);

        $rules = [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_.-]+$/', Rule::unique('users')->ignore($userId)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'roles' => 'required|integer|exists:roles,id',
        ];

        $messages = [
            'username.unique' => 'Username ini sudah digunakan, silakan pilih yang lain.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, titik, underscore, atau strip.',
            'email.unique' => 'Email ini sudah terdaftar, silakan gunakan email lain.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];

        if (!$userId) {
            $rules['password'] = 'required|string|min:4|confirmed';
        } else {
            $rules['password'] = 'nullable|string|min:4|confirmed';
        }

        return Validator::make($request->all(), $rules, $messages);
    }

    public function store(Request $request)
    {
        $validator = $this->sanitizeAndValidate($request);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::table('users')->insert([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type' => $request->roles,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'User berhasil ditambahkan.']);
        } catch (\Exception $e) {
            Log::error("Error simpan user: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan user. Terjadi kesalahan server.'], 500);
        }
    }

    public function edit($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if ($user) {
            return response()->json($user);
        }
        return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
    }

    public function update(Request $request, $id)
    {
        $validator = $this->sanitizeAndValidate($request, $id);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $updateData = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'type' => $request->roles,
            'updated_at' => now(),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        try {
            DB::table('users')->where('id', $id)->update($updateData);
            return response()->json(['success' => true, 'message' => 'User berhasil diperbarui.']);
        } catch (\Exception $e) {
            Log::error("Error update user: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui user.'], 500);
        }
    }

    public function destroy($id)
    {
        if ($id == Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'], 403);
        }
        try {
            DB::table('users')->where('id', $id)->delete();
            return response()->json(['success' => true, 'message' => 'User berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error("Error hapus user: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus user.'], 500);
        }
    }
}
