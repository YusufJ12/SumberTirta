<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Tunggakan 5 Bulan Terakhir</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #777;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 14px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Rekapitulasi Tunggakan 5 Bulan Terakhir</h1>
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->isoFormat('D MMMM<y_bin_865>, HH:mm') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>NO</th>
                <th>NAMA</th>
                <th>NO METER</th>
                @foreach ($months as $month)
                    <th>{{ strtoupper($month['name']) }}</th>
                @endforeach
                <th>TOTAL TUNGGAKAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['nama_pelanggan'] }}</td>
                    <td class="text-center">{{ $row['no_meter'] }}</td>
                    @foreach ($months as $month)
                        <td class="text-right">
                            {{-- PERBAIKAN: Tampilkan angka 0 dan format Rupiah --}}
                            Rp {{ number_format($row[strtoupper($month['name'])], 0, ',', '.') }}
                        </td>
                    @endforeach
                    <td class="text-right">Rp {{ number_format($row['total_tunggakan'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + count($months) + 1 }}" class="text-center">Tidak ada data tunggakan yang sesuai.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
