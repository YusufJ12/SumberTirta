<!DOCTYPE html>
<html>

<head>
    <title>Laporan Tunggakan</title>
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

        .filter-info {
            margin-bottom: 15px;
            font-size: 11px;
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
        <h1>Laporan Tunggakan Pelanggan</h1>
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Pelanggan</th>
                <th>Nama</th>
                <th>No. Meter</th>
                <th>Periode</th>
                <th class="text-center">Usia</th>
                <th class="text-right">Total Tagihan</th>
                <th class="text-right">Denda</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tunggakans as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->id_pelanggan_unik }}</td>
                    <td>{{ $item->nama_pelanggan }}</td>
                    <td>{{ $item->no_meter }}</td>
                    <td>{{ \Carbon\Carbon::create()->month($item->periode_tagihan_bulan)->isoFormat('MMM YYYY') }}</td>
                    <td class="text-center">{{ $item->usia_tunggakan_hari }} Hari</td>
                    <td class="text-right">{{ number_format($item->total_tagihan_pokok, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->denda_sekarang, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->total_keseluruhan, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data tunggakan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
