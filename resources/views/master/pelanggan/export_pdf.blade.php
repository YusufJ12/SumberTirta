<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Master Pelanggan</title>
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
            margin: 0.5in 0.25in;
        }

        /* Margin halaman lebih kecil untuk banyak kolom */
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Master Pelanggan</h1>
        <p>Sumber Tirta</p>
        <p>Dicetak pada: {{ Carbon\Carbon::now()->isoFormat('D MMMM Yogi, HH:mm') }}</p>
    </div>

    @if (
        $filterWilayahNama ||
            ($request->filled('filter_status_pelanggan') && $request->filter_status_pelanggan != '') ||
            $request->filled('filter_search_pelanggan'))
        <div class="filter-info">
            <strong>Filter Aktif:</strong>
            @if ($filterWilayahNama)
                Wilayah: {{ $filterWilayahNama }};
            @endif
            @if ($request->filled('filter_status_pelanggan') && $request->filter_status_pelanggan != '')
                Status: {{ $request->filter_status_pelanggan }};
            @endif
            @if ($request->filled('filter_search_pelanggan'))
                Pencarian: {{ $request->filter_search_pelanggan }};
            @endif
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Pelanggan</th>
                <th>No. Meter</th>
                <th>Nama Pelanggan</th>
                <th>Alamat</th>
                <th>Wilayah</th>
                <th>Kode Tarif</th>
                <th>Status</th>
                <th>Tgl Reg.</th>
                <th>Email</th>
                <th>Telepon</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pelanggans as $index => $pelanggan)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $pelanggan->id_pelanggan_unik }}</td>
                    <td>{{ $pelanggan->no_meter }}</td>
                    <td>{{ $pelanggan->nama_pelanggan }}</td>
                    <td>{{ $pelanggan->alamat }}</td>
                    <td>{{ $pelanggan->nama_wilayah }}</td>
                    <td>{{ $pelanggan->kode_tarif }}</td>
                    <td>{{ $pelanggan->status_pelanggan }}</td>
                    <td>{{ $pelanggan->tanggal_registrasi_formatted }}</td>
                    <td>{{ $pelanggan->email_kontak }}</td>
                    <td>{{ $pelanggan->no_telepon }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center;">Tidak ada data pelanggan yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Laporan Master Pelanggan - Sumber Tirta - Dicetak oleh {{ Auth::user()->name ?? 'Sistem' }}
    </div>
</body>

</html>
