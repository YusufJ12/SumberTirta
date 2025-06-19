@extends('layouts.master') {{-- Sesuaikan dengan layout master Anda --}}

@section('title', 'Master Wilayah') {{-- Judul halaman jika layout Anda mendukungnya --}}

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h3 class="m-0 font-weight-bold text-primary">Data Wilayah</h3>
                <button class="btn btn-outline-success" type="button" id="tombolTambahWilayah">
                    <i class="fa fa-plus-square"></i> Tambah Wilayah
                </button>
            </div>
            <div class="card-body">
                <table id="data-wilayah" class="table table-striped table-hover display compact responsive table-sm"
                    style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Wilayah</th>
                            <th>Keterangan</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="wilayahModal" tabindex="-1" role="dialog" aria-labelledby="wilayahModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="wilayahModalLabel">Form Wilayah</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="wilayahForm">
                        @csrf {{-- CSRF Token untuk keamanan --}}
                        <input type="hidden" id="wilayah_id" name="wilayah_id">
                        <div class="form-group">
                            <label for="nama_wilayah">Nama Wilayah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_wilayah" name="nama_wilayah" required>
                            <div class="invalid-feedback" id="nama_wilayah_error"></div>
                        </div>
                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                            <div class="invalid-feedback" id="keterangan_error"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="simpanWilayah">Simpan</button>
                    {{-- Tombol update akan di-handle oleh tombol simpan dengan logika berbeda di JS --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Pastikan layout Anda punya @stack('scripts') di bagian bawah sebelum </body> --}}
    <script>
        $(document).ready(function() {
            // Setup CSRF Token untuk semua request AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                        'content') // Jika Anda punya meta tag csrf-token di layout
                    // atau ambil dari form jika ada
                }
            });
            if ($('meta[name="csrf-token"]').length === 0 && $('#wilayahForm input[name="_token"]').length > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('#wilayahForm input[name="_token"]').val()
                    }
                });
            }


            var table = $('#data-wilayah').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('wilayah.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    }, // Nomor urut
                    {
                        data: 'nama_wilayah',
                        name: 'nama_wilayah'
                    },
                    {
                        data: 'keterangan',
                        name: 'keterangan',
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

            // Fungsi untuk membersihkan error validasi pada form
            function clearFormErrors() {
                $('#nama_wilayah_error').text('').hide();
                $('#keterangan_error').text('').hide();
                $('#nama_wilayah').removeClass('is-invalid');
                $('#keterangan').removeClass('is-invalid');
            }

            // Tombol Tambah Wilayah
            $('#tombolTambahWilayah').click(function() {
                $('#wilayahModalLabel').text('Tambah Wilayah Baru');
                $('#wilayahForm')[0].reset();
                $('#wilayah_id').val(''); // Pastikan ID kosong untuk mode tambah
                clearFormErrors();
                $('#wilayahModal').modal('show');
            });

            // Tombol Simpan/Update di Modal
            $('#simpanWilayah').click(function() {
                clearFormErrors();
                var formData = $('#wilayahForm').serialize(); // Mengambil semua data form
                var wilayahId = $('#wilayah_id').val();
                var ajaxUrl = wilayahId ? "{{ url('/master/wilayah/update') }}/" + wilayahId :
                    "{{ route('wilayah.store') }}";
                var ajaxType = wilayahId ? "POST" :
                "POST"; // Bisa juga "PUT" untuk update jika route di set demikian & method spoofing

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
                                $('#wilayahModal').modal('hide');
                                table.ajax.reload(); // Reload DataTables
                            });
                        } else {
                            // Tangani jika ada pesan error spesifik dari server (bukan validasi)
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message ||
                                    'Terjadi kesalahan yang tidak diketahui.'
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.close();
                        if (xhr.status === 422) { // Error validasi
                            var errors = xhr.responseJSON.errors;
                            if (errors.nama_wilayah) {
                                $('#nama_wilayah').addClass('is-invalid');
                                $('#nama_wilayah_error').text(errors.nama_wilayah[0]).show();
                            }
                            if (errors.keterangan) {
                                $('#keterangan').addClass('is-invalid');
                                $('#keterangan_error').text(errors.keterangan[0]).show();
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

            // Tombol Edit pada baris DataTables
            $('#data-wilayah tbody').on('click', '.editButton', function() {
                var id = $(this).data('id');
                clearFormErrors();
                $.ajax({
                    url: "{{ url('/master/wilayah/edit') }}/" + id,
                    type: 'GET',
                    success: function(data) {
                        if (data) { // Jika data wilayah ditemukan
                            $('#wilayahModalLabel').text('Edit Wilayah');
                            $('#wilayah_id').val(data.wilayah_id);
                            $('#nama_wilayah').val(data.nama_wilayah);
                            $('#keterangan').val(data.keterangan);
                            $('#wilayahModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: 'Data wilayah tidak ditemukan.'
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

            // Tombol Delete pada baris DataTables
            $('#data-wilayah tbody').on('click', '.deleteButton', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data wilayah ini akan dihapus secara permanen!",
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
                            url: "{{ url('/master/wilayah/delete') }}/" + id,
                            type: 'DELETE', // Pastikan method DELETE didukung oleh server atau gunakan POST dengan _method
                            // data: { _token: '{{ csrf_token() }}' }, // Sudah dihandle oleh $.ajaxSetup
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
