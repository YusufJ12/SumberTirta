@extends('layouts.master') {{-- Sesuaikan dengan layout master Anda --}}

@section('title', 'Master Golongan Tarif')

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h3 class="m-0 font-weight-bold text-primary">Data Golongan Tarif</h3>
                <button class="btn btn-outline-success" type="button" id="tombolTambahGolonganTarif">
                    <i class="fa fa-plus-square"></i> Tambah Golongan Tarif
                </button>
            </div>
            <div class="card-body">
                <table id="data-golongan-tarif" class="table table-striped table-hover display compact responsive table-sm"
                    style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Golongan</th>
                            <th>Deskripsi</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="golonganTarifModal" tabindex="-1" role="dialog" aria-labelledby="golonganTarifModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="golonganTarifModalLabel">Form Golongan Tarif</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="golonganTarifForm">
                        @csrf
                        <input type="hidden" id="golongan_id" name="golongan_id">
                        <div class="form-group">
                            <label for="nama_golongan">Nama Golongan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_golongan" name="nama_golongan" required>
                            <div class="invalid-feedback" id="nama_golongan_error"></div>
                        </div>
                        <div class="form-group">
                            <label for="deskripsi">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                            <div class="invalid-feedback" id="deskripsi_error"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="simpanGolonganTarif">Simpan</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Setup CSRF Token untuk semua request AJAX
            if ($('meta[name="csrf-token"]').length > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            } else if ($('#golonganTarifForm input[name="_token"]').length >
                0) { // Fallback ke token di form jika meta tag tidak ada
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('#golonganTarifForm input[name="_token"]').val()
                    }
                });
            }

            var table = $('#data-golongan-tarif').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('golongan_tarif.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama_golongan',
                        name: 'nama_golongan'
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi',
                        orderable: false,
                        searchable: false,
                        defaultContent: '-'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            function clearFormErrorsGolonganTarif() {
                $('#nama_golongan_error').text('').hide();
                $('#deskripsi_error').text('').hide();
                $('#nama_golongan').removeClass('is-invalid');
                $('#deskripsi').removeClass('is-invalid');
            }

            $('#tombolTambahGolonganTarif').click(function() {
                $('#golonganTarifModalLabel').text('Tambah Golongan Tarif Baru');
                $('#golonganTarifForm')[0].reset();
                $('#golongan_id').val('');
                clearFormErrorsGolonganTarif();
                $('#golonganTarifModal').modal('show');
            });

            $('#simpanGolonganTarif').click(function() {
                clearFormErrorsGolonganTarif();
                var formData = $('#golonganTarifForm').serialize();
                var golonganId = $('#golongan_id').val();
                var ajaxUrl = golonganId ? "{{ url('/master/golongan-tarif/update') }}/" + golonganId :
                    "{{ route('golongan_tarif.store') }}";
                var ajaxType = "POST"; // Store dan Update kita handle dengan POST di route

                Swal.fire({
                    title: 'Sedang menyimpan...',
                    text: 'Mohon tunggu sebentar.',
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
                                title: 'Sukses',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                $('#golonganTarifModal').modal('hide');
                                table.ajax.reload();
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
                            if (errors.nama_golongan) {
                                $('#nama_golongan').addClass('is-invalid');
                                $('#nama_golongan_error').text(errors.nama_golongan[0]).show();
                            }
                            if (errors.deskripsi) {
                                $('#deskripsi').addClass('is-invalid');
                                $('#deskripsi_error').text(errors.deskripsi[0]).show();
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Terjadi Kesalahan!',
                                text: 'Tidak dapat menyimpan data. Status: ' + xhr
                                    .statusText
                            });
                        }
                    }
                });
            });

            $('#data-golongan-tarif tbody').on('click', '.editButton', function() {
                var id = $(this).data('id');
                clearFormErrorsGolonganTarif();
                $.ajax({
                    url: "{{ url('/master/golongan-tarif/edit') }}/" + id,
                    type: 'GET',
                    success: function(data) {
                        if (data && data.golongan_id) { // Cek apakah data valid
                            $('#golonganTarifModalLabel').text('Edit Golongan Tarif');
                            $('#golongan_id').val(data.golongan_id);
                            $('#nama_golongan').val(data.nama_golongan);
                            $('#deskripsi').val(data.deskripsi);
                            $('#golonganTarifModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: (data.message ||
                                    'Data golongan tarif tidak ditemukan.')
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

            $('#data-golongan-tarif tbody').on('click', '.deleteButton', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data golongan tarif ini akan dihapus!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Sedang menghapus...',
                            text: 'Mohon tunggu sebentar.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            url: "{{ url('/master/golongan-tarif/delete') }}/" + id,
                            type: 'DELETE',
                            // data: { _token: '{{ csrf_token() }}' }, // Sudah dihandle $.ajaxSetup
                            success: function(response) {
                                Swal.close();
                                if (response.success) {
                                    Swal.fire('Dihapus!', response.message, 'success');
                                    table.ajax.reload();
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
