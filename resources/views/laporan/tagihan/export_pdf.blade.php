<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Tagihan</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin: 2px 0;
            font-size: 12px;
        }

        .summary-table {
            margin-top: 20px;
            width: 50%;
            float: right;
        }

        .summary-table th {
            width: 60%;
        }

        .footer {
            font-size: 9px;
            text-align: center;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Tagihan</h1>
        <p>Periode Data:
            {{ $request->filter_bulan_laporan ? \Carbon\Carbon::create()->month($request->filter_bulan_laporan)->isoFormat('MMMM') : 'Semua Bulan' }}
            {{ $request->filter_tahun_laporan ?: 'Semua Tahun' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Tagihan</th>
                <th>ID Pelanggan</th>
                <th>Nama</th>
                <th>No. Meter</th>
                <th>Wilayah</th>
                <th>Periode</th>
                <th>Jatuh Tempo</th>
                <th class="text-right">Total Tagihan</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $index => $tagihan)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $tagihan->tagihan_id }}</td>
                    <td>{{ $tagihan->id_pelanggan_unik }}</td>
                    <td>{{ $tagihan->nama_pelanggan }}</td>
                    <td>{{ $tagihan->no_meter }}</td>
                    <td>{{ $tagihan->nama_wilayah }}</td>
                    <td>{{ \Carbon\Carbon::create()->month($tagihan->periode_tagihan_bulan)->isoFormat('MMMM') }}
                        {{ $tagihan->periode_tagihan_tahun }}</td>
                    <td>{{ \Carbon\Carbon::parse($tagihan->tanggal_jatuh_tempo)->format('d-m-Y') }}</td>
                    <td class="text-right">{{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $tagihan->status_tagihan }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data tagihan yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($summary && $summary->jumlah_tagihan > 0)
        <table class="summary-table">
            <tr>
                <th>Jumlah Tagihan</th>
                <td class="text-right">{{ $summary->jumlah_tagihan }}</td>
            </tr>
            <tr>
                <th>Grand Total Tagihan</th>
                <td class="text-right">Rp {{ number_format($summary->grand_total_tagihan, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Grand Total Dibayar</th>
                <td class="text-right">Rp {{ number_format($summary->grand_total_dibayar, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Grand Total Tunggakan</th>
                <td class="text-right">Rp
                    {{ number_format($summary->grand_total_tagihan - $summary->grand_total_dibayar, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    @endif

    <div class="footer">
        Dicetak pada: {{ \Carbon\Carbon::now()->isoFormat('D MMMM YYYY, HH:mm:ss') }} oleh
        {{ Auth::user()->name ?? 'Sistem' }}
    </div>
</body>

</html>
