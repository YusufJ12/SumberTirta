<?php
// Helper function untuk format Rupiah
function formatRp($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper function untuk padding text (KUNCI UTAMA)
function padTo($text, $length)
{
    // str_pad dengan STR_PAD_RIGHT akan menambah spasi di kanan teks
    return str_pad($text, $length, ' ', STR_PAD_RIGHT);
}

// Definisikan panjang padding yang konsisten
$labelPadding = 15; // Panjang karakter untuk semua label di sisi kiri
$labelPaddingKanan = 13; // Panjang karakter untuk label di sisi kanan
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #{{ $pembayaran->nama_pelanggan }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            /* Font Monospaced WAJIB untuk perataan ini */
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
            font-weight: bold;
        }

        @page {
            size: 9.5in 11in;
            margin: 0.15in 0.5in;
        }

        .receipt-container {
            width: 100%;
            max-width: 85ch;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 28pt;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .company-address,
        .company-phone {
            font-size: 13pt;
            margin-bottom: 5px;
        }

        .receipt-title {
            text-align: center;
            font-weight: bold;
            font-size: 15pt;
            margin-bottom: 25px;
            text-decoration: underline;
        }

        .receipt-details {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .receipt-details td {
            vertical-align: top;
            padding-bottom: 2px;
            white-space: pre-wrap;
            /* Ini akan mempertahankan spasi dan mematahkan baris pada spasi jika perlu */
            /* word-break: break-all; <-- INI DIHAPUS KARENA MENJADI PENYEBAB MASALAH */
        }

        /* Lebar kolom menggunakan 'ch' untuk presisi, tidak perlu diubah */
        .col-1 {
            /* width: 16ch; */
            font-size: 14pt;
        }

        .col-2 {
            /* width: 35ch; */
            font-size: 14pt;
        }

        .col-3 {
            /* width: 14ch; */
            font-size: 14pt;
        }

        .col-4 {
            /* width: 20ch; */
            font-size: 14pt;
        }

        .divider-row td {
            padding-top: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #000;
        }

        .footer {
            font-size: 11pt;
            text-align: center;
            margin-top: 25px;
        }

        .thank-you {
            text-align: center;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    @php
        $meterAwal = $pembayaran->meter_awal;
        $meterAkhir = $pembayaran->meter_akhir;
        $pemakaian = $pembayaran->volume_pemakaian;

        $standMeter = "{$meterAwal} - {$meterAkhir} ({$pemakaian} m³)";
    @endphp
    <div class="receipt-container">
        <div class="header">
            <div class="company-name">{{ strtoupper($data_perusahaan['nama_perusahaan']) }}</div>
            <div class="company-address">{{ $data_perusahaan['alamat_perusahaan'] }}</div>
            <div class="company-phone">{{ $data_perusahaan['telepon_perusahaan'] }}</div>
        </div>

        <div class="receipt-title">STRUK PEMBAYARAN TAGIHAN AIR</div>

        <table class="receipt-details">
            <tr>
                <td class="col-1">{{ padTo('IDPEL', $labelPadding) }}:</td>
                <td class="col-2">{{ $pembayaran->id_pelanggan_unik }}</td>
                <td class="col-3">{{ padTo('Tanggal', $labelPaddingKanan) }}:</td>
                <td class="col-4">{{ \Carbon\Carbon::parse($pembayaran->tanggal_bayar)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="col-1">{{ padTo('NAMA', $labelPadding) }}:</td>
                <td class="col-2">{{ $pembayaran->nama_pelanggan }}</td>
                <td class="col-3">{{ padTo('BL/TH', $labelPaddingKanan) }}:</td>
                <td class="col-4">{{ $pembayaran->tanggal_terbit_formatted }}</td>
            </tr>
            <tr>
                <td class="col-1">{{ padTo('ALAMAT', $labelPadding) }}:</td>
                <td class="col-2">{{ $pembayaran->alamat_pelanggan }}</td>
                <td class="col-3">{{ padTo('STAND METER', $labelPaddingKanan) }}:</td>
                <td class="col-4">{{ $standMeter }}</td>
            </tr>
            <tr>
                <td class="col-1">{{ padTo('RP.TAG AIR', $labelPadding) }}:</td>
                <td colspan="3">{{ formatRp($pembayaran->biaya_pemakaian) }}</td>
            </tr>
            <tr>
                <td class="col-1">{{ padTo('DENDA', $labelPadding) }}:</td>
                <td colspan="3">{{ formatRp($pembayaran->denda_pada_tagihan) }}</td>
            </tr>
            <tr>
                <td class="col-1">{{ padTo('ABONEMEN', $labelPadding) }}:</td>
                <td colspan="3">{{ formatRp($pembayaran->abonemen_saat_tagihan) }}</td>
            </tr>
            <tr>
                <td class="col-1">{{ padTo('TOTAL BAYAR', $labelPadding) }}:</td>
                <td colspan="3">{{ formatRp($pembayaran->total_tagihan_final) }}</td>
            </tr>
        </table>

        @if ($pembayaran->keterangan_pembayaran)
            <div
                style="font-size: 11pt; margin-top: 15px; margin-bottom: 10px; font-family: 'Courier New', Courier, monospace; font-weight: bold;">
                {{ padTo('Keterangan', $labelPadding) }}: {{ $pembayaran->keterangan_pembayaran }}
            </div>
        @endif

        <div class="footer">
            Untuk menghindari denda dan pemutusan, bayarlah pada tanggal 1 s/d 20<br>
            tiap bulannya.<br>
            Rincian Tagihan dapat menghubungi kami
        </div>

        <div class="thank-you">TERIMA KASIH</div>
    </div>
    <script>
        function inIframe() {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        }
        if (inIframe()) {
            document.documentElement.classList.add('is-iframe');
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>

</html>
