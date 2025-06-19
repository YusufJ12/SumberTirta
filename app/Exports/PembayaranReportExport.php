<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use Illuminate\Http\Request; // Untuk tipe $request

class PembayaranReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request) // Ubah tipe parameter
    {
        $this->request = $request;
    }

    // Helper method untuk filter (bisa direplikasi dari controller atau di-refactor ke Trait/Service)
    private function applyFilters($query)
    {
        if ($this->request->filled('filter_tanggal_mulai') && $this->request->filled('filter_tanggal_selesai')) {
            $query->whereBetween('pb.tanggal_bayar', [
                Carbon::parse($this->request->filter_tanggal_mulai)->startOfDay(),
                Carbon::parse($this->request->filter_tanggal_selesai)->endOfDay()
            ]);
        }
        if ($this->request->filled('filter_kasir') && $this->request->filter_kasir != '') {
            $query->where('pb.diterima_oleh_user_id', $this->request->filter_kasir);
        }
        if ($this->request->filled('filter_metode_bayar') && $this->request->filter_metode_bayar != '') {
            $query->where('pb.metode_pembayaran', $this->request->filter_metode_bayar);
        }
        if ($this->request->filled('filter_pelanggan_bayar') && $this->request->filter_pelanggan_bayar != '') {
            $query->where(function ($q) {
                $q->where('p.nama_pelanggan', 'LIKE', '%' . $this->request->filter_pelanggan_bayar . '%')
                    ->orWhere('p.id_pelanggan_unik', 'LIKE', '%' . $this->request->filter_pelanggan_bayar . '%');
            });
        }
        return $query;
    }


    public function query()
    {
        $query = DB::table('pembayaran as pb')
            ->join('tagihan as tg', 'pb.tagihan_id', '=', 'tg.tagihan_id')
            ->join('pelanggan as p', 'tg.pelanggan_id', '=', 'p.pelanggan_id')
            ->join('users as u', 'pb.diterima_oleh_user_id', '=', 'u.id')
            ->select( // Pilih kolom yang akan diexport
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
            );

        $query = $this->applyFilters($query); // Terapkan filter

        return $query->orderBy('pb.tanggal_bayar', 'desc')->orderBy('pb.pembayaran_id', 'desc');
    }

    // ... (headings() dan map() tetap sama seperti sebelumnya) ...
    public function headings(): array
    {
        return [
            'ID Pembayaran',
            'Tanggal Bayar',
            'No. Tagihan',
            'ID Pelanggan',
            'Nama Pelanggan',
            'Jumlah Bayar (Rp)',
            'Metode Pembayaran',
            'Kasir',
            'No. Referensi',
            'Keterangan',
        ];
    }

    public function map($pembayaran): array
    {
        return [
            $pembayaran->pembayaran_id,
            Carbon::parse($pembayaran->tanggal_bayar)->format('d-m-Y H:i:s'),
            $pembayaran->no_tagihan,
            $pembayaran->id_pelanggan_unik,
            $pembayaran->nama_pelanggan,
            $pembayaran->jumlah_bayar,
            $pembayaran->metode_pembayaran,
            $pembayaran->nama_kasir,
            $pembayaran->referensi_pembayaran,
            $pembayaran->keterangan_pembayaran,
        ];
    }
}
