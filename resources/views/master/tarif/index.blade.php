@extends('layouts.master')

@section('title', 'Master Tarif')

@push('styles')
    <style>
        .modal-body {
            max-height: calc(100vh - 210px);
            /* Sesuaikan dengan tinggi header dan footer modal */
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 1.25rem;
            /* Spasi antar form group sedikit lebih besar */
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

        .section-divider {
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            border: 0;
            border-top: 1px solid rgba(0, 0, 0, .1);
        }

        .modal-header .close {
            padding: 1rem 1rem;
            margin: -1rem -1rem -1rem auto;
            color: #fff;
            /* Warna ikon close di header primary */
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
                <h3 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-invoice-dollar mr-2"></i>Data Tarif</h3>
                <button class="btn btn-success btn-icon-split" type="button" id="tombolTambahTarif">
                    <span class="icon text-white-50">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">Tambah Tarif</span>
                </button>
            </div>
            <div class="card-body">
                <table id="data-tarif"
                    class="table table-bordered table-striped table-hover display compact responsive table-sm"
                    style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Tarif</th>
                            <th>Nama Tarif</th>
                            <th>Wilayah</th>
                            <th>Golongan</th>
                            <th class="text-right">Abonemen</th>
                            <th class="text-right">Tarif/M³</th>
                            <th class="text-center">Status</th>
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

    <div class="modal fade" id="tarifModal" tabindex="-1" role="dialog" aria-labelledby="tarifModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tarifModalLabel">Form Tarif</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="tarifForm">
                        @csrf
                        <input type="hidden" id="tarif_id" name="tarif_id">

                        {{-- Area untuk menampilkan Kode Tarif saat edit --}}
                        <div id="display_kode_tarif_area" class="mb-3" style="display:none;">
                            <strong>Kode Tarif:</strong> <span id="display_kode_tarif" class="badge badge-info"></span>
                        </div>

                        <div class="form-group">
                            <label for="nama_tarif">Nama Tarif <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_tarif" name="nama_tarif"
                                placeholder="Misal: Rumah Tangga A1 Subsidi">
                            <div class="invalid-feedback" id="nama_tarif_error"></div>
                        </div>

                        <hr class="section-divider">
                        <h6 class="font-weight-bold text-secondary mb-3">Detail Penetapan</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="wilayah_id">Wilayah <span class="text-danger">*</span></label>
                                    <select class="form-control" id="wilayah_id" name="wilayah_id">
                                        <option value="">-- Pilih Wilayah --</option>
                                        @foreach ($wilayahs as $wilayah)
                                            <option value="{{ $wilayah->wilayah_id }}">{{ $wilayah->nama_wilayah }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="wilayah_id_error"></div>
                                </div>
                            </div>
                            {{-- <div class="col-md-6">
                                <div class="form-group">
                                    <label for="golongan_id">Golongan Tarif <span class="text-danger">*</span></label>
                                    <select class="form-control" id="golongan_id" name="golongan_id">
                                        <option value="">-- Pilih Golongan Tarif --</option>
                                        @foreach ($golongan_tarifs as $golongan)
                                            <option value="{{ $golongan->golongan_id }}">{{ $golongan->nama_golongan }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="golongan_id_error"></div>
                                </div>
                            </div> --}}
                            <input type="hidden" name="golongan_id" value="">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="abonemen">Biaya Abonemen (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="abonemen" name="abonemen" step="100"
                                        min="0" placeholder="Contoh: 25000">
                                    <div class="invalid-feedback" id="abonemen_error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tarif_per_m3">Tarif / M³ (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="tarif_per_m3" name="tarif_per_m3"
                                        step="50" min="0" placeholder="Contoh: 3500">
                                    <div class="invalid-feedback" id="tarif_per_m3_error"></div>
                                </div>
                            </div>
                        </div>

                        <hr class="section-divider">
                        <h6 class="font-weight-bold text-secondary mb-3">Periode Keberlakuan & Status</h6>

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
                                    <input type="date" class="form-control" id="berlaku_sampai"
                                        name="berlaku_sampai">
                                    <small class="form-text text-muted">Kosongkan jika berlaku selamanya.</small>
                                    <div class="invalid-feedback" id="berlaku_sampai_error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status" id="status_aktif"
                                        value="Aktif" checked>
                                    <label class="form-check-label" for="status_aktif">Aktif</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status"
                                        id="status_tidak_aktif" value="TidakAktif">
                                    <label class="form-check-label" for="status_tidak_aktif">Tidak Aktif</label>
                                </div>
                            </div>
                            <div class="invalid-feedback" id="status_error"></div>
                        </div>

                        <hr class="section-divider">
                        <div class="form-group">
                            <label for="deskripsi_tarif_form">Deskripsi Tambahan</label>
                            <textarea class="form-control" id="deskripsi_tarif_form" name="deskripsi" rows="2"
                                placeholder="Keterangan tambahan mengenai tarif ini"></textarea>
                            <div class="invalid-feedback" id="deskripsi_error"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal"><i
                            class="fas fa-times mr-1"></i>Tutup</button>
                    <button type="button" class="btn btn-primary" id="simpanTarif"><i
                            class="fas fa-save mr-1"></i>Simpan Tarif</button>
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
            } else if ($('#tarifForm input[name="_token"]').length > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('#tarifForm input[name="_token"]').val()
                    }
                });
            }

            var tableTarif = $('#data-tarif').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('tarif.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'kode_tarif',
                        name: 't.kode_tarif'
                    },
                    {
                        data: 'nama_tarif',
                        name: 't.nama_tarif'
                    },
                    {
                        data: 'nama_wilayah',
                        name: 'w.nama_wilayah'
                    },
                    {
                        data: 'nama_golongan',
                        name: 'gt.nama_golongan'
                    },
                    {
                        data: 'abonemen',
                        name: 't.abonemen',
                        className: 'text-right'
                    },
                    {
                        data: 'tarif_per_m3',
                        name: 't.tarif_per_m3',
                        className: 'text-right'
                    },
                    {
                        data: 'status',
                        name: 't.status',
                        className: 'text-center'
                    },
                    {
                        data: 'berlaku_mulai',
                        name: 'berlaku_mulai_formatted'
                    }, // Gunakan alias dari controller
                    {
                        data: 'berlaku_sampai',
                        name: 'berlaku_sampai_formatted'
                    }, // Gunakan alias dari controller
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
                    /* ... Opsi bahasa Indonesia ... */
                } // Salin dari contoh sebelumnya jika perlu
            });

            function clearFormErrorsTarif() {
                $('#tarifForm .form-control').removeClass('is-invalid');
                $('#tarifForm .invalid-feedback').text('').hide();
                $('#display_kode_tarif_area').hide(); // Sembunyikan area kode tarif
            }

            $('#tombolTambahTarif').click(function() {
                $('#tarifModalLabel').text('Tambah Tarif Baru');
                $('#tarifForm')[0].reset();
                $('#tarif_id').val('');
                $('input[name="status"][value="Aktif"]').prop('checked', true);
                clearFormErrorsTarif();
                $('#tarifModal').modal('show');
            });

            $('#simpanTarif').click(function() {
                clearFormErrorsTarif();
                var formData = $('#tarifForm').serialize();
                var tarifId = $('#tarif_id').val();
                var ajaxUrl = tarifId ? "{{ url('/master/tarif/update') }}/" + tarifId :
                    "{{ route('tarif.store') }}";
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
                                    timer: 2000
                                })
                                .then(() => {
                                    $('#tarifModal').modal('hide');
                                    tableTarif.ajax.reload(null, false);
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

            $('#data-tarif tbody').on('click', '.editButton', function() {
                var id = $(this).data('id');
                clearFormErrorsTarif();

                $.ajax({
                    url: "{{ url('/master/tarif/edit') }}/" + id,
                    type: 'GET',
                    success: function(data) {
                        if (data && data.tarif_id) {
                            $('#tarifModalLabel').text('Edit Tarif: ' + data.kode_tarif);
                            $('#tarif_id').val(data.tarif_id);

                            // Tampilkan kode tarif (read-only)
                            $('#display_kode_tarif').text(data.kode_tarif);
                            $('#display_kode_tarif_area').show();
                            // $('#kode_tarif').val(data.kode_tarif); // Jika kode tarif inputnya ada dan mau diisi (tapi kita hapus)

                            $('#nama_tarif').val(data.nama_tarif);
                            $('#wilayah_id').val(data.wilayah_id);
                            $('#golongan_id').val(data.golongan_id);
                            $('#abonemen').val(data.abonemen);
                            $('#tarif_per_m3').val(data.tarif_per_m3);
                            $('input[name="status"][value="' + data.status + '"]').prop(
                                'checked', true);
                            $('#deskripsi_tarif_form').val(data.deskripsi); // ID disesuaikan
                            $('#berlaku_mulai').val(data.berlaku_mulai);
                            $('#berlaku_sampai').val(data.berlaku_sampai);
                            $('#tarifModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: (data.message || 'Data tarif tidak ditemukan.')
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

            $('#data-tarif tbody').on('click', '.deleteButton', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data tarif ini akan dihapus!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6', // Tukar warna tombol
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
                            url: "{{ url('/master/tarif/delete') }}/" + id,
                            type: 'DELETE',
                            success: function(response) {
                                Swal.close();
                                if (response.success) {
                                    Swal.fire('Dihapus!', response.message, 'success');
                                    tableTarif.ajax.reload(null, false);
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
