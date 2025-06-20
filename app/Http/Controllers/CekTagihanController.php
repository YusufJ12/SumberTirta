<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class CekTagihanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Data ini dibutuhkan oleh dropdown di dalam modal pembayaran
        $metode_pembayaran_list = ['Tunai', 'TransferBank', 'Lainnya'];

        return view('cek_tagihan.index', compact('metode_pembayaran_list'));
    }

    /**
     * Mencari pelanggan untuk Select2
     */
    public function searchPelanggan(Request $request)
    {
        $searchTerm = $request->term ?? '';
        $pelanggans = DB::table('pelanggan as p')
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->where('p.status_pelanggan', 'Aktif')
            ->where(function ($query) use ($searchTerm) {
                if (!empty($searchTerm)) {
                    $query->where('p.nama_pelanggan', 'LIKE', "%{$searchTerm}%");
                }
            })
            ->select('p.pelanggan_id as id', 'p.nama_pelanggan', 'p.no_meter', 'p.id_pelanggan_unik', 'w.nama_wilayah')
            ->orderBy('p.nama_pelanggan', 'asc')
            ->limit(20)->get();

        foreach ($pelanggans as $pelanggan) {
            $pelanggan->text = "{$pelanggan->nama_pelanggan} ({$pelanggan->nama_wilayah}) - {$pelanggan->no_meter} ({$pelanggan->id_pelanggan_unik})";
        }

        return response()->json(['results' => $pelanggans]);
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
        if (!$request->filled('pelanggan_id')) {
            return DataTables::of(collect([]))->make(true);
        }

        $today = Carbon::now();
        $semuaAturanDenda = DB::table('aturan_denda')->get()->keyBy('keterlambatan_bulan');

        $query = DB::table('tagihan as tg')
            ->join('pelanggan as p', 'tg.pelanggan_id', '=', 'p.pelanggan_id')
            ->leftJoin('pencatatan_meter as pm', function ($join) {
                $join->on('tg.pelanggan_id', '=', 'pm.pelanggan_id')
                    ->on('tg.periode_tagihan_tahun', '=', 'pm.periode_tahun')
                    ->on('tg.periode_tagihan_bulan', '=', 'pm.periode_bulan');
            })
            ->where('tg.pelanggan_id', $request->pelanggan_id)
            ->whereIn('tg.status_tagihan', ['BelumLunas', 'LunasSebagian'])
            ->select(
                'p.id_pelanggan_unik',
                'p.nama_pelanggan',
                'tg.tagihan_id',
                'tg.periode_tagihan_tahun',
                'tg.periode_tagihan_bulan',
                'tg.tanggal_terbit',
                'tg.tanggal_jatuh_tempo',
                'tg.status_tagihan',
                'tg.sub_total_tagihan',
                'tg.denda',
                'tg.volume_pemakaian_saat_tagihan',
                'tg.total_tagihan',
                'pm.meter_awal',
                'pm.meter_akhir',
                'volume_pemakaian'
            )
            ->orderBy('tg.periode_tagihan_tahun', 'desc')
            ->orderBy('tg.periode_tagihan_bulan', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('status_tagihan', function ($row) {
                $badgeClass = 'badge-light';
                if ($row->status_tagihan == 'BelumLunas') $badgeClass = 'badge-danger';
                elseif ($row->status_tagihan == 'LunasSebagian') $badgeClass = 'badge-warning';
                elseif ($row->status_tagihan == 'Lunas') $badgeClass = 'badge-success';
                elseif ($row->status_tagihan == 'Dibatalkan') $badgeClass = 'badge-secondary';
                return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($row->status_tagihan) . '</span>';
            })
            ->addColumn('denda_sekarang', function ($row) use ($today, $semuaAturanDenda) {
                return $this->calculateCurrentDenda($row, $semuaAturanDenda, $today);
            })
            ->addColumn('total_tagihan_sekarang', function ($row) use ($today, $semuaAturanDenda) {
                $denda = $this->calculateCurrentDenda($row, $semuaAturanDenda, $today);
                return (float)$row->sub_total_tagihan + $denda;
            })
            ->addColumn('action', function ($row) {
                $btn = '';
                if ($row->status_tagihan == 'BelumLunas' || $row->status_tagihan == 'LunasSebagian') {
                    $btn = '<button class="bayarButton btn btn-sm btn-success" data-id="' . $row->tagihan_id . '"><i class="fas fa-money-check-alt fa-xs mr-1"></i> Bayar</button>';
                }
                return $btn;
            })
            ->rawColumns(['status_tagihan', 'action'])
            ->make(true);
    }
}
