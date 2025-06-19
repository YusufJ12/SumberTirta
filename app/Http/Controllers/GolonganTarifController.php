<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class GolonganTarifController extends Controller
{
    /**
     * Menampilkan halaman utama Master Golongan Tarif.
     */
    public function index()
    {
        return view('master.golongan_tarif.index');
    }

    /**
     * Mengambil data golongan tarif untuk server-side DataTables.
     */
    public function getGolonganTarifData(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('golongan_tarif')
                ->select('golongan_id', 'nama_golongan', 'deskripsi')
                ->orderBy('nama_golongan', 'asc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $editUrl = route('golongan_tarif.edit', $row->golongan_id);
                    $btn = '<button class="editButton btn btn-sm btn-warning mr-1" data-id="' . $row->golongan_id . '" data-url="' . $editUrl . '">Edit</button>';
                    $btn .= '<button class="deleteButton btn btn-sm btn-danger" data-id="' . $row->golongan_id . '">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Menyimpan golongan tarif baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_golongan' => 'required|string|max:100|unique:golongan_tarif,nama_golongan',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::table('golongan_tarif')->insert([
                'nama_golongan' => $request->nama_golongan,
                'deskripsi' => $request->deskripsi,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'Golongan tarif berhasil ditambahkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan golongan tarif: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengambil data golongan tarif untuk form edit (via AJAX).
     */
    public function edit($golongan_id)
    {
        $golongan = DB::table('golongan_tarif')->where('golongan_id', $golongan_id)->first();

        if ($golongan) {
            return response()->json($golongan);
        } else {
            return response()->json(['success' => false, 'message' => 'Golongan tarif tidak ditemukan.'], 404);
        }
    }

    /**
     * Memperbarui data golongan tarif.
     */
    public function update(Request $request, $golongan_id)
    {
        $validator = Validator::make($request->all(), [
            'nama_golongan' => 'required|string|max:100|unique:golongan_tarif,nama_golongan,' . $golongan_id . ',golongan_id',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $affected = DB::table('golongan_tarif')
                ->where('golongan_id', $golongan_id)
                ->update([
                    'nama_golongan' => $request->nama_golongan,
                    'deskripsi' => $request->deskripsi,
                    'updated_at' => now(),
                ]);

            if ($affected) {
                return response()->json(['success' => true, 'message' => 'Golongan tarif berhasil diperbarui.']);
            }
            return response()->json(['success' => false, 'message' => 'Tidak ada perubahan atau golongan tarif tidak ditemukan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui golongan tarif: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus data golongan tarif.
     */
    public function destroy($golongan_id)
    {
        // Pengecekan relasi: apakah golongan_id ini digunakan di tabel 'tarif'?
        $digunakan_di_tarif = DB::table('tarif')->where('golongan_id', $golongan_id)->exists();
        if ($digunakan_di_tarif) {
            return response()->json(['success' => false, 'message' => 'Golongan tarif tidak dapat dihapus karena masih digunakan oleh data tarif.'], 400);
        }

        try {
            $deleted = DB::table('golongan_tarif')->where('golongan_id', $golongan_id)->delete();
            if ($deleted) {
                return response()->json(['success' => true, 'message' => 'Golongan tarif berhasil dihapus.']);
            }
            return response()->json(['success' => false, 'message' => 'Golongan tarif tidak ditemukan atau gagal dihapus.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus golongan tarif: ' . $e->getMessage()], 500);
        }
    }
}
