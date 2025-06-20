<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TunggakanReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use App\Exports\RekapTunggakanExport;

class LaporanTunggakanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $wilayahs = DB::table('wilayah')->select('wilayah_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();
        $opsi_usia_tunggakan = ['' => 'Semua Usia', '1-30' => '1-30 Hari', '31-60' => '31-60 Hari', '61-90' => '61-90 Hari', '90+' => '> 90 Hari'];
        return view('laporan.tunggakan.index', compact('wilayahs', 'opsi_usia_tunggakan'));
    }

    private function buildFilteredTunggakanQuery(Request $request)
    {
        $today = Carbon::now()->toDateString();

        $innerQuery = DB::table('tagihan as tg')
            ->join('pelanggan as p', 'tg.pelanggan_id', '=', 'p.pelanggan_id')
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->leftJoin(DB::raw('(SELECT tagihan_id, SUM(jumlah_bayar) as total_dibayar FROM pembayaran WHERE status = "Valid" GROUP BY tagihan_id) as pemb'), 'tg.tagihan_id', '=', 'pemb.tagihan_id')
            ->select(
                'p.pelanggan_id',
                'p.id_pelanggan_unik',
                'p.nama_pelanggan',
                'p.no_meter',
                'p.alamat as alamat_pelanggan',
                'w.nama_wilayah',
                'tg.tagihan_id',
                'tg.periode_tagihan_tahun',
                'tg.periode_tagihan_bulan',
                'tg.tanggal_jatuh_tempo',
                'tg.tanggal_terbit',
                'tg.volume_pemakaian_saat_tagihan',
                'tg.sub_total_tagihan',
                'tg.denda',
                DB::raw('(tg.sub_total_tagihan + tg.denda - COALESCE(pemb.total_dibayar, 0)) as sisa_tunggakan'),
                DB::raw("DATEDIFF('{$today}', tg.tanggal_jatuh_tempo) as usia_tunggakan_hari")
            );

        $innerQuery->whereIn('tg.status_tagihan', ['BelumLunas', 'LunasSebagian']);
        $innerQuery->where('tg.tanggal_jatuh_tempo', '<', $today);

        if ($request->filled('filter_wilayah_tunggakan')) {
            $innerQuery->where('p.wilayah_id', $request->filter_wilayah_tunggakan);
        }

        $outerQuery = DB::table(DB::raw("({$innerQuery->toSql()}) as tunggakan"))
            ->mergeBindings($innerQuery);

        $outerQuery->where('sisa_tunggakan', '>', 0);

        if ($request->filled('filter_pelanggan_tunggakan')) {
            $searchTerm = $request->filter_pelanggan_tunggakan;
            $outerQuery->where(function ($q) use ($searchTerm) {
                $q->where('nama_pelanggan', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('id_pelanggan_unik', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('no_meter', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        if ($request->filled('filter_usia_tunggakan')) {
            $usia = $request->filter_usia_tunggakan;
            if ($usia == '1-30') {
                $outerQuery->whereBetween('usia_tunggakan_hari', [1, 30]);
            } elseif ($usia == '31-60') {
                $outerQuery->whereBetween('usia_tunggakan_hari', [31, 60]);
            } elseif ($usia == '61-90') {
                $outerQuery->whereBetween('usia_tunggakan_hari', [61, 90]);
            } elseif ($usia == '90+') {
                $outerQuery->where('usia_tunggakan_hari', '>', 90);
            }
        }

        return $outerQuery;
    }

    private function calculateCurrentDenda($row, $semuaAturanDenda, $today)
    {
        $row = (object) $row;
        if (!property_exists($row, 'volume_pemakaian_saat_tagihan') || (float)$row->volume_pemakaian_saat_tagihan == 0) {
            return 0;
        }

        $tanggalJatuhTempo = Carbon::parse($row->tanggal_jatuh_tempo);
        if ($today->lte($tanggalJatuhTempo)) {
            return 0;
        }

        $bulanTerlambat = $tanggalJatuhTempo->diffInMonths($today);
        $aturanDendaBerlaku = $semuaAturanDenda->filter(fn($aturan) => $aturan->keterlambatan_bulan <= $bulanTerlambat);

        return $aturanDendaBerlaku->isNotEmpty() ? $aturanDendaBerlaku->sum('nominal_denda_tambah') : 0;
    }

    private function getProcessedData(Request $request)
    {
        $today = Carbon::now();
        $semuaAturanDenda = DB::table('aturan_denda')->get();
        $data = $this->buildFilteredTunggakanQuery($request)->get();

        return $data->map(function ($item) use ($today, $semuaAturanDenda) {
            $dendaSekarang = $this->calculateCurrentDenda($item, $semuaAturanDenda->keyBy('keterlambatan_bulan'), $today);

            $item->total_tagihan_pokok = (float)$item->sub_total_tagihan;
            $item->denda_sekarang = $dendaSekarang;
            // PERBAIKAN: Menggunakan 'sisa_tunggakan' yang namanya sudah konsisten
            $item->total_keseluruhan = (float)$item->sisa_tunggakan + $dendaSekarang;

            return $item;
        });
    }

    public function getTunggakanData(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->getProcessedData($request);
            $summaryData = $data; // Gunakan data yang sudah diproses untuk summary

            $summary = [
                'jumlah_tagihan_menunggak' => $summaryData->count(),
                'jumlah_pelanggan_menunggak' => $summaryData->unique('pelanggan_id')->count(),
                'grand_total_sisa_tunggakan_rp' => 'Rp ' . number_format($summaryData->sum('total_keseluruhan'), 0, ',', '.'),
            ];

            return DataTables::of($data)
                ->with('summary', $summary)
                ->addIndexColumn()
                ->make(true);
        }
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getProcessedData($request);
        $timestamp = Carbon::now()->format('Ymd_His');
        return Excel::download(new TunggakanReportExport($data), "laporan_tunggakan_{$timestamp}.xlsx");
    }

    public function exportPdf(Request $request)
    {
        $tunggakans = $this->getProcessedData($request)->take(500);
        $pdf = Pdf::loadView('laporan.tunggakan.export_pdf', compact('tunggakans', 'request'));
        $timestamp = Carbon::now()->format('Ymd_His');
        return $pdf->setPaper('a4', 'landscape')->download("laporan_tunggakan_{$timestamp}.pdf");
    }

    private function getRekapData(Request $request)
    {
        // MODIFIKASI: Ambil data yang dibutuhkan untuk kalkulasi denda di awal
        $today = Carbon::now();
        $semuaAturanDenda = DB::table('aturan_denda')->get(); // Mengambil semua aturan denda

        // Bagian ini tetap sama
        $end_date = Carbon::now();
        $start_date = Carbon::now()->subMonths(4)->startOfMonth();

        $months = [];
        for ($date = $start_date->copy(); $date->lte($end_date); $date->addMonth()) {
            $months[] = [
                'year' => $date->year,
                'month' => $date->month,
                'name' => $date->isoFormat('MMMM'),
            ];
        }

        // Query pelanggan tetap sama
        $pelangganQuery = DB::table('pelanggan as p')->where('p.status_pelanggan', 'Aktif');
        if ($request->filled('filter_wilayah_tunggakan')) {
            $pelangganQuery->where('p.wilayah_id', $request->filter_wilayah_tunggakan);
        }
        if ($request->filled('filter_pelanggan_tunggakan')) {
            $pelangganQuery->where(function ($q) use ($request) {
                $q->where('p.nama_pelanggan', 'LIKE', '%' . $request->filter_pelanggan_tunggakan . '%')
                    ->orWhere('p.id_pelanggan_unik', 'LIKE', '%' . $request->filter_pelanggan_tunggakan . '%');
            });
        }
        $pelanggans = $pelangganQuery->orderBy('p.nama_pelanggan', 'asc')->get(['pelanggan_id', 'nama_pelanggan', 'no_meter']);

        // MODIFIKASI: Query tunggakan diubah untuk mengambil kolom-kolom yang diperlukan untuk menghitung denda
        $tunggakans = DB::table('tagihan')
            ->whereIn('pelanggan_id', $pelanggans->pluck('pelanggan_id'))
            ->whereIn('status_tagihan', ['BelumLunas', 'LunasSebagian'])
            ->where('tanggal_jatuh_tempo', '<', now())
            ->whereBetween('tanggal_terbit', [$start_date->toDateString(), $end_date->endOfDay()])
            ->get([
                'pelanggan_id',
                'tanggal_terbit',
                'sub_total_tagihan', // Menggunakan sub_total_tagihan sebagai dasar tunggakan pokok
                'tanggal_jatuh_tempo', // Diperlukan untuk hitung denda
                'volume_pemakaian_saat_tagihan' // Diperlukan untuk hitung denda
            ])
            ->groupBy('pelanggan_id');

        $rekapData = [];
        foreach ($pelanggans as $pelanggan) {
            $rowData = ['nama_pelanggan' => $pelanggan->nama_pelanggan, 'no_meter' => $pelanggan->no_meter, 'total_tunggakan' => 0];
            foreach ($months as $month) {
                $rowData[strtoupper($month['name'])] = 0;
            }

            if (isset($tunggakans[$pelanggan->pelanggan_id])) {
                $tunggakanPelanggan = $tunggakans[$pelanggan->pelanggan_id];

                // MODIFIKASI: Proses setiap tagihan untuk menghitung denda dan menambahkannya ke pokok
                foreach ($tunggakanPelanggan as $tunggakan) {
                    $periodeTunggakan = Carbon::parse($tunggakan->tanggal_terbit);
                    $bulanTunggakan = strtoupper($periodeTunggakan->isoFormat('MMMM'));

                    // Hitung denda saat ini untuk tagihan ini menggunakan fungsi yang sudah ada
                    $dendaSaatIni = $this->calculateCurrentDenda($tunggakan, $semuaAturanDenda, $today);

                    // Jumlahkan tagihan pokok dengan denda yang baru dihitung
                    $jumlahDenganDenda = (float)$tunggakan->sub_total_tagihan + $dendaSaatIni;

                    if (array_key_exists($bulanTunggakan, $rowData)) {
                        // Tambahkan jumlah yang SUDAH TERMASUK DENDA ke bulan yang sesuai
                        $rowData[$bulanTunggakan] += $jumlahDenganDenda;
                    }
                }

                // MODIFIKASI: Hitung ulang total tunggakan dari jumlah per bulan yang sudah mencakup denda
                $totalTunggakanPelanggan = 0;
                foreach ($months as $month) {
                    $totalTunggakanPelanggan += $rowData[strtoupper($month['name'])];
                }
                $rowData['total_tunggakan'] = $totalTunggakanPelanggan;
            }

            if ($rowData['total_tunggakan'] > 0) {
                $rekapData[] = $rowData;
            }
        }

        return ['data' => collect($rekapData), 'months' => $months];
    }

    public function exportRekap5Bulan(Request $request)
    {
        $processed = $this->getRekapData($request);
        $timestamp = Carbon::now()->format('Ymd_His');
        return Excel::download(new RekapTunggakanExport($processed['data'], $processed['months']), "rekap_tunggakan_5_bulan_{$timestamp}.xlsx");
    }

    public function exportRekap5BulanPdf(Request $request)
    {
        $processed = $this->getRekapData($request);
        $data = $processed['data']->take(500); // Batasi untuk PDF agar tidak terlalu berat
        $months = $processed['months'];

        $pdf = Pdf::loadView('laporan.tunggakan.rekap_export_pdf', compact('data', 'months', 'request'));
        $timestamp = Carbon::now()->format('Ymd_His');

        // PERBAIKAN: Mengubah orientasi menjadi 'portrait'
        return $pdf->setPaper('a4', 'portrait')->download("rekap_tunggakan_5_bulan_{$timestamp}.pdf");
    }
}
