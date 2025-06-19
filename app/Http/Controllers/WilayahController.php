<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables; // Import DataTables

class WilayahController extends Controller
{
    /**
     * Menampilkan halaman utama Master Wilayah.
     */
    public function index()
    {
        // Hanya menampilkan view, data akan di-load oleh DataTables via AJAX
        return view('master.wilayah.index'); // Akan kita buat view ini
    }

    /**
     * Mengambil data wilayah untuk server-side DataTables.
     */
    public function getWilayahData(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('wilayah')
                ->select('wilayah_id', 'nama_wilayah', 'keterangan')
                ->orderBy('nama_wilayah', 'asc');

            return DataTables::of($data)
                ->addIndexColumn() // Menambahkan kolom nomor urut DT_RowIndex
                ->addColumn('action', function ($row) {
                    $editUrl = route('wilayah.edit', $row->wilayah_id); // Meskipun edit akan via AJAX, URL ini bisa berguna
                    $btn = '<button class="editButton btn btn-sm btn-warning mr-1" data-id="' . $row->wilayah_id . '" data-url="' . $editUrl . '">Edit</button>';
                    $btn .= '<button class="deleteButton btn btn-sm btn-danger" data-id="' . $row->wilayah_id . '">Delete</button>';
                    return $btn;
                })
                ->rawColumns(['action']) // Karena kolom action berisi HTML
                ->make(true);
        }
    }

    /**
     * Menyimpan wilayah baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_wilayah' => 'required|string|max:100|unique:wilayah,nama_wilayah',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::table('wilayah')->insert([
                'nama_wilayah' => $request->nama_wilayah,
                'keterangan' => $request->keterangan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'Wilayah berhasil ditambahkan.']);
        } catch (\Exception $e) {
            // Log error jika perlu: Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan wilayah: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengambil data wilayah untuk form edit (via AJAX).
     */
    public function edit($wilayah_id)
    {
        $wilayah = DB::table('wilayah')->where('wilayah_id', $wilayah_id)->first();

        if ($wilayah) {
            return response()->json($wilayah);
        } else {
            return response()->json(['success' => false, 'message' => 'Wilayah tidak ditemukan.'], 404);
        }
    }

    /**
     * Memperbarui data wilayah.
     */
    public function update(Request $request, $wilayah_id)
    {
        $validator = Validator::make($request->all(), [
            'nama_wilayah' => 'required|string|max:100|unique:wilayah,nama_wilayah,' . $wilayah_id . ',wilayah_id',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $affected = DB::table('wilayah')
                ->where('wilayah_id', $wilayah_id)
                ->update([
                    'nama_wilayah' => $request->nama_wilayah,
                    'keterangan' => $request->keterangan,
                    'updated_at' => now(),
                ]);

            if ($affected) {
                return response()->json(['success' => true, 'message' => 'Wilayah berhasil diperbarui.']);
            }
            return response()->json(['success' => false, 'message' => 'Tidak ada perubahan atau wilayah tidak ditemukan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui wilayah: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus data wilayah.
     */
    public function destroy($wilayah_id)
    {
        // Pengecekan relasi (contoh)
        $digunakan_di_pelanggan = DB::table('pelanggan')->where('wilayah_id', $wilayah_id)->exists();
        if ($digunakan_di_pelanggan) {
            return response()->json(['success' => false, 'message' => 'Wilayah tidak dapat dihapus karena masih digunakan oleh data pelanggan.'], 400);
        }

        try {
            $deleted = DB::table('wilayah')->where('wilayah_id', $wilayah_id)->delete();
            if ($deleted) {
                return response()->json(['success' => true, 'message' => 'Wilayah berhasil dihapus.']);
            }
            return response()->json(['success' => false, 'message' => 'Wilayah tidak ditemukan atau gagal dihapus.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus wilayah: ' . $e->getMessage()], 500);
        }
    }
}
