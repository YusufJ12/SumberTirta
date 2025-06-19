<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;      // Import Excel Facade
use App\Exports\CatatMeterStatusExport; // Import class Export Anda
use Barryvdh\DomPDF\Facade\Pdf;           // Import PDF Facade
use Illuminate\Support\Facades\Auth;      // Untuk info user di PDF
use Illuminate\Support\Facades\Validator; // Import Validator Facade

class DaftarCatatMeterController extends Controller
{
    public function index()
    {
        // ... (method index tetap sama)
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
        $wilayahs = DB::table('wilayah')->select('wilayah_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();
        $statuses = ['Semua', 'Belum Dicatat', 'Baru', 'SudahDitagih', 'Dibatalkan'];

        return view('monitoring.catat_meter.index', compact('tahuns', 'bulans', 'wilayahs', 'statuses'));
    }

    private function buildFilteredQueryBase(Request $request)
    {
        $tahun = $request->filter_tahun;
        $bulan = $request->filter_bulan;
        $akhirPeriode = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();

        $query = DB::table('pelanggan as p')
            ->leftJoin('pencatatan_meter as pm', function ($join) use ($tahun, $bulan) {
                $join->on('p.pelanggan_id', '=', 'pm.pelanggan_id')
                    ->where('pm.periode_tahun', '=', $tahun)
                    ->where('pm.periode_bulan', '=', $bulan);
            })
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->where('p.status_pelanggan', 'Aktif')
            ->whereDate('p.tanggal_registrasi', '<=', $akhirPeriode);

        // Terapkan filter
        if ($request->filled('filter_wilayah') && $request->filter_wilayah != '') {
            $query->where('p.wilayah_id', $request->filter_wilayah);
        }

        $statusFilter = $request->filter_status;
        if ($statusFilter && $statusFilter != 'Semua') {
            if ($statusFilter == 'Belum Dicatat') {
                $query->whereNull('pm.pencatatan_id');
            } else {
                $query->where('pm.status_pencatatan', '=', $statusFilter);
            }
        }
        return $query;
    }

    public function getData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filter_tahun' => 'required|integer',
            'filter_bulan' => 'required|integer|between:1,12',
            'filter_wilayah' => 'nullable|integer|exists:wilayah,wilayah_id',
            'filter_status' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Filter tidak valid.'], 422);
        }

        $query = $this->buildFilteredQueryBase($request)
            ->select(
                'p.id_pelanggan_unik',
                'p.nama_pelanggan',
                'p.no_meter',
                'p.alamat',
                'w.nama_wilayah',
                'pm.meter_awal',
                'pm.meter_akhir',
                'pm.tanggal_catat',
                DB::raw("CASE WHEN pm.pencatatan_id IS NULL THEN 'Belum Dicatat' ELSE pm.status_pencatatan END as status_pencatatan_final")
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('status_pencatatan_final', function ($row) {
                // ... (logika badge status seperti sebelumnya)
            })
            ->editColumn('meter_awal', function ($row) { /* ... format ... */
            })
            ->editColumn('meter_akhir', function ($row) { /* ... format ... */
            })
            ->editColumn('tanggal_catat', function ($row) { /* ... format ... */
            })
            ->rawColumns(['status_pencatatan_final'])
            ->make(true);
    }

    public function exportExcel(Request $request)
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        return Excel::download(new CatatMeterStatusExport($request), "status_pencatatan_meter_{$timestamp}.xlsx");
    }

    public function exportPdf(Request $request)
    {
        $queryBuilder = $this->buildFilteredQueryBase($request);
        $data = $queryBuilder->select( // Pilih kolom yang dibutuhkan untuk PDF
            'p.id_pelanggan_unik',
            'p.nama_pelanggan',
            'p.no_meter',
            'p.alamat',
            'w.nama_wilayah',
            'pm.meter_awal',
            'pm.meter_akhir',
            'pm.tanggal_catat',
            DB::raw("CASE WHEN pm.pencatatan_id IS NULL THEN 'Belum Dicatat' ELSE pm.status_pencatatan END as status_pencatatan_final")
        )
            ->orderBy('p.nama_pelanggan', 'asc')
            ->limit(1000) // Batasi jumlah data untuk PDF agar tidak terlalu besar
            ->get();

        foreach ($data as $row) {
            $row->tanggal_catat_formatted = $row->tanggal_catat ? Carbon::parse($row->tanggal_catat)->format('d-m-Y') : '-';
        }

        $filterWilayahNama = null;
        if ($request->filled('filter_wilayah')) {
            $filterWilayahNama = DB::table('wilayah')->where('wilayah_id', $request->filter_wilayah)->value('nama_wilayah');
        }

        $pdf = Pdf::loadView('monitoring.catat_meter.export_pdf', compact('data', 'request', 'filterWilayahNama'));
        $timestamp = Carbon::now()->format('Ymd_His');
        return $pdf->setPaper('a4', 'landscape')->download("status_pencatatan_meter_{$timestamp}.pdf");
    }
}
