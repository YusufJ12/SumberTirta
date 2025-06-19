<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GantiMeterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('manajemen_meter.index');
    }

    public function searchPelanggan(Request $request)
    {
        $searchTerm = $request->term ?? '';
        $pelanggans = DB::table('pelanggan as p')
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->where('p.status_pelanggan', 'Aktif')
            ->where(function ($query) use ($searchTerm) {
                if (!empty($searchTerm)) {
                    $query->where('p.id_pelanggan_unik', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('p.no_meter', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('p.nama_pelanggan', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('w.nama_wilayah', 'LIKE', "%{$searchTerm}%");
                }
            })
            ->select('p.pelanggan_id as id', 'p.nama_pelanggan', 'p.no_meter', 'p.id_pelanggan_unik', 'w.nama_wilayah')
            ->orderBy('p.nama_pelanggan', 'asc')
            ->limit(20)->get();

        $formatted_results = [];
        foreach ($pelanggans as $pelanggan) {
            $pelanggan->text = "{$pelanggan->nama_pelanggan} - {$pelanggan->no_meter} ({$pelanggan->nama_wilayah})";
            $formatted_results[] = $pelanggan;
        }

        return response()->json(['results' => $formatted_results]);
    }

    /**
     * Menyimpan data penggantian meter dengan nama kolom yang sudah disesuaikan.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pelanggan_id' => 'required|integer|exists:pelanggan,pelanggan_id',
            'tanggal_ganti' => 'required|date_format:Y-m-d\TH:i',
            'nilai_meter_baru' => 'required|numeric|min:0',
            'no_meter_baru' => 'nullable|string|max:50',
            'alasan' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $pelanggan = DB::table('pelanggan')->where('pelanggan_id', $request->pelanggan_id)->first();

            // Ambil nilai meter akhir terakhir untuk dicatat di log
            $pencatatanTerakhir = DB::table('pencatatan_meter')
                ->where('pelanggan_id', $pelanggan->pelanggan_id)
                ->orderBy('periode_tahun', 'desc')
                ->orderBy('periode_bulan', 'desc')
                ->first();

            // PERBAIKAN NAMA KOLOM DI SINI
            DB::table('log_perubahan_meter')->insert([
                'pelanggan_id' => $pelanggan->pelanggan_id,
                'tanggal_ganti' => Carbon::parse($request->tanggal_ganti)->format('Y-m-d H:i:s'), // Menggunakan tanggal_ganti
                'no_meter_lama' => $pelanggan->no_meter,
                'no_meter_baru' => $request->no_meter_baru,
                'meter_akhir_lama' => $pencatatanTerakhir ? $pencatatanTerakhir->meter_akhir : null,
                'meter_awal_baru' => $request->nilai_meter_baru, // Menggunakan meter_awal_baru
                'alasan' => $request->alasan, // Menggunakan alasan
                'dicatat_oleh_user_id' => Auth::id(), // Menggunakan dicatat_oleh_user_id
                'created_at' => now(),
            ]);

            $updateData = [
                'tanggal_reset_meter_terakhir' => Carbon::parse($request->tanggal_ganti)->format('Y-m-d H:i:s'),
                'nilai_meter_saat_reset_terakhir' => $request->nilai_meter_baru,
                'updated_at' => now(),
            ];

            if ($request->filled('no_meter_baru')) {
                $updateData['no_meter'] = $request->no_meter_baru;
            }

            DB::table('pelanggan')->where('pelanggan_id', $request->pelanggan_id)->update($updateData);


            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data penggantian meter berhasil disimpan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error simpan ganti meter: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data. Terjadi kesalahan server.'], 500);
        }
    }
}
