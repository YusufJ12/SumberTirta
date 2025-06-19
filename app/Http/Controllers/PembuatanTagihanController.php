<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PembuatanTagihanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $tahun_sekarang = Carbon::now()->year;
        $tahuns = [];
        for ($i = 0; $i < 3; $i++) {
            $tahuns[] = $tahun_sekarang - $i;
        }
        $bulans = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        return view('proses.pembuatan_tagihan.index', compact('tahuns', 'bulans'));
    }

    /**
     * Helper method untuk membangun query dasar yang sudah difilter
     */
    private function buildPreviewQuery(Request $request)
    {
        $tahun = $request->periode_tahun_tagihan;
        $bulan = $request->periode_bulan_tagihan;

        $query = DB::table('pencatatan_meter as pm')
            ->join('pelanggan as p', 'pm.pelanggan_id', '=', 'p.pelanggan_id')
            ->join('tarif as t', 'p.tarif_id', '=', 't.tarif_id')

            // ========================================================================
            // PERBAIKAN UTAMA DI SINI: Menggunakan whereNotExists
            // Ini akan memilih `pencatatan_meter` yang TIDAK MEMILIKI tagihan aktif terkait.
            // Tagihan yang statusnya 'Dibatalkan' akan diabaikan.
            // ========================================================================
            ->whereNotExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('tagihan')
                    ->whereColumn('tagihan.pencatatan_id', 'pm.pencatatan_id')
                    ->where('tagihan.status_tagihan', '!=', 'Dibatalkan');
            })

            ->where('pm.periode_tahun', $tahun)
            ->where('pm.periode_bulan', $bulan)
            ->where('pm.status_pencatatan', 'Baru') // Hanya ambil pencatatan yang siap diproses
            ->where('p.status_pelanggan', 'Aktif')
            ->where('t.status', 'Aktif');

        // Terapkan filter pelanggan opsional
        if ($request->filled('id_pelanggan_filter')) {
            $searchTerm = $request->id_pelanggan_filter;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('p.id_pelanggan_unik', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('p.no_meter', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('p.nama_pelanggan', 'LIKE', "%{$searchTerm}%");
            });
        }
        return $query;
    }

    public function previewPelangganUntukDitagih(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'periode_tahun_tagihan' => 'required|integer',
            'periode_bulan_tagihan' => 'required|integer|between:1,12',
            'id_pelanggan_filter' => 'nullable|string|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $query = $this->buildPreviewQuery($request);

        $calonTagihans = $query->select(
            'p.pelanggan_id',
            'p.id_pelanggan_unik',
            'p.nama_pelanggan',
            'p.no_meter',
            'pm.pencatatan_id',
            'pm.meter_awal',
            'pm.meter_akhir',
            'pm.volume_pemakaian',
            't.abonemen',
            't.tarif_per_m3',
            DB::raw('(pm.volume_pemakaian * t.tarif_per_m3) + t.abonemen as perkiraan_total_tagihan')
        )
            ->orderBy('p.nama_pelanggan')->get();

        $message = '';
        if ($calonTagihans->isEmpty()) {
            $message = 'Tidak ada pelanggan yang siap ditagih untuk periode dan filter ini, atau semua sudah diproses.';
            // ... (logika pesan detail jika filter pelanggan diisi, bisa disederhanakan jika tidak perlu) ...
        }

        foreach ($calonTagihans as $calon) {
            $calon->meter_awal_formatted = $calon->meter_awal;
            $calon->meter_akhir_formatted = $calon->meter_akhir;
            $calon->volume_pemakaian_formatted = $calon->volume_pemakaian;
            $calon->perkiraan_total_tagihan_rp = 'Rp ' . number_format($calon->perkiraan_total_tagihan, 0, ',', '.');
        }

        return response()->json(['success' => true, 'data' => $calonTagihans, 'message' => $message]);
    }

    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'periode_tahun_tagihan' => 'required|integer',
            'periode_bulan_tagihan' => 'required|integer|between:1,12',
            'id_pelanggan_filter' => 'nullable|string|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Periode tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $tahun = $request->periode_tahun_tagihan;
        $bulan = $request->periode_bulan_tagihan;
        $userId = Auth::id();

        $periodePencatatan = Carbon::createFromDate($tahun, $bulan, 1);
        $tanggalTerbit = $periodePencatatan->copy()->addMonth()->startOfMonth();
        $tanggalJatuhTempo = $tanggalTerbit->copy()->day(20);

        $pencatatans = $this->buildPreviewQuery($request)
            ->select(
                'pm.pencatatan_id',
                'pm.pelanggan_id',
                'pm.volume_pemakaian',
                'p.tarif_id as tarif_id_pelanggan',
                't.abonemen',
                't.tarif_per_m3'
            )
            ->get();

        if ($pencatatans->isEmpty()) { /* ... pesan jika kosong ... */
        }

        $berhasilDibuat = 0;
        $berhasilDiperbarui = 0;

        DB::beginTransaction();
        try {
            foreach ($pencatatans as $catat) {
                // Perhitungan biaya berdasarkan data pencatatan terbaru
                $biayaPemakaian = $catat->volume_pemakaian * $catat->tarif_per_m3;
                $subTotalTagihan = $biayaPemakaian + $catat->abonemen;

                // Data umum untuk insert atau update
                $dataTagihan = [
                    'pelanggan_id' => $catat->pelanggan_id,
                    'periode_tagihan_tahun' => $tahun,
                    'periode_tagihan_bulan' => $bulan,
                    'tanggal_terbit' => $tanggalTerbit->format('Y-m-d'),
                    'tanggal_jatuh_tempo' => $tanggalJatuhTempo->format('Y-m-d'),
                    'tarif_id_saat_tagihan' => $catat->tarif_id_pelanggan,
                    'abonemen_saat_tagihan' => $catat->abonemen,
                    'tarif_per_m3_saat_tagihan' => $catat->tarif_per_m3,
                    'volume_pemakaian_saat_tagihan' => $catat->volume_pemakaian,
                    'biaya_pemakaian' => $biayaPemakaian,
                    'sub_total_tagihan' => $subTotalTagihan,
                    'denda' => 0, // Denda direset menjadi 0 saat tagihan dibuat/dibuat ulang
                    'status_tagihan' => 'BelumLunas', // Status kembali menjadi BelumLunas
                    'dibuat_oleh_user_id' => $userId,
                    'updated_at' => now(),
                ];

                // Cek apakah ada tagihan (dibatalkan) yang sudah ada untuk pencatatan ini
                $existingTagihan = DB::table('tagihan')
                    ->where('pencatatan_id', $catat->pencatatan_id)
                    ->first();

                if ($existingTagihan) {
                    // JIKA ADA, UPDATE TAGIHAN YANG DIBATALKAN (HIDUPKAN KEMBALI)
                    DB::table('tagihan')
                        ->where('tagihan_id', $existingTagihan->tagihan_id)
                        ->update($dataTagihan);
                    $berhasilDiperbarui++;
                } else {
                    // JIKA TIDAK ADA, BUAT TAGIHAN BARU (PROSES NORMAL)
                    $dataTagihan['pencatatan_id'] = $catat->pencatatan_id; // hanya ditambahkan saat insert baru
                    $dataTagihan['created_at'] = now();
                    DB::table('tagihan')->insert($dataTagihan);
                    $berhasilDibuat++;
                }

                // Update status pencatatan meter menjadi 'SudahDitagih'
                DB::table('pencatatan_meter')
                    ->where('pencatatan_id', $catat->pencatatan_id)
                    ->update(['status_pencatatan' => 'SudahDitagih', 'updated_at' => now()]);
            }

            DB::commit();

            $message = "Proses selesai. Tagihan baru dibuat: $berhasilDibuat. Tagihan dibatalkan diperbarui: $berhasilDiperbarui.";
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error pembuatan tagihan: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . " | Request: " . json_encode($request->all()));
            return response()->json(['success' => false, 'message' => "Terjadi kesalahan saat memproses tagihan. Silakan hubungi administrator sistem."], 500);
        }
    }
}
