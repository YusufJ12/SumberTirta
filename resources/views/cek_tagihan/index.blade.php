@extends('layouts.master')
@section('title', 'Input Pembayaran Pelanggan')

@push('styles')
    <style>
        .select2-container .select2-selection--single {
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .75rem);
        }

        .info-pelanggan-box {
            background-color: #f8f9fc;
            border-left: 5px solid #1cc88a;
            padding: 1rem;
            border-radius: .35rem;
            margin-bottom: 1.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-search-dollar mr-2"></i>Input Pembayaran Pelanggan</h1>
        <p class="mb-4">Cari pelanggan untuk menampilkan semua riwayat tagihannya dan melakukan pembayaran.</p>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user mr-2"></i>Cari Pelanggan</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="pelanggan_search">Ketik Nama</label>
                    <select class="form-control" id="pelanggan_search" style="width: 100%;"></select>
                </div>
                <button class="btn btn-sm btn-secondary" id="btnReset" style="display: none;"><i
                        class="fas fa-times mr-1"></i> Reset Pencarian</button>
            </div>
        </div>

        <div class="card shadow mb-4" id="hasilCard" style="display: none;">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Tagihan untuk <span
                        id="nama_pelanggan_terpilih"></span></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="data-tagihan-pelanggan" class="table table-bordered table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Tagihan</th>
                                {{-- <th>ID Pelanggan</th> --}}
                                <th>Nama Pelanggan</th>
                                <th>Periode Catat</th>
                                <th>Periode Tagihan</th>
                                <th class="text-right">Meter Awal</th>
                                <th class="text-right">Meter Akhir</th>
                                <th class="text-right">Meter Penggunaan</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-right">Denda Kini</th>
                                <th class="text-right">Total Kini</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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

            // Inisialisasi Select2
            $('#pelanggan_search').select2({
                theme: 'bootstrap4',
                placeholder: 'Ketik untuk mencari pelanggan...',
                allowClear: true,
                minimumInputLength: 1,
                ajax: {
                    url: "{{ route('cek_tagihan.search_pelanggan') }}",
                    dataType: 'json',
                    delay: 300,
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                }
            });

            // Inisialisasi DataTables
            var table = $('#data-tagihan-pelanggan').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('cek_tagihan.get_data') }}",
                    type: "GET",
                    data: function(d) {
                        d.pelanggan_id = $('#pelanggan_search').val();
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
                        name: 'tg.tagihan_id',
                        width: '50px'
                    },
                    // {
                    //     data: 'id_pelanggan_unik',
                    //     name: 'p.id_pelanggan_unik'
                    // },
                    {
                        data: 'nama_pelanggan',
                        name: 'p.nama_pelanggan'
                    },
                    {
                        data: 'periode_tagihan_bulan',
                        name: 'tg.periode_tagihan_bulan',
                        render: (data, type, row) =>
                            `${moment(row.periode_tagihan_tahun + '-' + data + '-01').format("MMMM")} ${row.periode_tagihan_tahun}`
                    },
                    {
                        data: 'tanggal_terbit',
                        name: 'tg.tanggal_terbit',
                        render: function(data) {
                            return data ? moment(data).format('DD MMMM YYYY') : '-';
                        }
                    },
                    {
                        data: 'meter_awal',
                        name: 'tg.meter_awal',
                        className: 'text-right',
                        width: '60px',
                        render: $.fn.dataTable.render.number('.', ',', 0)
                    },
                    {
                        data: 'meter_akhir',
                        name: 'tg.meter_akhir',
                        className: 'text-right',
                        width: '60px',
                        render: $.fn.dataTable.render.number('.', ',', 0)
                    },
                    {
                        data: 'volume_pemakaian',
                        name: 'tg.volume_pemakaian',
                        className: 'text-right',
                        width: '80px',
                        render: $.fn.dataTable.render.number('.', ',', 0)
                    },
                    {
                        data: 'tanggal_jatuh_tempo',
                        name: 'tg.tanggal_jatuh_tempo',
                        render: (data) => moment(data).format('DD-MM-YYYY')
                    },
                    {
                        data: 'denda_sekarang',
                        name: 'denda_sekarang',
                        className: 'text-right text-danger',
                        orderable: false,
                        searchable: false,
                        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
                    },
                    {
                        data: 'total_tagihan_sekarang',
                        name: 'total_tagihan_sekarang',
                        className: 'text-right font-weight-bold',
                        orderable: false,
                        searchable: false,
                        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
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
                        className: 'text-center'
                    }
                ],
                initComplete: function() {
                    // Sembunyikan tabel saat pertama kali dimuat
                    if (!$('#pelanggan_search').val()) {
                        $(this.api().table().container()).hide();
                    }
                }
            });

            $('#pelanggan_search').on('select2:select', function(e) {
                var data = e.params.data;
                $('#nama_pelanggan_terpilih').text(data.text);
                $('#hasilCard').slideDown();
                $(table.table().container()).show();
                table.ajax.reload();
                $('#btnReset').show();
            });

            $('#btnReset').on('click', function() {
                $('#pelanggan_search').val(null).trigger('change');
                $('#hasilCard').slideUp();
                table.clear().draw();
                $(this).hide();
            });

            $('#data-tagihan-pelanggan').on('click', '.bayarButton', function() {
                var tagihanId = $(this).data('id');

                // Menggunakan route detail dari Manajemen Tagihan karena fungsinya sama
                $.ajax({
                    url: "{{ url('/tagihan/detail') }}/" + tagihanId,
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
                            $('.form-control').removeClass('is-invalid');

                            // Mengisi info di dalam modal
                            $('#tagihan_id_hidden').val(tagihan.tagihan_id);
                            $('#info_tagihan_id').text('#' + tagihan.tagihan_id);
                            $('#info_nama_pelanggan').text(tagihan.nama_pelanggan);
                            $('#info_periode').text(tagihan.periode_tagihan_formatted);
                            $('#display_sub_total_rp').text(': ' + tagihan
                                .sub_total_tagihan_rp);

                            // Menampilkan modal pembayaran
                            $('#pembayaranModal').modal('show');

                            // Otomatis hitung denda saat modal muncul
                            setTimeout(() => {
                                $('#btnHitungDenda').click();
                            }, 300);
                        } else {
                            Swal.fire('Error', response.message, 'error');
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
                                    printReceipt(response.pembayaran_id_baru);
                                }
                                $('#data-tagihan-pelanggan').DataTable().ajax.reload(
                                    null, false);
                            });
                        } else {
                            Swal.fire('Gagal!', response.message, 'error');
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

            function printReceipt(pembayaranId) {
                $('#print-iframe').remove();
                var url = "{{ url('/pembayaran/struk') }}/" + pembayaranId;
                var iframe = $('<iframe id="print-iframe" src="' + url + '" style="display: none;"></iframe>');
                $('body').append(iframe);
                iframe.on('load', function() {
                    setTimeout(function() {
                        iframe.remove();
                    }, 2000);
                });
            }
        });
    </script>
@endpush
