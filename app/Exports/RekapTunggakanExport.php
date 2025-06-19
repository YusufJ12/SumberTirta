<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class RekapTunggakanExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithColumnFormatting
{
    protected $collection;
    protected $months;
    protected $headings;
    protected $rowIndex = 1;
    protected $totalColumns;

    public function __construct(Collection $collection, array $months)
    {
        $this->collection = $collection;
        $this->months = $months;

        $monthHeaders = array_map(fn($m) => strtoupper($m['name']), $this->months);
        $this->headings = array_merge(['NO', 'NAMA', 'NO METER'], $monthHeaders, ['TOTAL TUNGGAKAN']);
        $this->totalColumns = count($this->headings);
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        $mappedRow = [
            $this->rowIndex++,
            $row['nama_pelanggan'],
            $row['no_meter'],
        ];

        foreach ($this->months as $month) {
            $key = strtoupper($month['name']);
            $mappedRow[] = $row[$key] ?? 0; // Pastikan default ke 0 jika null
        }

        $mappedRow[] = $row['total_tunggakan'] ?? 0; // Pastikan default ke 0
        return $mappedRow;
    }

    public function styles(Worksheet $sheet)
    {
        // Header baris 1 bold & rata tengah
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Kolom A (NO) dan C (NO METER) rata tengah
        $sheet->getStyle('A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Kolom D sampai terakhir (nilai-nilai bulan & total tunggakan) rata kanan
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($this->totalColumns);
        $sheet->getStyle('D2:' . $lastColumn . $sheet->getHighestRow())->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    public function columnFormats(): array
    {
        $formats = [];
        $rupiahFormat = '"Rp "#,##0;-"Rp "#,##0;"Rp "0';

        // Format kolom D (4) sampai kolom terakhir
        for ($i = 4; $i <= $this->totalColumns; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $formats[$colLetter] = $rupiahFormat;
        }

        return $formats;
    }
}
