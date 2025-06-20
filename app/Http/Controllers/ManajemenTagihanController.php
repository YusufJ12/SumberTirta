<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth; // Untuk user ID saat pembatalan
use Illuminate\Support\Facades\Log; // Untuk logging error
use Illuminate\Support\Facades\Validator;
use App\Exports\TagihanReportExport; // Pastikan Anda sudah membuat Export Class ini
use Barryvdh\DomPDF\Facade\Pdf;


class ManajemenTagihanController extends Controller
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
        $statuses = ['BelumLunas', 'LunasSebagian', 'Lunas', 'Dibatalkan'];
        $wilayahs = DB::table('wilayah')->select('wilayah_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();
        $metode_pembayaran_list = ['Tunai', 'TransferBank', 'Lainnya'];

        return view('tagihan.manage.index', compact('tahuns', 'bulans', 'statuses', 'wilayahs', 'metode_pembayaran_list'));
    }

    private function calculateCurrentDenda($row, $semuaAturanDenda, $checkDate)
    {
        $row = (object) $row;
        // Jika tidak ada volume pemakaian, tidak ada denda.
        if (!property_exists($row, 'volume_pemakaian_saat_tagihan') || (float)$row->volume_pemakaian_saat_tagihan == 0) {
            return 0;
        }

        $tanggalPeriksa = $checkDate->copy()->startOfDay(); // Contoh: 19 Juni 2025
        $periodeTagihan = Carbon::create($row->periode_tagihan_tahun, $row->periode_tagihan_bulan, 1); // Contoh: 1 April 2025

        // Aturan 1: Batas toleransi pertama adalah tanggal 20 bulan berikutnya.
        $batasToleransi = $periodeTagihan->copy()->addMonth()->day(20); // Contoh: 20 Mei 2025

        // Jika tanggal periksa belum melewati batas toleransi, tidak ada denda.
        if ($tanggalPeriksa->lte($batasToleransi)) {
            return 0;
        }

        // Jika sudah lewat, denda level 0 (paling dasar) sudah pasti aktif.
        $keterlambatanLevel = 0;

        // Aturan 2: Cek untuk level denda berikutnya, yang dimulai setiap TANGGAL 1
        // pada bulan-bulan berikutnya setelah bulan jatuh tempo.
        $bulanPengecekan = $periodeTagihan->copy()->addMonths(2)->startOfMonth(); // Contoh: 1 Juni 2025

        while ($tanggalPeriksa->gte($bulanPengecekan)) {
            $keterlambatanLevel++;
            $bulanPengecekan->addMonth(); // Siapkan untuk iterasi berikutnya (menjadi 1 Juli, 1 Agu, dst.)
        }

        // Akumulasikan semua denda dari aturan yang levelnya terlewati.
        // Misal level=1, maka akan menjumlahkan denda untuk level 0 dan 1.
        $dendaTerakumulasi = $semuaAturanDenda
            ->where('keterlambatan_bulan', '<=', $keterlambatanLevel)
            ->sum('nominal_denda_tambah');

        return $dendaTerakumulasi;
    }


    public function getTagihanData(Request $request)
    {
        if ($request->ajax()) {
            $today = Carbon::now();

            $semuaAturanDenda = DB::table('aturan_denda')
                ->whereDate('berlaku_mulai', '<=', $today->format('Y-m-d'))
                ->where(function ($q) use ($today) {
                    $q->whereDate('berlaku_sampai', '>=', $today->format('Y-m-d'))->orWhereNull('berlaku_sampai');
                })->get()->keyBy('keterlambatan_bulan');

            $query = DB::table('tagihan as tg')
                ->join('pelanggan as p', 'tg.pelanggan_id', '=', 'p.pelanggan_id')
                ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
                ->select(
                    'tg.tagihan_id',
                    'p.id_pelanggan_unik',
                    'p.nama_pelanggan',
                    'tg.periode_tagihan_tahun',
                    'tg.periode_tagihan_bulan',
                    'tg.tanggal_jatuh_tempo',
                    'tg.status_tagihan',
                    'tg.sub_total_tagihan',
                    'tg.denda',
                    'tg.volume_pemakaian_saat_tagihan',
                    'tg.total_tagihan'
                );

            if ($request->filled('filter_tahun')) {
                $query->where('tg.periode_tagihan_tahun', $request->filter_tahun);
            }
            if ($request->filled('filter_bulan')) {
                $query->where('tg.periode_tagihan_bulan', $request->filter_bulan);
            }
            if ($request->filled('filter_status')) {
                $query->where('tg.status_tagihan', $request->filter_status);
            }
            if ($request->filled('filter_wilayah')) {
                $query->where('p.wilayah_id', $request->filter_wilayah);
            }
            if ($request->filled('filter_pelanggan')) {
                $query->where(function ($q) use ($request) {
                    $q->where('p.nama_pelanggan', 'LIKE', '%' . $request->filter_pelanggan . '%')
                        ->orWhere('p.no_meter', 'LIKE', '%' . $request->filter_pelanggan . '%');
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('periode_tagihan_bulan', function ($row) {
                    return Carbon::createFromDate(null, $row->periode_tagihan_bulan, null)->isoFormat('MMMM');
                })
                ->editColumn('tanggal_jatuh_tempo', function ($row) {
                    return Carbon::parse($row->tanggal_jatuh_tempo)->format('d-m-Y');
                })
                ->editColumn('status_tagihan', function ($row) {
                    $badgeClass = 'badge-light';
                    switch ($row->status_tagihan) {
                        case 'BelumLunas':
                            $badgeClass = 'badge-danger';
                            break;
                        case 'LunasSebagian':
                            $badgeClass = 'badge-warning';
                            break;
                        case 'Lunas':
                            $badgeClass = 'badge-success';
                            break;
                        case 'Dibatalkan':
                            $badgeClass = 'badge-secondary';
                            break;
                    }
                    return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($row->status_tagihan) . '</span>';
                })
                ->addColumn('denda_sekarang', function ($row) use ($today, $semuaAturanDenda) {
                    $denda = 0;
                    if ($row->status_tagihan == 'BelumLunas' || $row->status_tagihan == 'LunasSebagian') {
                        $denda = $this->calculateCurrentDenda($row, $semuaAturanDenda, $today);
                    } else {
                        $denda = $row->denda;
                    }
                    return 'Rp ' . number_format($denda, 0, ',', '.');
                })
                ->addColumn('total_tagihan_sekarang', function ($row) use ($today, $semuaAturanDenda) {
                    $total = 0;
                    if ($row->status_tagihan == 'BelumLunas' || $row->status_tagihan == 'LunasSebagian') {
                        $denda = $this->calculateCurrentDenda($row, $semuaAturanDenda, $today);
                        $total = (float)$row->sub_total_tagihan + $denda;
                    } else {
                        $total = (float)$row->total_tagihan;
                    }
                    return 'Rp ' . number_format($total, 0, ',', '.');
                })
                ->addColumn('action', function ($row) {
                    $btn = '';
                    if ($row->status_tagihan != 'Dibatalkan') {
                        $btn .= '<button class="detailButton btn btn-sm btn-info mr-1 mb-1" data-id="' . $row->tagihan_id . '" title="Lihat Detail"><i class="fas fa-eye fa-xs"></i></button>';
                        $btn .= '<a href="' . route('tagihan.manage.print', $row->tagihan_id) . '" target="_blank" class="btn btn-sm btn-primary mr-1 mb-1" title="Cetak Invoice"><i class="fas fa-print fa-xs"></i></a>';
                    }
                    if ($row->status_tagihan == 'BelumLunas' || $row->status_tagihan == 'LunasSebagian') {
                        $btn .= '<button class="bayarButton btn btn-sm btn-success mr-1 mb-1" data-id="' . $row->tagihan_id . '" title="Input Pembayaran"><i class="fas fa-money-check-alt fa-xs"></i></button>';
                        $btn .= '<button class="cancelButton btn btn-sm btn-danger mb-1" data-id="' . $row->tagihan_id . '" title="Batalkan Tagihan"><i class="fas fa-times-circle fa-xs"></i></button>';
                    }
                    return $btn;
                })
                ->rawColumns(['action', 'status_tagihan'])
                ->make(true);
        }
    }

    public function show($tagihan_id)
    {
        $tagihan = DB::table('tagihan as tg')
            ->join('pelanggan as p', 'tg.pelanggan_id', '=', 'p.pelanggan_id')
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->join('tarif as tr', 'tg.tarif_id_saat_tagihan', '=', 'tr.tarif_id')
            ->leftJoin('pencatatan_meter as pm', 'tg.pencatatan_id', '=', 'pm.pencatatan_id')
            ->leftJoin('users as u_catat', 'pm.dicatat_oleh_user_id', '=', 'u_catat.id')
            ->leftJoin('users as u_buat', 'tg.dibuat_oleh_user_id', '=', 'u_buat.id')
            ->select(
                'tg.*',
                'p.nama_pelanggan',
                'p.id_pelanggan_unik',
                'p.no_meter',
                'p.alamat as alamat_pelanggan',
                'w.nama_wilayah',
                'tr.kode_tarif as kode_tarif_tagihan',
                'tr.nama_tarif as nama_tarif_tagihan',
                'pm.meter_awal',
                'pm.meter_akhir',
                'pm.tanggal_catat as tanggal_catat_meter',
                'u_catat.name as nama_pencatat_meter',
                'u_buat.name as nama_pembuat_tagihan'
            )
            ->where('tg.tagihan_id', $tagihan_id)
            ->first();

        if ($tagihan) {
            $today = Carbon::now();
            $dendaSekarang = 0;

            if ($tagihan->status_tagihan == 'BelumLunas' || $tagihan->status_tagihan == 'LunasSebagian') {
                $semuaAturanDenda = DB::table('aturan_denda')
                    ->whereDate('berlaku_mulai', '<=', $today->format('Y-m-d'))
                    ->where(function ($q) use ($today) {
                        $q->whereDate('berlaku_sampai', '>=', $today->format('Y-m-d'))->orWhereNull('berlaku_sampai');
                    })->get();
                $dendaSekarang = $this->calculateCurrentDenda($tagihan, $semuaAturanDenda, $today);
            } else {
                $dendaSekarang = (float)$tagihan->denda;
            }

            $totalTagihanSekarang = (float)$tagihan->sub_total_tagihan + $dendaSekarang;

            // Tambahkan properti baru ke objek $tagihan
            $tagihan->denda_sekarang_rp = 'Rp ' . number_format($dendaSekarang, 0, ',', '.');
            $tagihan->total_tagihan_sekarang_rp = 'Rp ' . number_format($totalTagihanSekarang, 0, ',', '.');

            // Formatting data lain
            $tagihan->tanggal_terbit_formatted = Carbon::parse($tagihan->tanggal_terbit)->isoFormat('D MMMM YYYY');
            $tagihan->tanggal_jatuh_tempo_formatted = Carbon::parse($tagihan->tanggal_jatuh_tempo)->isoFormat('D MMMM YYYY');
            $tagihan->periode_tagihan_formatted = Carbon::create()->month($tagihan->periode_tagihan_bulan)->isoFormat('MMMM') . ' ' . $tagihan->periode_tagihan_tahun;
            $tagihan->tanggal_catat_meter_formatted = $tagihan->tanggal_catat_meter ? Carbon::parse($tagihan->tanggal_catat_meter)->isoFormat('D MMMM YYYY') : '-';
            $tagihan->abonemen_saat_tagihan_rp = 'Rp ' . number_format($tagihan->abonemen_saat_tagihan, 0, ',', '.');
            $tagihan->tarif_per_m3_saat_tagihan_rp = 'Rp ' . number_format($tagihan->tarif_per_m3_saat_tagihan, 0, ',', '.');
            $tagihan->volume_pemakaian_saat_tagihan_formatted = number_format($tagihan->volume_pemakaian_saat_tagihan, 0, ',', '.');
            $tagihan->biaya_pemakaian_rp = 'Rp ' . number_format($tagihan->biaya_pemakaian, 0, ',', '.');
            $tagihan->sub_total_tagihan_rp = 'Rp ' . number_format($tagihan->sub_total_tagihan, 0, ',', '.');
            $tagihan->denda_rp = 'Rp ' . number_format($tagihan->denda, 0, ',', '.');
            $tagihan->total_tagihan_rp = 'Rp ' . number_format($tagihan->total_tagihan, 0, ',', '.');
            $tagihan->created_at_formatted = Carbon::parse($tagihan->created_at)->isoFormat('D MMMM YYYY, HH:mm');


            return response()->json(['success' => true, 'data' => $tagihan]);
        }
        return response()->json(['success' => false, 'message' => 'Tagihan tidak ditemukan.'], 404);
    }


    /**
     * Menampilkan view untuk cetak invoice.
     */
    public function printInvoice($tagihan_id)
    {
        // Logika pengambilan data sama dengan show(), kita panggil saja
        $response = $this->show($tagihan_id);
        $responseData = $response->getData(true); // true untuk mendapatkan array

        if ($responseData['success']) {
            $tagihan = (object) $responseData['data']; // Ubah kembali ke object jika perlu
            return view('tagihan.cetak.invoice', compact('tagihan'));
        } else {
            // Handle jika tagihan tidak ditemukan
            abort(404, 'Tagihan tidak ditemukan.');
        }
    }

    /**
     * Membatalkan tagihan.
     */
    public function cancelBill(Request $request, $tagihan_id)
    {
        // $validator = Validator::make($request->all(), [
        //     'alasan_pembatalan' => 'required|string|max:255',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        // }

        DB::beginTransaction();
        try {
            $tagihan = DB::table('tagihan')
                ->where('tagihan_id', $tagihan_id)
                ->first();

            if (!$tagihan) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Tagihan tidak ditemukan.'], 404);
            }

            if ($tagihan->status_tagihan == 'Lunas' || $tagihan->status_tagihan == 'Dibatalkan') {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Tagihan yang sudah Lunas atau Dibatalkan tidak dapat dibatalkan lagi.'], 400);
            }

            // Update status tagihan
            DB::table('tagihan')
                ->where('tagihan_id', $tagihan_id)
                ->update([
                    'status_tagihan' => 'Dibatalkan',
                    // 'keterangan' => ($tagihan->keterangan ? $tagihan->keterangan . "\n" : '') . '[DIBATALKAN PADA ' . Carbon::now()->isoFormat('D/MM/YY HH:mm') . ' OLEH ' . Auth::user()->name . '] Alasan: ' . $request->alasan_pembatalan,
                    'updated_at' => now(),
                ]);

            // Kembalikan status pencatatan meter ke 'Baru' agar bisa diproses ulang atau dikoreksi
            if ($tagihan->pencatatan_id) {
                DB::table('pencatatan_meter')
                    ->where('pencatatan_id', $tagihan->pencatatan_id)
                    ->update(['status_pencatatan' => 'Baru', 'updated_at' => now()]);
            }

            DB::commit();
            Log::info("Tagihan ID {$tagihan_id} dibatalkan oleh User ID " . Auth::id() . ". Alasan: " . $request->alasan_pencatalan);
            return response()->json(['success' => true, 'message' => 'Tagihan berhasil dibatalkan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error pembatalan tagihan ID {$tagihan_id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal membatalkan tagihan: ' . $e->getMessage()], 500);
        }
    }
}
