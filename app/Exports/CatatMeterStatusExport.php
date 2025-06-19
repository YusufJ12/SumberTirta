<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CatatMeterStatusExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $tahun = $this->request->filter_tahun;
        $bulan = $this->request->filter_bulan;
        $akhirPeriode = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();

        $query = DB::table('pelanggan as p')
            ->leftJoin('pencatatan_meter as pm', function ($join) use ($tahun, $bulan) {
                $join->on('p.pelanggan_id', '=', 'pm.pelanggan_id')
                    ->where('pm.periode_tahun', '=', $tahun)
                    ->where('pm.periode_bulan', '=', $bulan);
            })
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->where('p.status_pelanggan', 'Aktif')
            ->whereDate('p.tanggal_registrasi', '<=', $akhirPeriode)
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
            )->orderBy('w.nama_wilayah', 'asc')->orderBy('p.nama_pelanggan', 'asc');

        // Terapkan filter dari request
        if ($this->request->filled('filter_wilayah') && $this->request->filter_wilayah != '') {
            $query->where('p.wilayah_id', $this->request->filter_wilayah);
        }

        $statusFilter = $this->request->filter_status;
        if ($statusFilter && $statusFilter != 'Semua') {
            if ($statusFilter == 'Belum Dicatat') {
                $query->whereNull('pm.pencatatan_id');
            } else {
                $query->where('pm.status_pencatatan', '=', $statusFilter);
            }
        }
        return $query;
    }

    public function headings(): array
    {
        return [
            'ID Pelanggan',
            'Nama Pelanggan',
            'No. Meter',
            'Wilayah',
            'Alamat',
            'Meter Awal',
            'Meter Akhir',
            'Tanggal Catat',
            'Status Pencatatan',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id_pelanggan_unik,
            $row->nama_pelanggan,
            $row->no_meter,
            $row->nama_wilayah,
            $row->alamat,
            $row->meter_awal,
            $row->meter_akhir,
            $row->tanggal_catat ? Carbon::parse($row->tanggal_catat)->format('d-m-Y') : '-',
            $row->status_pencatatan_final,
        ];
    }
}
