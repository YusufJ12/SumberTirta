<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return view('roles.index');
    }

    public function getRoleData()
    {
        $roles = DB::table('roles')
            ->select('id', 'nm_roles', 'created_at', 'updated_at')
            ->get();

        return datatables()->of($roles)->toJson();
    }

    public function store(Request $request)
    {
        $role = new Role();
        $role->nm_roles = $request->name;
        $role->save();

        return response()->json(['success' => 'Role berhasil ditambahkan']);
    }

    // Memperbarui role yang ada
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $role->nm_roles = $request->name;
        $role->save();

        return response()->json(['success' => 'Role berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['success' => 'Role berhasil dihapus']);
    }

    public function show($id)
    {
        $role = Role::findOrFail($id);
        return response()->json($role);
    }
}
