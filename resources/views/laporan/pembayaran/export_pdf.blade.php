<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Pembayaran</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
        }

        /* Ukuran font lebih kecil untuk PDF */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }

        /* Padding lebih kecil */
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
            margin-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            font-size: 16px;
        }

        .header p {
            margin: 2px 0;
            font-size: 10px;
        }

        .filter-info {
            margin-bottom: 10px;
            font-size: 9px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #ccc;
        }

        .summary-table {
            margin-top: 15px;
            width: 50%;
            float: right;
            font-size: 10px;
        }

        .summary-table th {
            width: 60%;
        }

        .footer {
            font-size: 8px;
            text-align: center;
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
        }

        @page {
            margin: 0.5in 0.3in;
        }

        /* Margin halaman lebih kecil */
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Pembayaran</h1>
        <p>Sumber Tirta</p>
        <p>Dicetak pada: {{ Carbon\Carbon::now()->isoFormat('D MMMM Yogi, HH:mm') }}</p>
    </div>

    @if (
        ($request->filled('filter_tanggal_mulai') && $request->filled('filter_tanggal_selesai')) ||
            $request->filled('filter_kasir') ||
            $request->filled('filter_metode_bayar') ||
            $request->filled('filter_pelanggan_bayar'))
        <div class="filter-info">
            <strong>Filter Aktif:</strong><br>
            @if ($request->filled('filter_tanggal_mulai') && $request->filled('filter_tanggal_selesai'))
                Periode: {{ Carbon\Carbon::parse($request->filter_tanggal_mulai)->format('d-m-Y') }} s/d
                {{ Carbon\Carbon::parse($request->filter_tanggal_selesai)->format('d-m-Y') }};
            @endif
            @if ($request->filled('filter_kasir'))
                @php
                    $namaKasir = \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $request->filter_kasir)
                        ->value('name');
                @endphp
                Kasir: {{ $namaKasir ?: $request->filter_kasir }};
            @endif
            @if ($request->filled('filter_metode_bayar'))
                Metode Bayar: {{ $request->filter_metode_bayar }};
            @endif
            @if ($request->filled('filter_pelanggan_bayar'))
                Pelanggan: {{ $request->filter_pelanggan_bayar }};
            @endif
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>ID Bayar</th>
                <th>Tgl Bayar</th>
                <th>No. Tagihan</th>
                <th>ID Pel.</th>
                <th>Nama Pelanggan</th>
                <th class="text-right">Jumlah Bayar</th>
                <th>Metode</th>
                <th>Kasir</th>
                <th>Ref.</th>
                <th>Ket.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pembayarans as $index => $pembayaran)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $pembayaran->pembayaran_id }}</td>
                    <td>{{ $pembayaran->tanggal_bayar_formatted }}</td>
                    <td class="text-center">{{ $pembayaran->no_tagihan }}</td>
                    <td>{{ $pembayaran->id_pelanggan_unik }}</td>
                    <td>{{ $pembayaran->nama_pelanggan }}</td>
                    <td class="text-right">{{ number_format($pembayaran->jumlah_bayar, 0, ',', '.') }}</td>
                    <td>{{ $pembayaran->metode_pembayaran }}</td>
                    <td>{{ $pembayaran->nama_kasir }}</td>
                    <td>{{ $pembayaran->referensi_pembayaran }}</td>
                    <td>{{ $pembayaran->keterangan_pembayaran }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">Tidak ada data pembayaran yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($summary && $summary->jumlah_transaksi_pembayaran > 0)
        <table class="summary-table">
            <tr>
                <th>Jumlah Transaksi Pembayaran</th>
                <td class="text-right">{{ $summary->jumlah_transaksi_pembayaran }}</td>
            </tr>
            <tr>
                <th>Grand Total Pembayaran</th>
                <td class="text-right">Rp {{ number_format($summary->grand_total_pembayaran, 0, ',', '.') }}</td>
            </tr>
        </table>
    @endif

    <div class="footer">
        Laporan Pembayaran - Sumber Tirta - Dicetak oleh {{ Auth::user()->name ?? 'Sistem' }}
    </div>
</body>

</html>
