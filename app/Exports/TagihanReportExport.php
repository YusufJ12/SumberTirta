<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class TagihanReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $collection;
    protected $rowIndex = 1;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return [
            'No',
            'ID Tagihan',
            'ID Pelanggan',
            'Nama Pelanggan',
            'No. Meter',
            'Wilayah',
            'Periode',
            'Tanggal Terbit',
            'Jatuh Tempo',
            'Total Tagihan (Rp)',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $this->rowIndex++,
            $row->tagihan_id,
            $row->id_pelanggan_unik,
            $row->nama_pelanggan,
            $row->no_meter,
            $row->nama_wilayah,
            Carbon::create()->month($row->periode_tagihan_bulan)->isoFormat('MMMM') . ' ' . $row->periode_tagihan_tahun,
            Carbon::parse($row->tanggal_terbit)->format('d-m-Y'),
            Carbon::parse($row->tanggal_jatuh_tempo)->format('d-m-Y'),
            $row->total_tagihan,
            $row->status_tagihan,
        ];
    }
}
