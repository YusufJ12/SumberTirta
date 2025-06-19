<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use Illuminate\Http\Request; // Untuk tipe $request

class PelangganExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = DB::table('pelanggan as p')
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->join('tarif as t', 'p.tarif_id', '=', 't.tarif_id')
            ->select(
                'p.pelanggan_id',
                'p.id_pelanggan_unik',
                'p.no_meter',
                'p.nama_pelanggan',
                'p.alamat',
                'w.nama_wilayah',
                't.kode_tarif',
                't.nama_tarif',
                'p.status_pelanggan',
                'p.tanggal_registrasi',
                'p.meter_awal_saat_pemasangan',
                'p.email_kontak',
                'p.no_telepon',
                'p.keterangan',
                'p.tanggal_reset_meter_terakhir',
                'p.nilai_meter_saat_reset_terakhir'
            )->orderBy('p.nama_pelanggan', 'asc');

        // Terapkan filter dari request
        if ($this->request->filled('filter_wilayah_id') && $this->request->filter_wilayah_id != '') {
            $query->where('p.wilayah_id', $this->request->filter_wilayah_id);
        }
        if ($this->request->filled('filter_status_pelanggan') && $this->request->filter_status_pelanggan != '') {
            $query->where('p.status_pelanggan', $this->request->filter_status_pelanggan);
        }
        // Anda bisa menambahkan filter lain jika ada di form utama, misalnya pencarian nama
        if ($this->request->filled('search_pelanggan') && $this->request->search_pelanggan != '') {
            $searchTerm = $this->request->search_pelanggan;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('p.nama_pelanggan', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('p.id_pelanggan_unik', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('p.no_meter', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Internal ID',
            'ID Pelanggan',
            'No. Meter',
            'Nama Pelanggan',
            'Alamat',
            'Wilayah',
            'Kode Tarif',
            'Nama Tarif',
            'Status',
            'Tgl Registrasi',
            'Meter Awal Pasang',
            'Email',
            'No. Telepon',
            'Keterangan',
            'Tgl Reset Meter Terakhir',
            'Nilai Meter Saat Reset'
        ];
    }

    public function map($pelanggan): array
    {
        return [
            $pelanggan->pelanggan_id,
            $pelanggan->id_pelanggan_unik,
            $pelanggan->no_meter,
            $pelanggan->nama_pelanggan,
            $pelanggan->alamat,
            $pelanggan->nama_wilayah,
            $pelanggan->kode_tarif,
            $pelanggan->nama_tarif,
            $pelanggan->status_pelanggan,
            $pelanggan->tanggal_registrasi ? Carbon::parse($pelanggan->tanggal_registrasi)->format('d-m-Y') : '-',
            $pelanggan->meter_awal_saat_pemasangan,
            $pelanggan->email_kontak,
            $pelanggan->no_telepon,
            $pelanggan->keterangan,
            $pelanggan->tanggal_reset_meter_terakhir ? Carbon::parse($pelanggan->tanggal_reset_meter_terakhir)->format('d-m-Y H:i') : '-',
            $pelanggan->nilai_meter_saat_reset_terakhir,
        ];
    }
}
