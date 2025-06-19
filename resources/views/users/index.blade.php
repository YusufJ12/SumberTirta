@extends('layouts.master')

@section('title', 'Master Pengguna')

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h3 class="m-0 font-weight-bold text-primary"><i class="fas fa-users-cog mr-2"></i>Data Pengguna Sistem</h3>
                <button class="btn btn-success btn-icon-split" type="button" id="tombolTambahUser">
                    <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                    <span class="text">Tambah User</span>
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="data-user" class="table table-bordered table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th style="width:120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Form User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <form id="userForm" onsubmit="return false;">
                        @csrf
                        <input type="hidden" id="user_id" name="user_id">
                        <div class="form-group">
                            <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name">
                            <div class="invalid-feedback" id="name_error"></div>
                        </div>
                        <div class="form-group">
                            <label for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username"
                                placeholder="hanya huruf kecil, angka, _ . -">
                            <div class="invalid-feedback" id="username_error"></div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email">
                            <div class="invalid-feedback" id="email_error"></div>
                        </div>
                        <div class="form-group">
                            <label for="roles">Role <span class="text-danger">*</span></label>
                            <select class="form-control" id="roles" name="roles">
                                <option value="">-- Pilih Role --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->nm_roles }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="roles_error"></div>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small id="passwordHelp" class="form-text text-muted">Kosongkan jika tidak ingin mengubah
                                password saat edit.</small>
                            <div class="invalid-feedback" id="password_error"></div>
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="simpanUser">Simpan</button>
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

            var table = $('#data-user').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('users.data') }}", // Pastikan route ini ada
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'username',
                        name: 'username'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'nm_roles',
                        name: 'roles.nm_roles'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                language: {
                    /* Opsi bahasa Indonesia jika perlu */ }
            });

            function clearFormErrors() {
                $('#userForm .form-control').removeClass('is-invalid');
                $('#userForm .invalid-feedback').text('').hide();
            }

            $('#tombolTambahUser').click(function() {
                $('#user_id').val('');
                $('#userForm')[0].reset();
                $('#userModalLabel').text('Tambah User Baru');
                $('#password').prop('required', true);
                $('#password_confirmation').prop('required', true);
                $('#passwordHelp').hide();
                clearFormErrors();
                $('#userModal').modal('show');
            });

            $('#data-user').on('click', '.editButton', function() {
                var id = $(this).data('id');
                clearFormErrors();
                $.get('/user/edit/' + id, function(data) { // Pastikan route ini ada
                    $('#userModalLabel').text('Edit User');
                    $('#user_id').val(data.id);
                    $('#name').val(data.name);
                    $('#username').val(data.username);
                    $('#email').val(data.email);
                    $('#roles').val(data.type);
                    $('#password').prop('required', false);
                    $('#password_confirmation').prop('required', false);
                    $('#passwordHelp').show();
                    $('#userModal').modal('show');
                });
            });

            $('#simpanUser').click(function() {
                clearFormErrors();
                var formData = $('#userForm').serialize();
                var userId = $('#user_id').val();
                var ajaxUrl = userId ? '/user/update/' + userId : '/user/save'; // Pastikan route ini ada

                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
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
                                    $('#userModal').modal('hide');
                                    table.ajax.reload(null, false);
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
                        if (xhr.status === 422) { // Validation error
                            var errors = xhr.responseJSON.errors;
                            var errorMessage = '<ul>';
                            $.each(errors, function(key, value) {
                                $('#' + key + '_error').text(value[0]).show();
                                $('#' + key).addClass('is-invalid');
                                errorMessage += `<li>${value[0]}</li>`;
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
                                title: 'Error Sistem!',
                                text: 'Tidak dapat menyimpan data.'
                            });
                        }
                    }
                });
            });

            $('#data-user').on('click', '.deleteButton', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/user/delete/' + id, // Pastikan route ini ada
                            type: 'DELETE',
                            success: function(response) {
                                Swal.fire('Dihapus!', response.message, 'success');
                                table.ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                Swal.fire('Gagal!', (xhr.responseJSON && xhr
                                        .responseJSON.message) ||
                                    'Tidak dapat menghapus data.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
