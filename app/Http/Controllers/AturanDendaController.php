<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class AturanDendaController extends Controller
{
    public function index()
    {
        return view('master.aturan_denda.index');
    }

    public function getAturanDendaData(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('aturan_denda')
                ->select(
                    'aturan_denda_id',
                    'deskripsi',
                    'keterlambatan_bulan',
                    'nominal_denda_tambah',
                    DB::raw("DATE_FORMAT(berlaku_mulai, '%d-%m-%Y') as berlaku_mulai_formatted"),
                    DB::raw("CASE WHEN berlaku_sampai IS NULL THEN 'Selamanya' ELSE DATE_FORMAT(berlaku_sampai, '%d-%m-%Y') END as berlaku_sampai_formatted")
                )
                ->orderBy('keterlambatan_bulan', 'asc')
                ->orderBy('berlaku_mulai', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('nominal_denda_tambah', function ($row) {
                    return 'Rp ' . number_format($row->nominal_denda_tambah, 0, ',', '.');
                })
                ->addColumn('berlaku_mulai', function ($row) {
                    return $row->berlaku_mulai_formatted;
                })
                ->addColumn('berlaku_sampai', function ($row) {
                    return $row->berlaku_sampai_formatted;
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="editButton btn btn-sm btn-warning mr-1" data-id="' . $row->aturan_denda_id . '"><i class="fas fa-edit fa-xs"></i> Edit</button>';
                    $btn .= '<button class="deleteButton btn btn-sm btn-danger" data-id="' . $row->aturan_denda_id . '"><i class="fas fa-trash fa-xs"></i> Delete</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    private function getValidationRules($aturan_denda_id = null)
    {
        return [
            'deskripsi' => 'required|string|max:255',
            'keterlambatan_bulan' => 'required|integer|min:0',
            'nominal_denda_tambah' => 'required|numeric|min:0',
            'berlaku_mulai' => 'required|date_format:Y-m-d',
            'berlaku_sampai' => 'nullable|date_format:Y-m-d|after_or_equal:berlaku_mulai',
        ];
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::table('aturan_denda')->insert([
                'deskripsi' => $request->deskripsi,
                'keterlambatan_bulan' => $request->keterlambatan_bulan,
                'nominal_denda_tambah' => $request->nominal_denda_tambah,
                'berlaku_mulai' => $request->berlaku_mulai,
                'berlaku_sampai' => $request->berlaku_sampai,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'Aturan denda berhasil ditambahkan.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan aturan denda: ' . $e->getMessage()], 500);
        }
    }

    public function edit($aturan_denda_id)
    {
        $aturan = DB::table('aturan_denda')->where('aturan_denda_id', $aturan_denda_id)->first();
        if ($aturan) {
            if ($aturan->berlaku_mulai) {
                $aturan->berlaku_mulai = Carbon::parse($aturan->berlaku_mulai)->format('Y-m-d');
            }
            if ($aturan->berlaku_sampai) {
                $aturan->berlaku_sampai = Carbon::parse($aturan->berlaku_sampai)->format('Y-m-d');
            }
            return response()->json($aturan);
        }
        return response()->json(['success' => false, 'message' => 'Aturan denda tidak ditemukan.'], 404);
    }

    public function update(Request $request, $aturan_denda_id)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules($aturan_denda_id));

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $affected = DB::table('aturan_denda')
                ->where('aturan_denda_id', $aturan_denda_id)
                ->update([
                    'deskripsi' => $request->deskripsi,
                    'keterlambatan_bulan' => $request->keterlambatan_bulan,
                    'nominal_denda_tambah' => $request->nominal_denda_tambah,
                    'berlaku_mulai' => $request->berlaku_mulai,
                    'berlaku_sampai' => $request->berlaku_sampai,
                    'updated_at' => now(),
                ]);

            if ($affected) {
                return response()->json(['success' => true, 'message' => 'Aturan denda berhasil diperbarui.']);
            }
            $exists = DB::table('aturan_denda')->where('aturan_denda_id', $aturan_denda_id)->exists();
            if (!$exists) {
                return response()->json(['success' => false, 'message' => 'Aturan denda tidak ditemukan.']);
            }
            return response()->json(['success' => true, 'message' => 'Tidak ada perubahan pada data aturan denda.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui aturan denda: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($aturan_denda_id)
    {
        // Aturan denda biasanya bisa dihapus jika tidak ada constraint foreign key yang ketat.
        // Namun, jika aturan sudah pernah dipakai dalam perhitungan tagihan historis,
        // mungkin lebih baik menonaktifkannya dengan mengisi 'berlaku_sampai' daripada menghapus.
        // Untuk saat ini, kita lakukan hard delete.
        try {
            $deleted = DB::table('aturan_denda')->where('aturan_denda_id', $aturan_denda_id)->delete();
            if ($deleted) {
                return response()->json(['success' => true, 'message' => 'Aturan denda berhasil dihapus.']);
            }
            return response()->json(['success' => false, 'message' => 'Aturan denda tidak ditemukan atau gagal dihapus.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus aturan denda: ' . $e->getMessage()], 500);
        }
    }
}
