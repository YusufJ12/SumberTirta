<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PencatatanMeterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // View tidak lagi butuh data periode, jadi lebih sederhana
        return view('transaksi.pencatatan_meter.index');
    }

    public function searchPelangganUntukDicatat(Request $request)
    {
        // Method ini diubah agar pencarian hanya berdasarkan nama pelanggan
        $searchTerm = $request->term ?? '';

        $pelanggans = DB::table('pelanggan as p')
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->where('p.status_pelanggan', 'Aktif')
            ->where(function ($query) use ($searchTerm) {
                // Hanya mencari jika search term tidak kosong
                if (!empty($searchTerm)) {
                    // Pencarian hanya pada kolom nama_pelanggan
                    $query->where('p.nama_pelanggan', 'LIKE', "%{$searchTerm}%");
                }
            })
            ->select('p.pelanggan_id as id', 'p.nama_pelanggan', 'p.no_meter', 'p.id_pelanggan_unik', 'w.nama_wilayah')
            ->orderBy('p.nama_pelanggan', 'asc')
            ->limit(20)
            ->get();

        $formatted_results = [];
        foreach ($pelanggans as $pelanggan) {
            // Format teks yang akan ditampilkan di hasil pencarian (misal: Select2)
            $pelanggan->text = "{$pelanggan->nama_pelanggan} - {$pelanggan->no_meter} ({$pelanggan->nama_wilayah})";
            $formatted_results[] = $pelanggan;
        }

        return response()->json(['results' => $formatted_results]);
    }

    /**
     * FUNGSI YANG DITULIS ULANG TOTAL UNTUK MEMASTIKAN AKURASI
     */
    public function getDetailPelangganUntukDicatat(Request $request)
    {
        $validator = Validator::make($request->all(), ['pelanggan_id' => 'required|integer|exists:pelanggan,pelanggan_id']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $pelanggan_id = $request->pelanggan_id;
        $pelanggan = DB::table('pelanggan')->where('pelanggan_id', $pelanggan_id)->first();
        if (!$pelanggan) {
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan.']);
        }

        // --- Langkah 1: Tentukan Periode Target ---
        $periodeTarget = null;
        $dataUntukEdit = DB::table('pencatatan_meter')
            ->where('pelanggan_id', $pelanggan_id)
            ->whereIn('status_pencatatan', ['Baru', 'Dibatalkan'])
            ->orderBy('periode_tahun', 'asc')->orderBy('periode_bulan', 'asc')
            ->first();

        if ($dataUntukEdit) {
            $periodeTarget = Carbon::createFromDate($dataUntukEdit->periode_tahun, $dataUntukEdit->periode_bulan, 1);
        } else {
            $pencatatanTerakhir = DB::table('pencatatan_meter')->where('pelanggan_id', $pelanggan_id)
                ->orderBy('periode_tahun', 'desc')->orderBy('periode_bulan', 'desc')->first();

            if ($pencatatanTerakhir) {
                $periodeTarget = Carbon::createFromDate($pencatatanTerakhir->periode_tahun, $pencatatanTerakhir->periode_bulan, 1)->addMonth();
            } else {
                $periodeTarget = Carbon::parse($pelanggan->tanggal_registrasi)->startOfMonth();
            }
        }

        // --- Langkah 2: Hitung Meter Awal Dinamis ---
        $periodeSebelumTarget = $periodeTarget->copy()->subMonth();
        $dataPeriodeSebelumnya = DB::table('pencatatan_meter')
            ->where('pelanggan_id', $pelanggan_id)
            ->where('periode_tahun', $periodeSebelumTarget->year)
            ->where('periode_bulan', $periodeSebelumTarget->month)
            ->first();

        $meter_awal_final = $dataPeriodeSebelumnya ? $dataPeriodeSebelumnya->meter_akhir : $pelanggan->meter_awal_saat_pemasangan;

        if ($pelanggan->tanggal_reset_meter_terakhir) {
            $tglReset = Carbon::parse($pelanggan->tanggal_reset_meter_terakhir);
            $tglKejadianTerakhir = $dataPeriodeSebelumnya ? Carbon::parse($dataPeriodeSebelumnya->created_at) : Carbon::createFromTimestamp(0);

            if ($tglReset->gt($tglKejadianTerakhir)) {
                $meter_awal_final = $pelanggan->nilai_meter_saat_reset_terakhir;
            }
        }

        // --- Langkah 3: Siapkan semua data untuk dikirim sebagai JSON ---
        $dataResponse = [
            'pelanggan_id' => $pelanggan->pelanggan_id,
            'nama_pelanggan' => $pelanggan->nama_pelanggan,
            'id_pelanggan_unik' => $pelanggan->id_pelanggan_unik,
            'alamat' => $pelanggan->alamat,
            'no_meter' => $pelanggan->no_meter,
            'nama_wilayah' => DB::table('wilayah')->where('wilayah_id', $pelanggan->wilayah_id)->value('nama_wilayah'),
            'periode_target_tahun' => $periodeTarget->year,
            'periode_target_bulan' => $periodeTarget->month,
            'periode_target_formatted' => $periodeTarget->isoFormat('MMMM YYYY'),
            'meter_awal_final' => (float)$meter_awal_final,
            'meter_akhir_final' => $dataUntukEdit ? $dataUntukEdit->meter_akhir : null,
            'tanggal_catat_final' => $dataUntukEdit ? Carbon::parse($dataUntukEdit->tanggal_catat)->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
            'keterangan_final' => $dataUntukEdit ? $dataUntukEdit->keterangan : null
        ];

        return response()->json(['success' => true, 'data' => $dataResponse]);
    }

    public function storeSingleReading(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pelanggan_id' => 'required|integer|exists:pelanggan,pelanggan_id',
            'periode_tahun' => 'required|integer',
            'periode_bulan' => 'required|integer|between:1,12',
            'meter_awal' => 'required|numeric',
            'meter_akhir' => 'required|numeric|gte:meter_awal',
            'tanggal_catat' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
        ], [
            'meter_akhir.gte' => 'Meter akhir harus lebih besar atau sama dengan meter awal.'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // PERBAIKAN 1: Hitung volume pemakaian di sini
            $volume_pemakaian = (float)$request->meter_akhir - (float)$request->meter_awal;

            $dataToSave = [
                'meter_awal' => $request->meter_awal,
                'meter_akhir' => $request->meter_akhir,
                'tanggal_catat' => $request->tanggal_catat,
                'dicatat_oleh_user_id' => Auth::id(),
                'status_pencatatan' => 'Baru',
                'keterangan' => $request->keterangan,
                'updated_at' => now(),
                // PERBAIKAN 2: Sertakan volume pemakaian dalam data yang disimpan
                'volume_pemakaian' => $volume_pemakaian,
            ];

            // Gunakan updateOrInsert untuk menangani kasus input baru atau revisi
            DB::table('pencatatan_meter')->updateOrInsert(
                [
                    'pelanggan_id' => $request->pelanggan_id,
                    'periode_tahun' => $request->periode_tahun,
                    'periode_bulan' => $request->periode_bulan,
                ],
                $dataToSave + ['created_at' => now()] // created_at hanya diisi saat insert baru
            );

            return response()->json(['success' => true, 'message' => 'Pencatatan meter berhasil disimpan.']);
        } catch (\Exception $e) {
            Log::error("Error simpan pencatatan tunggal: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data: Terjadi kesalahan server.'], 500);
        }
    }
}
