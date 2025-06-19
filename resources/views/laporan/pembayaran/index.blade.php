@extends('layouts.master')

@section('title', 'Laporan Pembayaran')

@push('styles')
    <style>
        .filter-card {
            background-color: #f8f9fc;
            border-radius: .35rem;
        }

        .summary-card {
            margin-top: 20px;
        }

        .summary-card .card-body {
            padding: 1rem;
        }

        .summary-card h6 {
            font-size: 0.8rem;
            color: #858796;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
        }

        .summary-card .h5 {
            margin-bottom: 0;
            font-weight: bold;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-receipt mr-2"></i>Laporan Pembayaran</h1>
        <p class="mb-4">Menampilkan daftar dan ringkasan transaksi pembayaran berdasarkan filter.</p>

        <div class="card shadow mb-4 filter-card">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Filter Laporan</h6>
            </div>
            <div class="card-body">
                <form id="filterLaporanPembayaranForm" class="form-row align-items-end">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label for="filter_tanggal_mulai">Tanggal Mulai</label>
                        <input type="date" class="form-control form-control-sm" id="filter_tanggal_mulai"
                            name="filter_tanggal_mulai" value="{{ Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label for="filter_tanggal_selesai">Tanggal Selesai</label>
                        <input type="date" class="form-control form-control-sm" id="filter_tanggal_selesai"
                            name="filter_tanggal_selesai" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_kasir">Kasir</label>
                        <select class="form-control form-control-sm" id="filter_kasir" name="filter_kasir">
                            <option value="">Semua Kasir</option>
                            @foreach ($kasirs as $kasir)
                                <option value="{{ $kasir->id }}">{{ $kasir->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_metode_bayar">Metode Bayar</label>
                        <select class="form-control form-control-sm" id="filter_metode_bayar" name="filter_metode_bayar">
                            <option value="">Semua Metode</option>
                            @foreach ($metode_pembayaran_list as $metode)
                                <option value="{{ $metode }}">{{ $metode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-12 mb-2">
                        <label for="filter_pelanggan_bayar">ID/Nama Pelanggan</label>
                        <input type="text" class="form-control form-control-sm" id="filter_pelanggan_bayar"
                            name="filter_pelanggan_bayar" placeholder="Cari...">
                    </div>
                    <div class="col-md-auto col-sm-12 mb-2 align-self-end">
                        <button type="button" id="btnFilterLaporanPembayaran" class="btn btn-sm btn-primary btn-block"><i
                                class="fas fa-search fa-sm"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row summary-card">
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <h6>Total Pembayaran Diterima (Filtered)</h6>
                                <div class="h5 text-success" id="summary_total_pembayaran">Rp 0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <h6>Jumlah Transaksi Pembayaran</h6>
                                <div class="h5 text-info" id="summary_jumlah_transaksi">0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-exchange-alt fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Detail Laporan Pembayaran</h6>
                <div>
                    <button class="btn btn-sm btn-success" id="btnExportExcelPembayaran"><i
                            class="fas fa-file-excel mr-1"></i> Export Excel</button>
                    <button class="btn btn-sm btn-danger" id="btnExportPdfPembayaran"><i class="fas fa-file-pdf mr-1"></i>
                        Export PDF</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="data-laporan-pembayaran"
                        class="table table-bordered table-striped table-hover display compact responsive table-sm"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
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
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            if ($('meta[name="csrf-token"]').length > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            }

            var tableLaporanPembayaran = $('#data-laporan-pembayaran').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('laporan.pembayaran.data') }}",
                    type: "GET",
                    data: function(d) {
                        d.filter_tanggal_mulai = $('#filter_tanggal_mulai').val();
                        d.filter_tanggal_selesai = $('#filter_tanggal_selesai').val();
                        d.filter_kasir = $('#filter_kasir').val();
                        d.filter_metode_bayar = $('#filter_metode_bayar').val();
                        d.filter_pelanggan_bayar = $('#filter_pelanggan_bayar').val();
                    },
                    dataSrc: function(json) {
                        if (json.summary) {
                            $('#summary_total_pembayaran').text(json.summary
                                .grand_total_pembayaran_rp || 'Rp 0');
                            $('#summary_jumlah_transaksi').text(json.summary
                                .jumlah_transaksi_pembayaran || '0');
                        }
                        return json.data;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'pembayaran_id',
                        name: 'pb.pembayaran_id',
                        className: 'text-center'
                    },
                    {
                        data: 'tanggal_bayar',
                        name: 'pb.tanggal_bayar'
                    },
                    {
                        data: 'no_tagihan',
                        name: 'tg.tagihan_id',
                        className: 'text-center'
                    },
                    {
                        data: 'id_pelanggan_unik',
                        name: 'p.id_pelanggan_unik'
                    },
                    {
                        data: 'nama_pelanggan',
                        name: 'p.nama_pelanggan'
                    },
                    {
                        data: 'jumlah_bayar',
                        name: 'pb.jumlah_bayar',
                        className: 'text-right'
                    },
                    {
                        data: 'metode_pembayaran',
                        name: 'pb.metode_pembayaran'
                    },
                    {
                        data: 'nama_kasir',
                        name: 'u.name'
                    },
                    {
                        data: 'referensi_pembayaran',
                        name: 'pb.referensi_pembayaran',
                        defaultContent: '-'
                    },
                    {
                        data: 'keterangan_pembayaran',
                        name: 'pb.keterangan',
                        defaultContent: '-'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                responsive: true,
                order: [
                    [2, 'desc']
                ], // Urutkan berdasarkan Tgl Bayar terbaru
                language: {
                    processing: "Sedang memproses...",
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                    infoFiltered: "(disaring dari _MAX_ total entri)",
                    zeroRecords: "Tidak ada data yang cocok ditemukan",
                    paginate: {
                        first: "Awal",
                        last: "Akhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });

            $('#btnFilterLaporanPembayaran').click(function() {
                tableLaporanPembayaran.ajax.reload();
            });
            $('#filter_pelanggan_bayar').keypress(function(event) {
                if (event.which == 13) {
                    $('#btnFilterLaporanPembayaran').click();
                }
            });

            function buildPembayaranFilterQueryString() {
                var params = {
                    filter_tanggal_mulai: $('#filter_tanggal_mulai').val(),
                    filter_tanggal_selesai: $('#filter_tanggal_selesai').val(),
                    filter_kasir: $('#filter_kasir').val(),
                    filter_metode_bayar: $('#filter_metode_bayar').val(),
                    filter_pelanggan_bayar: $('#filter_pelanggan_bayar').val()
                };
                return $.param(params);
            }

            $('#btnExportExcelPembayaran').click(function(e) {
                e.preventDefault();
                var queryString = buildPembayaranFilterQueryString();
                window.location.href = "{{ route('laporan.pembayaran.export_excel') }}?" + queryString;
            });

            $('#btnExportPdfPembayaran').click(function(e) {
                e.preventDefault();
                var queryString = buildPembayaranFilterQueryString();
                window.open("{{ route('laporan.pembayaran.export_pdf') }}?" + queryString, '_blank');
            });

            $('#data-laporan-pembayaran tbody').on('click', '.cancelPaymentButton', function() {
                var id = $(this).data('id');
                var url = "{{ url('/laporan/pembayaran/cancel') }}/" + id; // URL untuk request

                Swal.fire({
                    title: 'Anda yakin?',
                    text: "Aksi ini akan membatalkan transaksi. Aksi ini tidak dapat diurungkan!",
                    icon: 'warning',
                    showCancelButton: true, // Tampilkan tombol "Tidak"
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, batalkan!',
                    cancelButtonText: 'Tidak'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika pengguna klik "Ya, batalkan!"
                        // Tampilkan loading
                        Swal.fire({
                            title: 'Memproses...',
                            text: 'Mohon tunggu sebentar.',
                            didOpen: () => {
                                Swal.showLoading()
                            },
                            allowOutsideClick: false
                        });

                        $.ajax({
                            url: url,
                            type: 'POST', // Menggunakan POST sesuai route yang akan kita buat
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Dibatalkan!', response.message,
                                        'success');
                                    tableLaporanPembayaran.ajax.reload(null,
                                    false); // Reload tabel
                                } else {
                                    Swal.fire('Gagal!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                var errorMsg = 'Terjadi kesalahan. Silakan coba lagi.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                });
            });

            $('#data-laporan-pembayaran').on('click', '.btnPrintStruk', function() {
                var pembayaranId = $(this).data('id');
                printStruk(pembayaranId);
            });

            function printStruk(pembayaranId) {
                // Hapus iframe lama jika ada untuk menghindari duplikasi
                $('#print-iframe').remove();

                var url = "{{ url('/pembayaran/struk') }}/" + pembayaranId;

                // Buat iframe baru yang tersembunyi
                var iframe = $('<iframe id="print-iframe" src="' + url + '" style="display: none;"></iframe>');

                // Tambahkan iframe ke body
                $('body').append(iframe);

                // JavaScript di dalam struk.blade.php akan menangani pemanggilan window.print()
                // Kita hanya perlu menghapus iframe setelah beberapa saat.
                iframe.on('load', function() {
                    setTimeout(function() {
                        iframe.remove();
                    }, 2000); // Hapus setelah 2 detik
                });
            }

        });
    </script>
@endpush
