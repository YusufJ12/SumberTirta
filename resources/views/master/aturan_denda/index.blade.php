@extends('layouts.master')

@section('title', 'Master Aturan Denda')

@push('styles')
    <style>
        .modal-body {
            max-height: calc(100vh - 210px);
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-control.is-invalid {
            border-color: #e74a3b;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: .25rem;
            font-size: .875em;
            color: #e74a3b;
        }

        .modal-header .close {
            color: #fff;
            opacity: 0.75;
        }

        .modal-header .close:hover {
            opacity: 1;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h3 class="m-0 font-weight-bold text-primary"><i class="fas fa-exclamation-triangle mr-2"></i>Data Aturan
                    Denda</h3>
                <button class="btn btn-success btn-icon-split" type="button" id="tombolTambahAturanDenda">
                    <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                    <span class="text">Tambah Aturan Denda</span>
                </button>
            </div>
            <div class="card-body">
                <table id="data-aturan-denda"
                    class="table table-bordered table-striped table-hover display compact responsive table-sm"
                    style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Deskripsi</th>
                            <th>Keterlambatan (Bulan Ke-)</th>
                            <th class="text-right">Nominal Denda Tambah</th>
                            <th>Berlaku Mulai</th>
                            <th>Berlaku Sampai</th>
                            <th style="width: 120px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="aturanDendaModal" tabindex="-1" role="dialog" aria-labelledby="aturanDendaModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="aturanDendaModalLabel">Form Aturan Denda</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="aturanDendaForm">
                        @csrf
                        <input type="hidden" id="aturan_denda_id" name="aturan_denda_id">

                        <div class="form-group">
                            <label for="deskripsi">Deskripsi Aturan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="deskripsi" name="deskripsi"
                                placeholder="Misal: Denda keterlambatan pembayaran bulan pertama">
                            <div class="invalid-feedback" id="deskripsi_error"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="keterlambatan_bulan">Keterlambatan Bulan Ke- <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="keterlambatan_bulan"
                                        name="keterlambatan_bulan" min="0"
                                        placeholder="0 untuk denda di bulan berjalan, 1 untuk bulan berikutnya, dst.">
                                    <small class="form-text text-muted">0 = telat setelah jatuh tempo di bulan tagihan, 1 =
                                        telat 1 bulan penuh, dst.</small>
                                    <div class="invalid-feedback" id="keterlambatan_bulan_error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nominal_denda_tambah">Nominal Denda Tambahan (Rp) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="nominal_denda_tambah"
                                        name="nominal_denda_tambah" step="1000" min="0"
                                        placeholder="Contoh: 10000">
                                    <div class="invalid-feedback" id="nominal_denda_tambah_error"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="berlaku_mulai">Berlaku Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="berlaku_mulai" name="berlaku_mulai">
                                    <div class="invalid-feedback" id="berlaku_mulai_error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="berlaku_sampai">Berlaku Sampai</label>
                                    <input type="date" class="form-control" id="berlaku_sampai" name="berlaku_sampai">
                                    <small class="form-text text-muted">Kosongkan jika masih berlaku.</small>
                                    <div class="invalid-feedback" id="berlaku_sampai_error"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal"><i
                            class="fas fa-times mr-1"></i>Tutup</button>
                    <button type="button" class="btn btn-primary" id="simpanAturanDenda"><i
                            class="fas fa-save mr-1"></i>Simpan Aturan</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Setup CSRF Token
            if ($('meta[name="csrf-token"]').length > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            } else if ($('#aturanDendaForm input[name="_token"]').length > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('#aturanDendaForm input[name="_token"]').val()
                    }
                });
            }

            var tableAturanDenda = $('#data-aturan-denda').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('aturan_denda.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi'
                    },
                    {
                        data: 'keterlambatan_bulan',
                        name: 'keterlambatan_bulan',
                        className: 'text-center'
                    },
                    {
                        data: 'nominal_denda_tambah',
                        name: 'nominal_denda_tambah',
                        className: 'text-right'
                    },
                    {
                        data: 'berlaku_mulai',
                        name: 'berlaku_mulai_formatted'
                    },
                    {
                        data: 'berlaku_sampai',
                        name: 'berlaku_sampai_formatted'
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
                language: {
                    /* ... Opsi bahasa Indonesia ... */ }
            });

            function clearFormErrorsAturanDenda() {
                $('#aturanDendaForm .form-control').removeClass('is-invalid');
                $('#aturanDendaForm .invalid-feedback').text('').hide();
            }

            $('#tombolTambahAturanDenda').click(function() {
                $('#aturanDendaModalLabel').text('Tambah Aturan Denda Baru');
                $('#aturanDendaForm')[0].reset();
                $('#aturan_denda_id').val('');
                clearFormErrorsAturanDenda();
                $('#aturanDendaModal').modal('show');
            });

            $('#simpanAturanDenda').click(function() {
                clearFormErrorsAturanDenda();
                var formData = $('#aturanDendaForm').serialize();
                var aturanDendaId = $('#aturan_denda_id').val();
                var ajaxUrl = aturanDendaId ? "{{ url('/master/aturan-denda/update') }}/" + aturanDendaId :
                    "{{ route('aturan_denda.store') }}";
                var ajaxType = "POST";

                Swal.fire({
                    title: 'Sedang menyimpan...',
                    text: 'Mohon tunggu.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: ajaxUrl,
                    type: ajaxType,
                    data: formData,
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire({
                                    icon: 'success',
                                    title: 'Sukses!',
                                    text: response.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                })
                                .then(() => {
                                    $('#aturanDendaModal').modal('hide');
                                    tableAturanDenda.ajax.reload(null, false);
                                });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message || 'Terjadi kesalahan.'
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.close();
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            var errorMessage = 'Mohon periksa kembali input Anda:<ul>';
                            $.each(errors, function(key, value) {
                                $('#' + key + '_error').text(value[0]).show();
                                $('#' + key).addClass('is-invalid');
                                errorMessage += '<li>' + value[0] + '</li>';
                            });
                            errorMessage += '</ul>';
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Validasi!',
                                html: errorMessage
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Terjadi Kesalahan Sistem!',
                                text: 'Tidak dapat menyimpan data. (' + xhr.status +
                                    ': ' + (xhr.responseJSON && xhr.responseJSON
                                        .message ? xhr.responseJSON.message : xhr
                                        .statusText) + ')'
                            });
                        }
                    }
                });
            });

            $('#data-aturan-denda tbody').on('click', '.editButton', function() {
                var id = $(this).data('id');
                clearFormErrorsAturanDenda();
                $.ajax({
                    url: "{{ url('/master/aturan-denda/edit') }}/" + id,
                    type: 'GET',
                    success: function(data) {
                        if (data && data.aturan_denda_id) {
                            $('#aturanDendaModalLabel').text('Edit Aturan Denda');
                            $('#aturan_denda_id').val(data.aturan_denda_id);
                            $('#deskripsi').val(data.deskripsi);
                            $('#keterlambatan_bulan').val(data.keterlambatan_bulan);
                            $('#nominal_denda_tambah').val(data.nominal_denda_tambah);
                            $('#berlaku_mulai').val(data
                            .berlaku_mulai); // Format Y-m-d dari controller
                            $('#berlaku_sampai').val(data
                            .berlaku_sampai); // Format Y-m-d dari controller
                            $('#aturanDendaModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: (data.message ||
                                    'Data aturan denda tidak ditemukan.')
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengambil Data!',
                            text: 'Status: ' + xhr.statusText
                        });
                    }
                });
            });

            $('#data-aturan-denda tbody').on('click', '.deleteButton', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data aturan denda ini akan dihapus!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Sedang menghapus...',
                            text: 'Mohon tunggu.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            url: "{{ url('/master/aturan-denda/delete') }}/" + id,
                            type: 'DELETE',
                            success: function(response) {
                                Swal.close();
                                if (response.success) {
                                    Swal.fire('Dihapus!', response.message, 'success');
                                    tableAturanDenda.ajax.reload(null, false);
                                } else {
                                    Swal.fire('Gagal!', response.message ||
                                        'Data tidak dapat dihapus.', 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.close();
                                var errorMsg =
                                    'Terjadi kesalahan, data tidak dapat dihapus.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
