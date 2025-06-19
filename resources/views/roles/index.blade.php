@extends('layouts.master')

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h3 class="m-0 font-weight-bold text-primary">Data Role</h3>
                <button class="btn btn-outline-success" type="button" id="tombolTambahRole">
                    <i class="fa fa-plus-square"></i> Tambah Role
                </button>
            </div>
            <div class="card-body">
                <table id="data-role" class="table table-striped table-hover display compact responsive table-sm"
                    style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Role</th>
                            <th>Dibuat Tanggal</th>
                            <th>Diupdate Tanggal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data User akan dimuat dari server-side -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal untuk Tambah/Edit Role -->
    <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="roleForm">
                        <input type="hidden" id="roleId" name="roleId">
                        <div class="form-group">
                            <label for="roleName">Nama Role</label>
                            <input type="text" class="form-control" id="roleName" name="roleName" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary" id="simpanRole">Simpan</button>
                            <button type="submit" class="btn btn-primary" id="updateRole">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#data-role').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/role-data',
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'nm_roles',
                        name: 'nm_roles'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'updated_at',
                        name: 'updated_at'
                    },
                    {
                        data: 'id',
                        render: function(data, type, row, meta) {
                            return `
                        <button class="editButton btn btn-sm btn-warning" data-id="${data}">Edit</button>
                        <button class="deleteButton btn btn-sm btn-danger" data-id="${data}">Delete</button>
                    `;
                        }
                    }
                ]
            });

            $('#tombolTambahRole').click(function() {
                $('#roleModal').modal('show');
                $('#roleModalLabel').text('Tambah Role Baru');
                $('#roleForm')[0].reset();
                $('#simpanRole').show();
                $('#updateRole').hide();
            });

            $('#roleForm').submit(function(e) {
                e.preventDefault();
                var id = $('#roleId').val();
                var name = $('#roleName').val();
                var url = id ? '/roles/update/' + id : '/roles/store';
                var method = id ? 'PUT' : 'POST';

                // Tampilkan loader
                Swal.fire({
                    title: 'Sedang menyimpan...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Menonaktifkan tombol simpan untuk mencegah klik berulang
                $(this).prop('disabled', true);

                $.ajax({
                    url: url,
                    type: method,
                    data: {
                        name: name,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses',
                                text: 'Role berhasil disimpan.',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                $('#roleModal').modal('hide');
                                $('#data-role').DataTable().ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.error
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi kesalahan',
                            text: 'Tidak dapat menyimpan data: ' + xhr.statusText
                        });
                    },
                    complete: function() {
                        // Aktifkan kembali tombol simpan setelah selesai
                        $('#simpanRole').prop('disabled', false);
                    }
                });
            });

            $('#data-role').on('click', '.deleteButton', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Anda tidak akan dapat mengembalikan ini!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/roles/delete/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        'Terhapus!',
                                        'Role telah dihapus.',
                                        'success'
                                    ).then(() => {
                                        $('#data-role').DataTable().ajax
                                            .reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Gagal!',
                                        'Role tidak dapat dihapus.',
                                        'error'
                                    );
                                }
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    'Gagal!',
                                    'Terjadi kesalahan saat menghapus role.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            $('#data-role').on('click', '.editButton', function() {
                var id = $(this).data('id');
                $.ajax({
                    url: '/roles/' + id,
                    type: 'GET',
                    success: function(response) {
                        // Setel nilai modal dengan detail role
                        $('#roleId').val(response.id);
                        $('#roleName').val(response.nm_roles);
                        $('#roleModalLabel').text('Edit Role');
                        $('#simpanRole').hide();
                        $('#updateRole').show();
                        $('#roleModal').modal('show');
                    },
                    error: function(xhr) {
                        console.log(xhr);
                        alert('Error mengambil data role');
                    }
                });
            });
        });
    </script>
@endpush
