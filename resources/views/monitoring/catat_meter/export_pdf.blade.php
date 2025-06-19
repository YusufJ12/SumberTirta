<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Status Pencatatan Meter</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
        }

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
            word-wrap: break-word;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
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

        .footer {
            font-size: 8px;
            text-align: center;
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
        }

        @page {
            margin: 0.5in 0.3in;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Status Pencatatan Meter</h1>
        <p>Periode: {{ \Carbon\Carbon::create()->month($request->filter_bulan)->isoFormat('MMMM') }}
            {{ $request->filter_tahun }}</p>
        @if ($filterWilayahNama)
            <p>Wilayah: {{ $filterWilayahNama }}</p>
        @endif
        @if ($request->filled('filter_status'))
            <p>Status: {{ $request->filter_status }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Pelanggan</th>
                <th>Nama Pelanggan</th>
                <th>No. Meter</th>
                <th>Wilayah</th>
                <th>Alamat</th>
                <th class="text-right">Meter Awal</th>
                <th class="text-right">Meter Akhir</th>
                <th>Tgl Catat</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row->id_pelanggan_unik }}</td>
                    <td>{{ $row->nama_pelanggan }}</td>
                    <td>{{ $row->no_meter }}</td>
                    <td>{{ $row->nama_wilayah }}</td>
                    <td>{{ $row->alamat }}</td>
                    <td class="text-right">
                        {{ $row->meter_awal !== null ? $row->meter_awal : '-' }}
                    </td>
                    <td class="text-right">
                        {{ $row->meter_akhir !== null ? $row->meter_akhir : '-' }}
                    </td>
                    <td class="text-center">{{ $row->tanggal_catat_formatted }}</td>
                    <td class="text-center">{{ $row->status_pencatatan_final }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Dicetak pada: {{ \Carbon\Carbon::now()->isoFormat('D MMMM YYYY, HH:mm:ss') }} oleh
        {{ Auth::user()->name ?? 'Sistem' }}</div>
</body>

</html>
