<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PembayaranReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LaporanPembayaranController extends Controller
{
    public function index()
    {
        $kasirs = DB::table('users')->whereIn('type', [1, 2])->select('id', 'name')->orderBy('name')->get();
        $metode_pembayaran_list = ['Tunai', 'TransferBank', 'Lainnya'];
        return view('laporan.pembayaran.index', compact('kasirs', 'metode_pembayaran_list'));
    }

    private function buildFilteredPembayaranQueryBase(Request $request)
    {
        $query = DB::table('pembayaran as pb')
            ->join('tagihan as tg', 'pb.tagihan_id', '=', 'tg.tagihan_id')
            ->join('pelanggan as p', 'tg.pelanggan_id', '=', 'p.pelanggan_id')
            ->join('users as u', 'pb.diterima_oleh_user_id', '=', 'u.id')
            // PERUBAHAN: Hanya ambil pembayaran yang statusnya 'Valid' secara default
            ->where('pb.status', 'Valid');

        // Terapkan filter dari request
        if ($request->filled('filter_tanggal_mulai') && $request->filled('filter_tanggal_selesai')) {
            $query->whereBetween('pb.tanggal_bayar', [
                Carbon::parse($request->filter_tanggal_mulai)->startOfDay(),
                Carbon::parse($request->filter_tanggal_selesai)->endOfDay()
            ]);
        }
        if ($request->filled('filter_kasir') && $request->filter_kasir != '') {
            $query->where('pb.diterima_oleh_user_id', $request->filter_kasir);
        }
        if ($request->filled('filter_metode_bayar') && $request->filter_metode_bayar != '') {
            $query->where('pb.metode_pembayaran', $request->filter_metode_bayar);
        }
        if ($request->filled('filter_pelanggan_bayar') && $request->filter_pelanggan_bayar != '') {
            $query->where(function ($q) use ($request) {
                $q->where('p.nama_pelanggan', 'LIKE', '%' . $request->filter_pelanggan_bayar . '%')
                    ->orWhere('p.id_pelanggan_unik', 'LIKE', '%' . $request->filter_pelanggan_bayar . '%');
            });
        }
        return $query;
    }

    public function getPembayaranData(Request $request)
    {
        if ($request->ajax()) {
            $baseQueryBuilder = $this->buildFilteredPembayaranQueryBase($request);

            $summaryQuery = clone $baseQueryBuilder;
            $summaryData = $summaryQuery->selectRaw(
                'SUM(pb.jumlah_bayar) as grand_total_pembayaran,
                 COUNT(pb.pembayaran_id) as jumlah_transaksi_pembayaran'
            )->first();

            $summary = [
                'jumlah_transaksi_pembayaran' => $summaryData->jumlah_transaksi_pembayaran ?? 0,
                'grand_total_pembayaran_rp' => 'Rp ' . number_format($summaryData->grand_total_pembayaran ?? 0, 0, ',', '.'),
            ];

            $datatablesQuery = $this->buildFilteredPembayaranQueryBase($request)
                ->select(
                    'pb.pembayaran_id',
                    'pb.tanggal_bayar',
                    'tg.tagihan_id as no_tagihan',
                    'p.id_pelanggan_unik',
                    'p.nama_pelanggan',
                    'pb.jumlah_bayar',
                    'pb.metode_pembayaran',
                    'u.name as nama_kasir',
                    'pb.referensi_pembayaran',
                    'pb.keterangan as keterangan_pembayaran'
                )
                ->orderBy('pb.tanggal_bayar', 'desc')->orderBy('pb.pembayaran_id', 'desc');

            return DataTables::of($datatablesQuery)
                ->with('summary', $summary)
                ->addIndexColumn()
                ->editColumn('tanggal_bayar', function ($row) {
                    return Carbon::parse($row->tanggal_bayar)->format('d-m-Y H:i:s');
                })
                ->editColumn('jumlah_bayar', function ($row) {
                    return 'Rp ' . number_format($row->jumlah_bayar, 0, ',', '.');
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="btn btn-sm btn-info btnPrintStruk mr-1" data-id="' . $row->pembayaran_id . '" title="Cetak Struk"><i class="fas fa-print fa-xs"></i></button>';
                    $btn .= '<button class="cancelPaymentButton btn btn-sm btn-danger" data-id="' . $row->pembayaran_id . '" title="Batalkan Pembayaran Ini"><i class="fas fa-undo fa-xs"></i></button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Method baru untuk membatalkan transaksi pembayaran.
     */
    /**
     * Method baru untuk membatalkan transaksi pembayaran (TANPA ALASAN).
     */
    public function cancelPayment(Request $request, $pembayaran_id)
    {
        DB::beginTransaction();
        try {
            $pembayaran = DB::table('pembayaran')->where('pembayaran_id', $pembayaran_id)->first();

            if (!$pembayaran) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Transaksi pembayaran tidak ditemukan.'], 404);
            }
            if ($pembayaran->status == 'Dibatalkan') {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Transaksi ini sudah pernah dibatalkan.'], 400);
            }

            $user_name = Auth::user()->name ?? 'Sistem';
            $keterangan_pembatalan = '[DIBATALKAN PADA ' . Carbon::now()->isoFormat('D/MM/YY HH:mm') . ' OLEH ' . $user_name . ']';

            DB::table('pembayaran')
                ->where('pembayaran_id', $pembayaran_id)
                ->update([
                    'status' => 'Dibatalkan',
                    'keterangan' => ($pembayaran->keterangan ? $pembayaran->keterangan . "\n" : '') . $keterangan_pembatalan,
                    'updated_at' => now(),
                ]);

            $tagihan_id = $pembayaran->tagihan_id;

            $totalSudahDibayarValid = DB::table('pembayaran')
                ->where('tagihan_id', $tagihan_id)
                ->where('status', 'Valid')
                ->sum('jumlah_bayar');

            $tagihan = DB::table('tagihan')->where('tagihan_id', $tagihan_id)->select('total_tagihan')->first();

            $status_tagihan_baru = 'BelumLunas';
            if ($totalSudahDibayarValid > 0) {
                if ($totalSudahDibayarValid >= (float)$tagihan->total_tagihan) {
                    $status_tagihan_baru = 'Lunas';
                } else {
                    $status_tagihan_baru = 'LunasSebagian';
                }
            }

            DB::table('tagihan')
                ->where('tagihan_id', $tagihan_id)
                ->update(['status_tagihan' => $status_tagihan_baru, 'updated_at' => now()]);

            DB::commit();

            Log::info("Pembayaran ID {$pembayaran_id} dibatalkan oleh User ID " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Transaksi pembayaran berhasil dibatalkan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error pembatalan pembayaran ID {$pembayaran_id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal membatalkan pembayaran: Terjadi kesalahan internal.'], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        return Excel::download(new PembayaranReportExport($request), "laporan_pembayaran_{$timestamp}.xlsx");
    }

    public function exportPdf(Request $request)
    {
        $pembayarans = $this->buildFilteredPembayaranQueryBase($request)
            ->select(
                'pb.pembayaran_id',
                'pb.tanggal_bayar',
                'tg.tagihan_id as no_tagihan',
                'p.id_pelanggan_unik',
                'p.nama_pelanggan',
                'pb.jumlah_bayar',
                'pb.metode_pembayaran',
                'u.name as nama_kasir',
                'pb.referensi_pembayaran',
                'pb.keterangan as keterangan_pembayaran'
            )
            ->orderBy('pb.tanggal_bayar', 'desc')->orderBy('pb.pembayaran_id', 'desc')
            ->get();

        foreach ($pembayarans as $pembayaran) {
            $pembayaran->tanggal_bayar_formatted = Carbon::parse($pembayaran->tanggal_bayar)->format('d-m-Y H:i:s');
        }

        $summaryQueryForPdf = $this->buildFilteredPembayaranQueryBase($request);
        $summary = $summaryQueryForPdf->selectRaw(
            'SUM(pb.jumlah_bayar) as grand_total_pembayaran,
                 COUNT(pb.pembayaran_id) as jumlah_transaksi_pembayaran'
        )->first();

        $pdf = Pdf::loadView('laporan.pembayaran.export_pdf', compact('pembayarans', 'summary', 'request'));
        $timestamp = Carbon::now()->format('Ymd_His');
        return $pdf->download("laporan_pembayaran_{$timestamp}.pdf");
    }
}
