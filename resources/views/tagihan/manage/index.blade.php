@extends('layouts.master')
@section('title', 'Manajemen Tagihan Pelanggan')

@push('styles')
    <style>
        .filter-card {
            background-color: #f8f9fc;
            border-radius: .35rem;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .modal-body {
            max-height: 75vh;
            overflow-y: auto;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-invoice-list mr-2"></i>Manajemen Tagihan Pelanggan</h1>
        <p class="mb-4">Daftar semua tagihan pelanggan. Gunakan filter untuk mencari tagihan spesifik.</p>

        <div class="card shadow mb-4 filter-card">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Filter Tagihan</h6>
            </div>
            <div class="card-body">
                <form id="filterTagihanForm" class="form-row align-items-end">
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_tahun">Tahun</label>
                        <select class="form-control form-control-sm" id="filter_tahun" name="filter_tahun">
                            <option value="">Semua</option>
                            @foreach ($tahuns as $tahun)
                                <option value="{{ $tahun }}"
                                    {{ $tahun == \Carbon\Carbon::now()->year ? 'selected' : '' }}>{{ $tahun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_bulan">Bulan</label>
                        <select class="form-control form-control-sm" id="filter_bulan" name="filter_bulan">
                            <option value="">Semua</option>
                            @foreach ($bulans as $num => $nama)
                                <option value="{{ $num }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_status">Status</label>
                        <select class="form-control form-control-sm" id="filter_status" name="filter_status">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_wilayah">Wilayah</label>
                        <select class="form-control form-control-sm" id="filter_wilayah" name="filter_wilayah">
                            <option value="">Semua Wilayah</option>
                            @foreach ($wilayahs as $wilayah)
                                <option value="{{ $wilayah->wilayah_id }}">{{ $wilayah->nama_wilayah }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-12 mb-2">
                        <label for="filter_pelanggan">Cari (ID/No.Meter/Nama)</label>
                        <input type="text" class="form-control form-control-sm" id="filter_pelanggan"
                            name="filter_pelanggan" placeholder="Masukkan kata kunci...">
                    </div>

                    <div class="col-auto mb-2">
                        <label>&nbsp;</label>
                        <div>
                            <button type="button" id="btnFilterTampilkan" class="btn btn-primary btn-sm">
                                <i class="fas fa-search fa-sm mr-1"></i> Tampilkan
                            </button>
                            <button type="button" id="btnFilterReset" class="btn btn-secondary btn-sm">
                                <i class="fas fa-undo fa-sm mr-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Tagihan</h6>
                {{-- Tombol Export bisa ditambahkan di sini jika perlu --}}
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="data-tagihan"
                        class="table table-bordered table-striped table-hover display compact responsive"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Tagihan</th>
                                <th>ID Pelanggan</th>
                                <th>Nama Pelanggan</th>
                                <th>Periode</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-right">Denda Saat Ini</th>
                                <th class="text-right">Total Tagihan</th>
                                <th class="text-center">Status</th>
                                <th style="width: 150px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailTagihanModal" tabindex="-1" role="dialog" aria-labelledby="detailTagihanModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="detailTagihanModalLabel">Detail Tagihan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <div id="detailTagihanContent">
                        <p class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Memuat detail...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i
                            class="fas fa-times mr-1"></i>Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="pembayaranModal" tabindex="-1" role="dialog" aria-labelledby="pembayaranModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="pembayaranModalLabel">Input Pembayaran</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <form id="formPembayaran" onsubmit="return false;">
                        @csrf
                        <input type="hidden" name="tagihan_id_hidden" id="tagihan_id_hidden">
                        <div class="alert alert-info">
                            Pembayaran untuk Tagihan: <strong id="info_tagihan_id"></strong><br>
                            Pelanggan: <strong id="info_nama_pelanggan"></strong> | Periode: <strong
                                id="info_periode"></strong>
                        </div>
                        <div class="form-section">
                            <h6 class="font-weight-bold text-primary">Perhitungan Denda & Total</h6>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="tanggal_bayar_submit">Tanggal Bayar <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_bayar_submit"
                                        name="tanggal_bayar_submit" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-6 d-flex align-items-end form-group">
                                    <button type="button" class="btn btn-sm btn-outline-info" id="btnHitungDenda"><i
                                            class="fas fa-calculator mr-1"></i> Hitung Ulang Denda</button>
                                </div>
                            </div>
                            <dl class="row">
                                <dt class="col-sm-5">Sub Total Tagihan</dt>
                                <dd class="col-sm-7" id="display_sub_total_rp">: Rp 0</dd>
                                <dt class="col-sm-5">Denda Terhitung</dt>
                                <dd class="col-sm-7" id="display_denda_calculated_rp">: Rp 0</dd>
                                <dt class="col-sm-5 h5 text-danger">Total Tagihan Akhir</dt>
                                <dd class="col-sm-7 h5 text-danger" id="display_total_akhir_rp">: Rp 0</dd>
                            </dl>
                            <input type="hidden" name="denda_final_submit" id="denda_final_submit" value="0">
                            <input type="hidden" name="total_akhir_submit" id="total_akhir_submit" value="0">
                        </div>
                        <div class="form-section">
                            <h6 class="font-weight-bold text-primary">Detail Pembayaran</h6>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="jumlah_bayar_submit">Jumlah Bayar (Rp) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="jumlah_bayar_submit"
                                        name="jumlah_bayar_submit" step="any" min="1">
                                    <div class="invalid-feedback" id="jumlah_bayar_submit_error"></div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="metode_pembayaran_submit">Metode Pembayaran <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="metode_pembayaran_submit"
                                        name="metode_pembayaran_submit">
                                        @foreach ($metode_pembayaran_list as $metode)
                                            <option value="{{ $metode }}">{{ $metode }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="referensi_pembayaran_submit">No. Referensi (Opsional)</label>
                                <input type="text" class="form-control" id="referensi_pembayaran_submit"
                                    name="referensi_pembayaran_submit">
                            </div>
                            <div class="form-group">
                                <label for="keterangan_pembayaran_submit">Keterangan (Opsional)</label>
                                <textarea class="form-control" id="keterangan_pembayaran_submit" name="keterangan_pembayaran_submit" rows="2"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="btnSimpanPembayaran"><i
                            class="fas fa-check-circle mr-1"></i> Proses Pembayaran</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var tableTagihan = $('#data-tagihan').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('tagihan.manage.data') }}",
                    type: "GET",
                    data: function(d) {
                        d.filter_tahun = $('#filter_tahun').val();
                        d.filter_bulan = $('#filter_bulan').val();
                        d.filter_status = $('#filter_status').val();
                        d.filter_wilayah = $('#filter_wilayah').val();
                        d.filter_pelanggan = $('#filter_pelanggan').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tagihan_id',
                        name: 'tg.tagihan_id'
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
                        data: 'periode_tagihan_bulan',
                        name: 'tg.periode_tagihan_bulan',
                        render: function(data, type, row) {
                            return row.periode_tagihan_bulan + ' ' + row.periode_tagihan_tahun;
                        }
                    },
                    {
                        data: 'tanggal_jatuh_tempo',
                        name: 'tg.tanggal_jatuh_tempo'
                    },
                    {
                        data: 'denda_sekarang',
                        name: 'denda_sekarang',
                        className: 'text-right text-danger',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_tagihan_sekarang',
                        name: 'total_tagihan_sekarang',
                        className: 'text-right font-weight-bold',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status_tagihan',
                        name: 'tg.status_tagihan',
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center action-buttons'
                    }
                ],
                order: [
                    [1, 'desc']
                ]
            });

            // 1. Pemicu dari Tombol "Tampilkan"
            $('#btnFilterTampilkan').click(function() {
                tableTagihan.draw();
            });

            // 2. Pemicu dari tombol Enter di kolom pencarian
            $('#filter_pelanggan').on('keyup', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    tableTagihan.draw();
                }
            });

            // 3. Pemicu dari tombol Reset
            $('#btnFilterReset').click(function() {
                $('#filterTagihanForm')[0].reset(); // Reset semua input
                // Kembalikan filter tahun ke tahun sekarang (opsional)
                $('#filter_tahun').val('{{ \Carbon\Carbon::now()->year }}');
                tableTagihan.draw(); // Refresh tabel ke kondisi awal
            });


            // --- LOGIKA UNTUK MODAL PEMBAYARAN ---

            $('#data-tagihan').on('click', '.bayarButton', function() {
                var tagihanId = $(this).data('id');

                $.ajax({
                    url: "{{ url('/tagihan/detail') }}/" +
                        tagihanId, // Menggunakan route detail tagihan
                    type: 'GET',
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Memuat Form...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            var tagihan = response.data;

                            $('#formPembayaran')[0].reset();
                            $('#formPembayaran .is-invalid').removeClass('is-invalid');

                            $('#tagihan_id_hidden').val(tagihan.tagihan_id);
                            $('#info_tagihan_id').text('#' + tagihan.tagihan_id);
                            $('#info_nama_pelanggan').text(tagihan.nama_pelanggan);
                            $('#info_periode').text(tagihan.periode_tagihan_formatted);
                            $('#display_sub_total_rp').text(': ' + tagihan
                                .sub_total_tagihan_rp);

                            $('#pembayaranModal').modal('show');

                            setTimeout(() => {
                                $('#btnHitungDenda').click();
                            }, 300);
                        } else {
                            Swal.fire('Error', response.message || 'Gagal memuat detail.',
                                'error');
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                    }
                });
            });

            $('#btnHitungDenda').click(function() {
                var btn = $(this);
                var tagihanId = $('#tagihan_id_hidden').val();
                var tanggalBayar = $('#tanggal_bayar_submit').val();
                if (!tagihanId || !tanggalBayar) return;

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menghitung...');

                $.ajax({
                    url: "{{ route('pembayaran.hitung_denda') }}",
                    type: 'POST',
                    data: {
                        tagihan_id: tagihanId,
                        tanggal_bayar: tanggalBayar
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#display_denda_calculated_rp').text(': ' + response
                                .denda_calculated_rp);
                            $('#display_total_akhir_rp').text(': ' + response
                                .total_dengan_denda_rp);
                            $('#denda_final_submit').val(response.denda_calculated);
                            $('#total_akhir_submit').val(response.total_dengan_denda);
                            $('#jumlah_bayar_submit').val(response.total_dengan_denda.toFixed(
                                0));
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghitung denda.', 'error');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-calculator mr-1"></i> Hitung Ulang Denda');
                    }
                });
            });

            $('#btnSimpanPembayaran').click(function() {
                var btn = $(this);
                var formData = $('#formPembayaran').serialize();

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

                $.ajax({
                    url: "{{ route('pembayaran.store') }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#pembayaranModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses!',
                                text: response.message,
                                showDenyButton: response.pembayaran_id_baru ? true :
                                    false,
                                denyButtonText: '<i class="fas fa-print"></i> Cetak Struk',
                                denyButtonColor: '#1cc88a',
                                focusDeny: true
                            }).then((result) => {
                                if (result.isDenied) {
                                    window.open("{{ url('/pembayaran/struk') }}/" +
                                        response.pembayaran_id_baru, '_blank');
                                }
                                tableTagihan.ajax.reload(null, false);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            var errorHtml = '<ul>';
                            $.each(errors, function(key, value) {
                                $('#' + key.replace('_submit', '_error')).text(value[0])
                                    .show();
                                $('#' + key.replace('_submit', '')).addClass(
                                    'is-invalid');
                                errorHtml += `<li>${value[0]}</li>`;
                            });
                            errorHtml += '</ul>';
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Validasi!',
                                html: errorHtml
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error Sistem!',
                                text: 'Tidak dapat menyimpan data.'
                            });
                        }
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-check-circle mr-1"></i> Proses Pembayaran');
                    }
                });
            });


            $('#data-tagihan tbody').on('click', '.detailButton', function() {
                var id = $(this).data('id');
                $('#detailTagihanContent').html(
                    '<p class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Memuat detail...</p>'
                );
                $('#detailTagihanModal').modal('show');

                $.ajax({
                    url: "{{ url('/tagihan/detail') }}/" + id,
                    type: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            var tagihan = response.data;

                            var html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-file-alt mr-2"></i>Informasi Tagihan</h6>
                            <dl class="row">
                                <dt class="col-sm-5">ID Tagihan</dt><dd class="col-sm-7">: ${tagihan.tagihan_id}</dd>
                                <dt class="col-sm-5">Periode</dt><dd class="col-sm-7">: ${tagihan.periode_tagihan_formatted}</dd>
                                <dt class="col-sm-5">Tgl Terbit</dt><dd class="col-sm-7">: ${tagihan.tanggal_terbit_formatted}</dd>
                                <dt class="col-sm-5">Jatuh Tempo</dt><dd class="col-sm-7">: ${tagihan.tanggal_jatuh_tempo_formatted}</dd>
                                <dt class="col-sm-5">Status</dt><dd class="col-sm-7">: <span class="badge badge-${tagihan.status_tagihan === 'Lunas' ? 'success' : (tagihan.status_tagihan === 'BelumLunas' ? 'danger' : (tagihan.status_tagihan === 'LunasSebagian' ? 'warning' : 'secondary'))}">${tagihan.status_tagihan}</span></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-user mr-2"></i>Informasi Pelanggan</h6>
                            <dl class="row">
                                <dt class="col-sm-5">ID Pelanggan</dt><dd class="col-sm-7">: ${tagihan.id_pelanggan_unik}</dd>
                                <dt class="col-sm-5">Nama</dt><dd class="col-sm-7">: ${tagihan.nama_pelanggan}</dd>
                                <dt class="col-sm-5">Alamat</dt><dd class="col-sm-7">: ${tagihan.alamat_pelanggan}</dd>
                                <dt class="col-sm-5">Wilayah</dt><dd class="col-sm-7">: ${tagihan.nama_wilayah}</dd>
                            </dl>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                             <h6><i class="fas fa-tint mr-2"></i>Detail Pemakaian</h6>
                             <dl class="row">
                                <dt class="col-sm-5">Meter Awal</dt><dd class="col-sm-7">: ${parseFloat(tagihan.meter_awal || 0).toFixed(0)} M³</dd>
                                <dt class="col-sm-5">Meter Akhir</dt><dd class="col-sm-7">: ${parseFloat(tagihan.meter_akhir || 0).toFixed(0)} M³</dd>
                                <dt class="col-sm-5">Volume Pakai</dt><dd class="col-sm-7">: ${tagihan.volume_pemakaian_saat_tagihan_formatted} M³</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-money-bill-wave mr-2"></i>Rincian Biaya</h6>
                             <dl class="row">
                                <dt class="col-sm-5">Biaya Pemakaian</dt><dd class="col-sm-7">: ${tagihan.biaya_pemakaian_rp}</dd>
                                <dt class="col-sm-5">Abonemen</dt><dd class="col-sm-7">: ${tagihan.abonemen_saat_tagihan_rp}</dd>
                                <dt class="col-sm-5 font-weight-bold">Sub Total</dt><dd class="col-sm-7 font-weight-bold">: ${tagihan.sub_total_tagihan_rp}</dd>
                                <dt class="col-sm-5">Denda Tercatat</dt><dd class="col-sm-7">: ${tagihan.denda_rp}</dd>
                                <dt class="col-sm-5 text-info">Denda Saat Ini</dt><dd class="col-sm-7 text-info">: ${tagihan.denda_sekarang_rp}</dd>
                                <dt class="col-sm-5 h5 text-danger">Total Tagihan Kini</dt><dd class="col-sm-7 h5 text-danger font-weight-bold">: ${tagihan.total_tagihan_sekarang_rp}</dd>
                            </dl>
                        </div>
                    </div>
                    `;
                            $('#detailTagihanContent').html(html);
                        } else {
                            $('#detailTagihanContent').html(
                                `<p class="text-center text-danger">${response.message}</p>`
                            );
                        }
                    },
                    error: function() {
                        $('#detailTagihanContent').html(
                            '<p class="text-center text-danger">Gagal memuat data dari server.</p>'
                        );
                    }
                });
            });

            function prosesPembatalanTagihan(id) {
                Swal.fire({
                    title: 'Anda Yakin?',
                    text: "Anda akan membatalkan Tagihan ID: " + id +
                        ". Tindakan ini tidak dapat diurungkan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Batalkan!',
                    cancelButtonText: 'Tidak',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: "{{ url('/tagihan/batalkan') }}/" + id,
                            type: 'POST',
                            // HAPUS PENGIRIMAN DATA ALASAN
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                        }).catch(error => {
                            var errorMsg = 'Gagal membatalkan tagihan.';
                            if (error.responseJSON && error.responseJSON.message) {
                                errorMsg = error.responseJSON.message;
                            }
                            Swal.showValidationMessage(`Request gagal: ${errorMsg}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed && result.value && result.value.success) {
                        Swal.fire('Dibatalkan!', result.value.message, 'success');
                        tableTagihan.ajax.reload(null,
                            false); // 'tableTagihan' adalah variabel datatables Anda
                        $('#detailTagihanModal').modal('hide'); // Tutup modal detail jika terbuka
                    } else if (result.isConfirmed && result.value && !result.value.success) {
                        Swal.fire('Gagal!', result.value.message || 'Tidak dapat membatalkan tagihan.',
                            'error');
                    }
                });
            }

            $('#data-tagihan tbody').on('click', '.cancelButton', function() {
                var id = $(this).data('id');
                prosesPembatalanTagihan(id);
            });

            // Tombol Cetak dari Modal Detail
            $('#btnPrintInvoiceFromModal').click(function() {
                var tagihanId = $('#detailTagihanIdForAction').val();
                if (tagihanId) {
                    var url = "{{ url('/tagihan/cetak') }}/" + tagihanId;
                    window.open(url, '_blank');
                } else {
                    Swal.fire('Error', 'ID Tagihan tidak ditemukan untuk dicetak.', 'error');
                }
            });




        });
    </script>
@endpush
