<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TagihanReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class LaporanTagihanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $tahuns = DB::table('tagihan')->select('periode_tagihan_tahun')->distinct()->orderBy('periode_tagihan_tahun', 'desc')->pluck('periode_tagihan_tahun');
        $bulans = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $statuses = ['Semua', 'BelumLunas', 'LunasSebagian', 'Lunas', 'Dibatalkan'];
        $wilayahs = DB::table('wilayah')->select('wilayah_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();

        return view('laporan.tagihan.index', compact('tahuns', 'bulans', 'statuses', 'wilayahs'));
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = DB::table('tagihan as tg')
            ->join('pelanggan as p', 'tg.pelanggan_id', '=', 'p.pelanggan_id')
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id');

        if ($request->filled('filter_tahun_laporan')) {
            $query->where('tg.periode_tagihan_tahun', $request->filter_tahun_laporan);
        }
        if ($request->filled('filter_bulan_laporan')) {
            $query->where('tg.periode_tagihan_bulan', $request->filter_bulan_laporan);
        }
        if ($request->filled('filter_status_laporan') && $request->filter_status_laporan != 'Semua') {
            $query->where('tg.status_tagihan', $request->filter_status_laporan);
        }
        if ($request->filled('filter_wilayah_laporan')) {
            $query->where('p.wilayah_id', $request->filter_wilayah_laporan);
        }
        if ($request->filled('filter_pelanggan_laporan')) {
            $searchTerm = $request->filter_pelanggan_laporan;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('p.nama_pelanggan', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('p.id_pelanggan_unik', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('p.no_meter', 'LIKE', '%' . $searchTerm . '%');
            });
        }
        return $query;
    }

    public function getTagihanData(Request $request)
    {
        if ($request->ajax()) {
            $queryBuilder = $this->buildFilteredQuery($request);

            $summaryQuery = (clone $queryBuilder)->leftJoin(DB::raw('(SELECT tagihan_id, SUM(jumlah_bayar) as total_dibayar FROM pembayaran WHERE status = "Valid" GROUP BY tagihan_id) as pemb'), 'tg.tagihan_id', '=', 'pemb.tagihan_id');

            $summaryData = $summaryQuery->selectRaw(
                'COUNT(tg.tagihan_id) as jumlah_tagihan,
                 SUM(tg.total_tagihan) as grand_total_tagihan,
                 SUM(COALESCE(pemb.total_dibayar, 0)) as grand_total_dibayar'
            )->first();

            $summary = [
                'jumlah_tagihan' => $summaryData->jumlah_tagihan ?? 0,
                'grand_total_tagihan_rp' => 'Rp ' . number_format($summaryData->grand_total_tagihan ?? 0, 0, ',', '.'),
                'grand_total_dibayar_rp' => 'Rp ' . number_format($summaryData->grand_total_dibayar ?? 0, 0, ',', '.'),
                'grand_total_tunggakan_rp' => 'Rp ' . number_format(($summaryData->grand_total_tagihan ?? 0) - ($summaryData->grand_total_dibayar ?? 0), 0, ',', '.'),
            ];

            $datatablesQuery = $queryBuilder->select(
                'tg.tagihan_id',
                'p.id_pelanggan_unik',
                'p.nama_pelanggan',
                'p.no_meter',
                'w.nama_wilayah',
                'tg.periode_tagihan_tahun',
                'tg.periode_tagihan_bulan',
                'tg.tanggal_terbit',
                'tg.tanggal_jatuh_tempo',
                'tg.total_tagihan',
                'tg.status_tagihan'
            );

            return DataTables::of($datatablesQuery)
                ->with('summary', $summary)
                ->addIndexColumn()
                ->make(true);
        }
    }

    private function getExportData(Request $request)
    {
        return $this->buildFilteredQuery($request)
            ->select(
                'tg.tagihan_id',
                'p.id_pelanggan_unik',
                'p.nama_pelanggan',
                'p.no_meter',
                'w.nama_wilayah',
                'tg.periode_tagihan_tahun',
                'tg.periode_tagihan_bulan',
                'tg.tanggal_terbit',
                'tg.tanggal_jatuh_tempo',
                'tg.total_tagihan',
                'tg.status_tagihan'
            )->get();
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getExportData($request);
        $timestamp = Carbon::now()->format('Ymd_His');
        return Excel::download(new TagihanReportExport($data), "laporan_tagihan_{$timestamp}.xlsx");
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getExportData($request)->take(1000);
        $summaryQuery = $this->buildFilteredQuery($request)->leftJoin(DB::raw('(SELECT tagihan_id, SUM(jumlah_bayar) as total_dibayar FROM pembayaran WHERE status = "Valid" GROUP BY tagihan_id) as pemb'), 'tg.tagihan_id', '=', 'pemb.tagihan_id');
        $summary = $summaryQuery->selectRaw('SUM(tg.total_tagihan) as grand_total_tagihan, SUM(COALESCE(pemb.total_dibayar, 0)) as grand_total_dibayar, COUNT(tg.tagihan_id) as jumlah_tagihan')->first();

        $pdf = Pdf::loadView('laporan.tagihan.export_pdf', compact('data', 'summary', 'request'));
        $timestamp = Carbon::now()->format('Ymd_His');
        return $pdf->setPaper('a4', 'landscape')->download("laporan_tagihan_{$timestamp}.pdf");
    }
}
