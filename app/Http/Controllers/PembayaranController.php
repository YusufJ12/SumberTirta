<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PembayaranController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $metode_pembayaran = ['Tunai', 'TransferBank', 'Lainnya'];
        $datauser = ['nama' => Auth::user()->name];
        // Jika halaman ini adalah dashboard, gunakan view transaksi pembayaran
        if (request()->routeIs('home')) {
            return view('transaksi.pembayaran.index', compact('metode_pembayaran', 'datauser'));
        }
        return view('transaksi.pembayaran.index', compact('metode_pembayaran'));
    }

    public function searchTagihan(Request $request)
    {
        $searchTerm = $request->term ?? '';

        $tagihans = DB::table('tagihan as tg')
            ->join('pelanggan as p', 'tg.pelanggan_id', '=', 'p.pelanggan_id')
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->whereIn('tg.status_tagihan', ['BelumLunas', 'LunasSebagian'])
            ->when(!empty($searchTerm), function ($query) use ($searchTerm) {
                $query->where('p.nama_pelanggan', 'LIKE', "%{$searchTerm}%");
            })
            ->select(
                'tg.tagihan_id as id',
                'p.id_pelanggan_unik',
                'p.nama_pelanggan',
                'p.no_meter',
                'w.nama_wilayah',
                'tg.periode_tagihan_tahun',
                'tg.periode_tagihan_bulan',
                'tg.total_tagihan',
                'tg.status_tagihan',
                'tg.denda as denda_existing',
                'tg.tanggal_jatuh_tempo',
                'tg.sub_total_tagihan',
                'tg.volume_pemakaian_saat_tagihan',
                'tg.biaya_pemakaian',
                'tg.abonemen_saat_tagihan'
            )
            ->orderBy('tg.tanggal_jatuh_tempo', 'asc')
            ->limit(25)
            ->get();

        $formatted_tagihans = [];
        foreach ($tagihans as $tagihan) {
            $periodeFormatted = Carbon::createFromDate(null, $tagihan->periode_tagihan_bulan, null)->isoFormat('MMMM YYYY');
            $tagihan->text = "Tagihan #{$tagihan->id} - {$tagihan->nama_pelanggan} ({$tagihan->no_meter}) - Periode: {$periodeFormatted}";
            $formatted_tagihans[] = $tagihan;
        }

        return response()->json(['results' => $formatted_tagihans]);
    }

    /**
     * Menghitung denda dengan logika TIERED FALLBACK dan penanganan kasus pemakaian nol.
     */
    public function hitungDenda(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tagihan_id' => 'required|integer|exists:tagihan,tagihan_id',
            'tanggal_bayar' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $tagihan = DB::table('tagihan')->where('tagihan_id', $request->tagihan_id)
            ->select('periode_tagihan_tahun', 'periode_tagihan_bulan', 'tanggal_jatuh_tempo', 'volume_pemakaian_saat_tagihan', 'sub_total_tagihan')->first();

        if (!$tagihan) {
            return response()->json(['success' => false, 'message' => 'Tagihan tidak ditemukan.'], 404);
        }

        $subTotal = (float)$tagihan->sub_total_tagihan;

        if (!isset($tagihan->volume_pemakaian_saat_tagihan) || (float)$tagihan->volume_pemakaian_saat_tagihan == 0) {
            return response()->json([
                'success' => true,
                'denda_calculated' => 0,
                'denda_calculated_rp' => 'Rp 0',
                'total_dengan_denda' => $subTotal,
                'total_dengan_denda_rp' => 'Rp ' . number_format($subTotal, 0, ',', '.')
            ]);
        }

        $tanggalBayar = Carbon::parse($request->tanggal_bayar);
        $totalDenda = 0;
        $keterlambatanLevel = -1;

        $periodeTagihan = Carbon::createFromDate($tagihan->periode_tagihan_tahun, $tagihan->periode_tagihan_bulan, 1);
        $batasDendaSiklus1 = $periodeTagihan->copy()->addMonth()->day(20);

        if ($tanggalBayar->gt($batasDendaSiklus1)) {
            $keterlambatanLevel = 0;
            $bulanCek = $periodeTagihan->copy()->addMonths(2)->startOfMonth();
            while ($tanggalBayar->gte($bulanCek)) {
                $keterlambatanLevel++;
                $bulanCek->addMonth();
            }
        }

        if ($keterlambatanLevel !== -1) {
            // Ambil SEMUA aturan denda yang levelnya kurang dari atau sama dengan level keterlambatan saat ini
            $aturanDendas = DB::table('aturan_denda')
                ->where('keterlambatan_bulan', '<=', $keterlambatanLevel)
                ->whereDate('berlaku_mulai', '<=', $tanggalBayar->format('Y-m-d'))
                ->where(function ($q) use ($tanggalBayar) {
                    $q->whereDate('berlaku_sampai', '>=', $tanggalBayar->format('Y-m-d'))->orWhereNull('berlaku_sampai');
                })
                ->get(); // Gunakan get() untuk mengambil semua aturan yang cocok

            if ($aturanDendas->isNotEmpty()) {
                // JUMLAHKAN semua nominal denda dari aturan yang terlewati
                $totalDenda = $aturanDendas->sum('nominal_denda_tambah');
            }
        }

        $totalDenganDenda = $subTotal + $totalDenda;

        return response()->json([
            'success' => true,
            'denda_calculated' => $totalDenda,
            'denda_calculated_rp' => 'Rp ' . number_format($totalDenda, 0, ',', '.'),
            'total_dengan_denda' => $totalDenganDenda,
            'total_dengan_denda_rp' => 'Rp ' . number_format($totalDenganDenda, 0, ',', '.')
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tagihan_id_hidden' => 'required|integer|exists:tagihan,tagihan_id',
            'tanggal_bayar_submit' => 'required|date_format:Y-m-d',
            'jumlah_bayar_submit' => 'required|numeric|min:1',
            'denda_final_submit' => 'required|numeric|min:0',
            'total_akhir_submit' => 'required|numeric|min:0',
            'metode_pembayaran_submit' => 'required|string|in:Tunai,TransferBank,Lainnya',
            'referensi_pembayaran_submit' => 'nullable|string|max:100',
            'keterangan_pembayaran_submit' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $tagihan_id = $request->tagihan_id_hidden;
            $jumlah_bayar_saat_ini = (float)$request->jumlah_bayar_submit;
            $denda_final_dihitung_dari_client = (float)$request->denda_final_submit;

            $tagihan = DB::table('tagihan')->where('tagihan_id', $tagihan_id)->first();

            if (!$tagihan) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Tagihan tidak ditemukan.'], 404);
            }

            if ($tagihan->status_tagihan == 'Lunas' || $tagihan->status_tagihan == 'Dibatalkan') {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Tagihan ini sudah Lunas atau Dibatalkan.'], 400);
            }

            $total_tagihan_seharusnya_dibayar = (float)$tagihan->sub_total_tagihan + $denda_final_dihitung_dari_client;

            if ((float)$tagihan->denda != $denda_final_dihitung_dari_client) {
                DB::table('tagihan')
                    ->where('tagihan_id', $tagihan_id)
                    ->update(['denda' => $denda_final_dihitung_dari_client, 'updated_at' => now()]);
            }

            $pembayaranIdBaru = DB::table('pembayaran')->insertGetId([
                'tagihan_id' => $tagihan_id,
                'tanggal_bayar' => $request->tanggal_bayar_submit,
                'jumlah_bayar' => $jumlah_bayar_saat_ini,
                'metode_pembayaran' => $request->metode_pembayaran_submit,
                'referensi_pembayaran' => $request->referensi_pembayaran_submit,
                'diterima_oleh_user_id' => Auth::id(),
                'keterangan' => $request->keterangan_pembayaran_submit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $totalSudahDibayar = DB::table('pembayaran')
                ->where('tagihan_id', $tagihan_id)
                ->sum('jumlah_bayar');

            $status_tagihan_baru = 'LunasSebagian';
            if ($totalSudahDibayar >= $total_tagihan_seharusnya_dibayar) {
                $status_tagihan_baru = 'Lunas';
            }

            DB::table('tagihan')
                ->where('tagihan_id', $tagihan_id)
                ->update(['status_tagihan' => $status_tagihan_baru, 'updated_at' => now()]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil disimpan. Status tagihan: ' . $status_tagihan_baru,
                'pembayaran_id_baru' => $pembayaranIdBaru
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error simpan pembayaran: " . $e->getMessage() . " Stack: " . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan pembayaran: Terjadi kesalahan internal.'], 500);
        }
    }

    public function cetakStruk($pembayaran_id)
    {
        $pembayaran = DB::table('pembayaran as pb')
            ->join('tagihan as tg', 'pb.tagihan_id', '=', 'tg.tagihan_id')
            ->join('pelanggan as p', 'tg.pelanggan_id', '=', 'p.pelanggan_id')
            ->join('users as u', 'pb.diterima_oleh_user_id', '=', 'u.id')
            ->leftJoin('pencatatan_meter as pm', 'tg.pencatatan_id', '=', 'pm.pencatatan_id')
            ->select(
                'pb.pembayaran_id',
                'pb.tanggal_bayar',
                'pb.jumlah_bayar as jumlah_dibayar_saat_ini',
                'pb.metode_pembayaran',
                'pb.referensi_pembayaran',
                'pb.keterangan as keterangan_pembayaran',
                'u.name as nama_kasir',
                'tg.tagihan_id',
                'tg.periode_tagihan_tahun',
                'tg.periode_tagihan_bulan',
                'tg.biaya_pemakaian',
                'tg.abonemen_saat_tagihan',
                'tg.denda as denda_pada_tagihan',
                'tg.total_tagihan as total_tagihan_final',
                'tg.tanggal_terbit', // Pastikan kolom ini ada
                'p.id_pelanggan_unik',
                'p.nama_pelanggan',
                'p.alamat as alamat_pelanggan',
                'p.no_meter',
                'pm.meter_awal',
                'pm.meter_akhir',
                'pm.volume_pemakaian'
            )
            ->where('pb.pembayaran_id', $pembayaran_id)
            ->first();

        if (!$pembayaran) {
            abort(404, 'Data pembayaran tidak ditemukan.');
        }

        // Langkah 2: Hitung total pembayaran yang valid
        $totalSudahDibayar = DB::table('pembayaran')
            ->where('tagihan_id', $pembayaran->tagihan_id)
            ->where('status', 'Valid')
            ->sum('jumlah_bayar');

        // Langkah 3: Hitung sisa tagihan
        $pembayaran->sisa_setelah_pembayaran_ini = (float)$pembayaran->total_tagihan_final - $totalSudahDibayar;
        if ($pembayaran->sisa_setelah_pembayaran_ini < 0) {
            $pembayaran->sisa_setelah_pembayaran_ini = 0;
        }

        // Langkah 4: Format data lain yang dibutuhkan oleh view
        $pembayaran->tanggal_bayar_formatted = Carbon::parse($pembayaran->tanggal_bayar)->isoFormat('D MMMM YYYY, HH:mm');

        // ================================================================
        // PERUBAHAN UTAMA DI SINI: Mengubah format tanggal terbit
        // ================================================================
        $pembayaran->tanggal_terbit_formatted = Carbon::parse($pembayaran->tanggal_terbit)->isoFormat('MMMM YYYY');

        $data_perusahaan = [
            'nama_perusahaan' => config('app.company_name', 'SUMBER TIRTA'),
            'alamat_perusahaan' => config('app.company_address', 'Jl. Karanggawang Lama Kel.Sendangguwo, Kec. Tembalang, Semarang'),
            'telepon_perusahaan' => config('app.company_phone', 'Telp. Kantor 081349127722'),
        ];

        // Gunakan view struk dot matrix Anda
        return view('transaksi.pembayaran.struk', compact('pembayaran', 'data_perusahaan'));
    }
}
