<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice Tagihan #{{ $tagihan->tagihan_id }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
        }

        .header .company-details p {
            margin: 2px 0;
            font-size: 11px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table td {
            padding: 5px;
            vertical-align: top;
        }

        .details-section {
            margin-bottom: 30px;
        }

        .details-section .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #4e73df;
            border-bottom: 2px solid #4e73df;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .billing-table {
            margin-top: 20px;
            font-size: 11px;
        }

        .billing-table th,
        .billing-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .billing-table th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .font-weight-bold {
            font-weight: bold;
        }

        .total-section {
            margin-top: 20px;
        }

        .total-section table {
            width: 50%;
            float: right;
        }

        .total-section td {
            padding: 5px 8px;
        }

        .grand-total {
            font-size: 16px;
            font-weight: bold;
            color: #d9534f;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #777;
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .print-button-area {
            text-align: center;
            margin: 20px auto;
        }

        .print-button {
            padding: 10px 20px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        @media print {
            .print-button-area {
                display: none;
            }

            body {
                -webkit-print-color-adjust: exact;
            }

            .invoice-box {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }

            @page {
                margin: 0.75in;
            }
        }
    </style>
</head>

<body>
    <div class="print-button-area">
        <button class="print-button" onclick="window.print()">Cetak Invoice</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <h1>INVOICE TAGIHAN AIR</h1>
            <div class="company-details">
                <p><strong>SUMBER TIRTA</strong></p>
                <p>Jl. Karanggawang Lama Kel.Sendangguwo, Kec. Tembalang, Semarang | Telp: 081349127722</p>
            </div>
        </div>

        <div class="details-section">
            <table class="details-table">
                <tr>
                    <td style="width:50%;">
                        <strong>Ditagihkan Kepada:</strong><br>
                        {{ $tagihan->nama_pelanggan }}<br>
                        {{ $tagihan->alamat_pelanggan }}<br>
                        Wilayah: {{ $tagihan->nama_wilayah }}
                    </td>
                    <td style="width:50%; text-align: right;">
                        <strong>No. Tagihan:</strong> #{{ $tagihan->tagihan_id }}<br>
                        <strong>Tgl. Terbit:</strong> {{ $tagihan->tanggal_terbit_formatted }}<br>
                        <strong>Jatuh Tempo:</strong> {{ $tagihan->tanggal_jatuh_tempo_formatted }}<br>
                        <strong>Status:</strong> <span class="font-weight-bold">{{ $tagihan->status_tagihan }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="details-section">
            <div class="section-title">Rincian Pemakaian</div>
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Meter Awal</th>
                        <th>Meter Akhir</th>
                        <th class="text-center">Volume Pemakaian</th>
                        <th class="text-right">Tarif / M³</th>
                        <th class="text-right">Biaya Pemakaian</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $tagihan->meter_awal }} M³</td>
                        <td>{{ $tagihan->meter_akhir }} M³</td>
                        <td class="text-center">{{ $tagihan->volume_pemakaian_saat_tagihan_formatted }} M³</td>
                        <td class="text-right">{{ $tagihan->tarif_per_m3_saat_tagihan_rp }}</td>
                        <td class="text-right">{{ $tagihan->biaya_pemakaian_rp }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <table>
                <tr>
                    <td>Sub Total Pemakaian</td>
                    <td class="text-right">{{ $tagihan->biaya_pemakaian_rp }}</td>
                </tr>
                <tr>
                    <td>Biaya Abonemen</td>
                    <td class="text-right">{{ $tagihan->abonemen_saat_tagihan_rp }}</td>
                </tr>
                <tr>
                    <td>Denda Saat Ini</td>
                    <td class="text-right">{{ $tagihan->denda_sekarang_rp }}</td>
                </tr>
                <tr class="grand-total">
                    <td style="border-top: 2px solid #333; padding-top:10px;">TOTAL TAGIHAN</td>
                    <td class="text-right" style="border-top: 2px solid #333; padding-top:10px;">
                        {{ $tagihan->total_tagihan_sekarang_rp }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Mohon lakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari denda. Terima kasih.</p>
        </div>
    </div>
</body>

</html>
