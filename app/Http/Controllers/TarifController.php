<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class TarifController extends Controller
{
    // public function index()
    // {
    //     $wilayahs = DB::table('wilayah')->select('wilayah_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();
    //     $golongan_tarifs = DB::table('golongan_tarif')->select('golongan_id', 'nama_golongan')->orderBy('nama_golongan')->get();
    //     return view('master.tarif.index', compact('wilayahs', 'golongan_tarifs'));
    // }

    public function index()
    {
        $wilayahs = DB::table('wilayah')->select('wilayah_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();
        // Kita tidak lagi mengambil atau mengirim $golongan_tarifs
        return view('master.tarif.index', compact('wilayahs'));
    }

    public function getTarifData(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('tarif as t')
                ->join('wilayah as w', 't.wilayah_id', '=', 'w.wilayah_id')
                // ->join('golongan_tarif as gt', 't.golongan_id', '=', 'gt.golongan_id')
                // ->select(
                //     't.tarif_id',
                //     't.kode_tarif',
                //     't.nama_tarif',
                //     'w.nama_wilayah',
                //     'gt.nama_golongan',
                //     't.abonemen',
                //     't.tarif_per_m3',
                //     't.status',
                //     DB::raw("DATE_FORMAT(t.berlaku_mulai, '%d-%m-%Y') as berlaku_mulai_formatted"),
                //     DB::raw("CASE WHEN t.berlaku_sampai IS NULL THEN 'Selamanya' ELSE DATE_FORMAT(t.berlaku_sampai, '%d-%m-%Y') END as berlaku_sampai_formatted")
                // )
                // ->orderBy('t.kode_tarif', 'asc');
                ->leftJoin('golongan_tarif as gt', 't.golongan_id', '=', 'gt.golongan_id')
                ->select(
                    't.tarif_id',
                    't.kode_tarif',
                    't.nama_tarif',
                    'w.nama_wilayah',
                    // Menggunakan COALESCE untuk menampilkan '-' jika nama_golongan NULL
                    DB::raw('COALESCE(gt.nama_golongan, "-") as nama_golongan'),
                    't.abonemen',
                    't.tarif_per_m3',
                    't.status',
                    't.berlaku_mulai',
                    't.berlaku_sampai'
                )
                ->orderBy('t.kode_tarif', 'asc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('abonemen', function ($row) {
                    return 'Rp ' . number_format($row->abonemen, 0, ',', '.');
                })
                ->editColumn('tarif_per_m3', function ($row) {
                    return 'Rp ' . number_format($row->tarif_per_m3, 0, ',', '.');
                })
                ->editColumn('berlaku_mulai', function ($row) {
                    return $row->berlaku_mulai ? Carbon::parse($row->berlaku_mulai)->isoFormat('D MMM YY') : '-';
                })
                ->editColumn('berlaku_sampai', function ($row) {
                    return $row->berlaku_sampai ? Carbon::parse($row->berlaku_sampai)->isoFormat('D MMM YY') : 'Selamanya';
                })
                ->editColumn('status', function ($row) {
                    return $row->status == 'Aktif' ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Tidak Aktif</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="editButton btn btn-sm btn-warning mr-1" data-id="' . $row->tarif_id . '"><i class="fas fa-edit fa-xs"></i> Edit</button>';
                    $btn .= '<button class="deleteButton btn btn-sm btn-danger" data-id="' . $row->tarif_id . '"><i class="fas fa-trash fa-xs"></i> Delete</button>';
                    return $btn;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }
    }

    private function getValidationRules($tarif_id = null)
    {
        $rules = [
            'nama_tarif' => 'required|string|max:255',
            'wilayah_id' => 'required|integer|exists:wilayah,wilayah_id',
            'golongan_id' => 'nullable|integer|exists:golongan_tarif,golongan_id',
            'abonemen' => 'required|numeric|min:0',
            'tarif_per_m3' => 'required|numeric|min:0',
            'status' => 'required|in:Aktif,TidakAktif',
            'deskripsi' => 'nullable|string|max:500',
            'berlaku_mulai' => 'required|date_format:Y-m-d',
            'berlaku_sampai' => 'nullable|date_format:Y-m-d|after_or_equal:berlaku_mulai',
        ];

        if ($tarif_id) { // Aturan untuk update jika kode_tarif boleh diubah (sebaiknya tidak)
            // $rules['kode_tarif'] = ['required', 'string', 'max:50', Rule::unique('tarif')->ignore($tarif_id, 'tarif_id')];
        }
        return $rules;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Generate kode_tarif otomatis
        $prefix = "TRF-";
        $yearMonth = Carbon::now()->format('Ym'); // Format: YYYYMM
        $baseKodeTarif = $prefix . $yearMonth . "-";

        $lastTarif = DB::table('tarif')
            ->where('kode_tarif', 'LIKE', $baseKodeTarif . '%')
            ->orderBy('kode_tarif', 'desc') // Urutkan berdasarkan string kode_tarif
            ->first();

        $nextSequence = 1;
        if ($lastTarif) {
            $parts = explode('-', $lastTarif->kode_tarif); // TRF-YYYYMM-XXX
            if (count($parts) === 3 && strlen($parts[2]) > 0) {
                $lastSequenceNumber = (int) $parts[2];
                $nextSequence = $lastSequenceNumber + 1;
            }
            // Jika format tidak cocok, default ke 1 atau log error
        }
        $newKodeTarif = $baseKodeTarif . str_pad($nextSequence, 3, '0', STR_PAD_LEFT); // Misal: TRF-202506-001

        try {
            DB::table('tarif')->insert([
                'kode_tarif' => $newKodeTarif,
                'nama_tarif' => $request->nama_tarif,
                'wilayah_id' => $request->wilayah_id,
                'golongan_id' => $request->golongan_id ?: null,
                'abonemen' => $request->abonemen,
                'tarif_per_m3' => $request->tarif_per_m3,
                'status' => $request->status,
                'deskripsi' => $request->deskripsi,
                'berlaku_mulai' => $request->berlaku_mulai,
                'berlaku_sampai' => $request->berlaku_sampai,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Tarif berhasil ditambahkan dengan Kode: ' . $newKodeTarif
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) { // Error code for duplicate entry
                return response()->json(['success' => false, 'message' => 'Gagal menghasilkan Kode Tarif unik. Coba lagi atau periksa data terakhir.'], 500);
            }
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan tarif: Terjadi kesalahan database.'], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan tarif: ' . $e->getMessage()], 500);
        }
    }

    public function edit($tarif_id)
    {
        $tarif = DB::table('tarif')->where('tarif_id', $tarif_id)->first();

        if ($tarif) {
            if ($tarif->berlaku_mulai) {
                $tarif->berlaku_mulai = Carbon::parse($tarif->berlaku_mulai)->format('Y-m-d');
            }
            if ($tarif->berlaku_sampai) {
                $tarif->berlaku_sampai = Carbon::parse($tarif->berlaku_sampai)->format('Y-m-d');
            }
            return response()->json($tarif);
        } else {
            return response()->json(['success' => false, 'message' => 'Tarif tidak ditemukan.'], 404);
        }
    }

    public function update(Request $request, $tarif_id)
    {
        // Kode tarif biasanya tidak diubah. Jika boleh, tambahkan validasi unique untuknya.
        $validationRules = $this->getValidationRules($tarif_id);
        // unset($validationRules['kode_tarif']); // Jika kode_tarif tidak boleh diubah

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $updateData = [
                // 'kode_tarif' => $request->kode_tarif, // Hanya jika kode tarif boleh diubah
                'nama_tarif' => $request->nama_tarif,
                'wilayah_id' => $request->wilayah_id,
                'golongan_id' => $request->golongan_id ?: null,
                'abonemen' => $request->abonemen,
                'tarif_per_m3' => $request->tarif_per_m3,
                'status' => $request->status,
                'deskripsi' => $request->deskripsi,
                'berlaku_mulai' => $request->berlaku_mulai,
                'berlaku_sampai' => $request->berlaku_sampai,
                'updated_at' => now(),
            ];

            $affected = DB::table('tarif')
                ->where('tarif_id', $tarif_id)
                ->update($updateData);

            if ($affected) {
                return response()->json(['success' => true, 'message' => 'Tarif berhasil diperbarui.']);
            }
            $exists = DB::table('tarif')->where('tarif_id', $tarif_id)->exists();
            if (!$exists) {
                return response()->json(['success' => false, 'message' => 'Tarif tidak ditemukan.']);
            }
            return response()->json(['success' => true, 'message' => 'Tidak ada perubahan pada data tarif.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui tarif: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($tarif_id)
    {
        $digunakan_di_pelanggan_aktif = DB::table('pelanggan as p')
            ->join('tarif as t', 'p.tarif_id', '=', 't.tarif_id')
            ->where('p.tarif_id', $tarif_id)
            // ->where('t.status', 'Aktif') // Cek status tarif, atau status pelanggan?
            ->where('p.status_pelanggan', 'Aktif') // Cek jika pelanggan yang menggunakan tarif ini masih aktif
            ->exists();

        if ($digunakan_di_pelanggan_aktif) {
            return response()->json(['success' => false, 'message' => 'Tarif tidak dapat dihapus karena masih digunakan oleh pelanggan aktif.'], 400);
        }

        try {
            $deleted = DB::table('tarif')->where('tarif_id', $tarif_id)->delete();
            if ($deleted) {
                return response()->json(['success' => true, 'message' => 'Tarif berhasil dihapus.']);
            }
            return response()->json(['success' => false, 'message' => 'Tarif tidak ditemukan atau gagal dihapus.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus tarif: ' . $e->getMessage()], 500);
        }
    }
}
