@extends('layouts.master')

@section('title', 'Master Pelanggan')

@push('styles')
    <style>
        .filter-card-pelanggan {
            background-color: #f8f9fc;
            border-radius: .35rem;
            margin-bottom: 1.5rem;
        }

        .filter-card-pelanggan .card-body {
            padding: 1rem 1.25rem;
        }

        .filter-card-pelanggan label {
            font-size: 0.8rem;
            margin-bottom: .25rem;
        }

        .action-buttons button,
        .action-buttons a {
            margin-right: 2px;
            margin-bottom: 2px;
        }

        .modal-body {
            max-height: 75vh;
            overflow-y: auto;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="modal fade" id="pelangganModal" tabindex="-1" role="dialog" aria-labelledby="pelangganModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="pelangganModalLabel">Form Pelanggan</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="pelangganForm">
                            @csrf
                            <input type="hidden" id="pelanggan_id" name="pelanggan_id">

                            <div id="display_otomatis_area" class="alert alert-info" style="display:none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>ID Pelanggan Unik:</strong> <span id="display_id_pelanggan_unik"
                                            class="font-weight-bold"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>No. Meter:</strong> <span id="display_no_meter"
                                            class="font-weight-bold"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="nama_pelanggan">Nama Pelanggan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="nama_pelanggan"
                                    name="nama_pelanggan" placeholder="Masukkan nama lengkap pelanggan">
                                <div class="invalid-feedback" id="nama_pelanggan_error"></div>
                            </div>
                            <div class="form-group">
                                <label for="alamat">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" id="alamat" name="alamat" rows="2"
                                    placeholder="Masukkan alamat lengkap"></textarea>
                                <div class="invalid-feedback" id="alamat_error"></div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="wilayah_id">Wilayah <span class="text-danger">*</span></label>
                                        <select class="form-control form-control-sm" id="wilayah_id" name="wilayah_id">
                                            <option value="">-- Pilih Wilayah --</option>
                                            @foreach ($wilayahs as $wilayah)
                                                <option value="{{ $wilayah->wilayah_id }}">{{ $wilayah->nama_wilayah }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="wilayah_id_error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tarif_id">Jenis Tarif <span class="text-danger">*</span></label>
                                        <select class="form-control form-control-sm" id="tarif_id" name="tarif_id">
                                            <option value="">-- Pilih Tarif --</option>
                                            @foreach ($tarifs as $tarif)
                                                <option value="{{ $tarif->tarif_id }}">{{ $tarif->kode_tarif }} -
                                                    {{ Str::limit($tarif->nama_tarif, 30) }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="tarif_id_error"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="status_pelanggan">Status Pelanggan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control form-control-sm" id="status_pelanggan"
                                            name="status_pelanggan">
                                            <option value="Baru">Baru</option>
                                            <option value="Aktif">Aktif</option>
                                            <option value="NonAktif">NonAktif</option>
                                            <option value="PemutusanSementara">Pemutusan Sementara</option>
                                        </select>
                                        <div class="invalid-feedback" id="status_pelanggan_error"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="tanggal_registrasi">Tgl. Registrasi</label>
                                        <input type="date" class="form-control form-control-sm" id="tanggal_registrasi"
                                            name="tanggal_registrasi">
                                        <div class="invalid-feedback" id="tanggal_registrasi_error"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="meter_awal_saat_pemasangan">Meter Awal <span
                                                class="text-danger">*</span></label>
                                        <input type="number" step="1" class="form-control form-control-sm"
                                            id="meter_awal_saat_pemasangan" name="meter_awal_saat_pemasangan"
                                            value="0">
                                        <div class="invalid-feedback" id="meter_awal_saat_pemasangan_error"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email_kontak">Email Kontak</label>
                                        <input type="email" class="form-control form-control-sm" id="email_kontak"
                                            name="email_kontak" placeholder="email@example.com">
                                        <div class="invalid-feedback" id="email_kontak_error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="no_telepon">No. Telepon</label>
                                        <input type="text" class="form-control form-control-sm" id="no_telepon"
                                            name="no_telepon" placeholder="08123456789">
                                        <div class="invalid-feedback" id="no_telepon_error"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="keterangan_pelanggan_form">Keterangan</label>
                                <textarea class="form-control form-control-sm" id="keterangan_pelanggan_form" name="keterangan" rows="2"></textarea>
                                <div class="invalid-feedback" id="keterangan_error"></div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-dismiss="modal"><i
                                class="fas fa-times mr-1"></i>Tutup</button>
                        <button type="button" class="btn btn-primary" id="simpanPelanggan"><i
                                class="fas fa-save mr-1"></i>Simpan</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Judul Halaman --}}
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-users mr-2"></i>Data Master Pelanggan</h1>
            <button class="btn btn-success btn-icon-split" type="button" id="tombolTambahPelanggan">
                <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                <span class="text">Tambah Pelanggan</span>
            </button>
        </div>

        <div class="card shadow mb-4 filter-card-pelanggan">
            <div class="card-body">
                <form id="filterPelangganForm" class="form-row">
                    <div class="col-md-3 mb-2">
                        <label for="filter_wilayah_id">Filter Wilayah</label>
                        <select class="form-control form-control-sm" id="filter_wilayah_id" name="filter_wilayah_id">
                            <option value="">Semua Wilayah</option>
                            @foreach ($wilayahs as $wilayah)
                                <option value="{{ $wilayah->wilayah_id }}">{{ $wilayah->nama_wilayah }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="filter_status_pelanggan">Filter Status</label>
                        <select class="form-control form-control-sm" id="filter_status_pelanggan"
                            name="filter_status_pelanggan">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label for="filter_search_pelanggan">Cari (ID Unik/No.Meter/Nama)</label>
                        <input type="text" class="form-control form-control-sm" id="filter_search_pelanggan"
                            name="filter_search_pelanggan" placeholder="Masukkan kata kunci...">
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="button" id="btnTerapkanFilterPelanggan"
                            class="btn btn-sm btn-primary btn-block"><i class="fas fa-filter fa-sm"></i> Terapkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Pelanggan</h6>
                <div>
                    <button class="btn btn-sm btn-success" id="btnExportExcelPelanggan"><i
                            class="fas fa-file-excel mr-1"></i> Excel</button>
                    <button class="btn btn-sm btn-danger" id="btnExportPdfPelanggan"><i class="fas fa-file-pdf mr-1"></i>
                        PDF</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="data-pelanggan"
                        class="table table-bordered table-striped table-hover display compact responsive table-sm"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Unik</th>
                                <th>No. Meter</th>
                                <th>Nama Pelanggan</th>
                                <th>Wilayah</th>
                                <th>Kode Tarif</th>
                                <th>Status</th>
                                <th>Alamat Singkat</th>
                                <th style="width: 120px;" class="text-center">Action</th>
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
            // ... ($.ajaxSetup seperti sebelumnya) ...
            if ($('meta[name="csrf-token"]').length > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            } else if ($('#pelangganForm input[name="_token"]').length > 0) { // Fallback
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('#pelangganForm input[name="_token"]').val()
                    }
                });
            }

            var tablePelanggan = $('#data-pelanggan').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('pelanggan.data') }}",
                    type: "GET",
                    data: function(d) { // Mengirim data filter
                        d.filter_wilayah_id = $('#filter_wilayah_id').val();
                        d.filter_status_pelanggan = $('#filter_status_pelanggan').val();
                        // Untuk search global DataTables, kita akan gunakan parameter search.value
                        // Jika ada filter search spesifik, kirim juga
                        d.search_value = d.search.value; // Kirim search global DataTables
                        if ($('#filter_search_pelanggan').val()) { // Jika filter search kustom diisi
                            d.search_value = $('#filter_search_pelanggan').val();
                            d.search = {
                                value: $('#filter_search_pelanggan').val(),
                                regex: false
                            }; // Timpa search global jika filter kustom diisi
                        }

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
                        data: 'id_pelanggan_unik',
                        name: 'p.id_pelanggan_unik'
                    },
                    {
                        data: 'no_meter',
                        name: 'p.no_meter'
                    },
                    {
                        data: 'nama_pelanggan',
                        name: 'p.nama_pelanggan'
                    },
                    {
                        data: 'nama_wilayah',
                        name: 'w.nama_wilayah'
                    },
                    {
                        data: 'kode_tarif',
                        name: 't.kode_tarif'
                    },
                    {
                        data: 'status_pelanggan',
                        name: 'p.status_pelanggan',
                        className: 'text-center'
                    },
                    {
                        data: 'alamat',
                        name: 'p.alamat',
                        orderable: false,
                        searchable: true,
                        render: function(data, type, row) {
                            return data && data.length > 30 ? data.substr(0, 30) + '...' : (data ||
                                '-');
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center action-buttons'
                    }
                ],
                responsive: true,
                language: {
                    /* ... Opsi bahasa Indonesia ... */
                },
                // Menonaktifkan search global bawaan DataTables jika kita ingin kontrol penuh via filter form
                // searching: false 
            });

            $('#btnTerapkanFilterPelanggan').click(function() {
                tablePelanggan.ajax.reload();
            });
            $('#filter_search_pelanggan').keypress(function(event) {
                if (event.which == 13) {
                    $('#btnTerapkanFilterPelanggan').click();
                }
            });


            function clearFormErrorsPelanggan() {
                $('#pelangganForm .form-control').removeClass('is-invalid');
                $('#pelangganForm .invalid-feedback').text('').hide();
                $('#generated_id_pelanggan_unik_display_area').hide();
            }

            $('#tombolTambahPelanggan').click(function() {
                $('#pelangganModalLabel').text('Tambah Pelanggan Baru');
                $('#pelangganForm')[0].reset();
                $('#pelanggan_id').val('');
                $('#status_pelanggan').val('Baru');
                $('#meter_awal_saat_pemasangan').val('0');
                clearFormErrorsPelanggan();
                $('#pelangganModal').modal('show');
            });

            $('#simpanPelanggan').click(function() {
                // ... (Logika simpan pelanggan via AJAX seperti di jawaban Master Pelanggan sebelumnya) ...
                // Pastikan ID pelanggan unik di-handle di controller
                // Dan form modal di-reset dengan benar, ID field untuk error juga benar
                clearFormErrorsPelanggan();
                var formData = $('#pelangganForm').serialize();
                var pelangganId = $('#pelanggan_id').val();
                var ajaxUrl = pelangganId ? "{{ url('/master/pelanggan/update') }}/" + pelangganId :
                    "{{ route('pelanggan.store') }}";
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
                                    $('#pelangganModal').modal('hide');
                                    tablePelanggan.ajax.reload(null, false);
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
                        /* ... (Error handling AJAX seperti sebelumnya) ... */
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

            $('#data-pelanggan tbody').on('click', '.editButton', function() {
                // ... (Logika edit pelanggan via AJAX seperti di jawaban Master Pelanggan sebelumnya) ...
                // Pastikan menampilkan ID pelanggan unik yang sudah digenerate
                var id = $(this).data('id');
                clearFormErrorsPelanggan();
                $('#generated_id_pelanggan_unik_display_area').show();

                $.ajax({
                    url: "{{ url('/master/pelanggan/edit') }}/" + id,
                    type: 'GET',
                    success: function(data) {
                        if (data && data.pelanggan_id) {
                            $('#pelangganModalLabel').text('Edit Pelanggan: ' + data
                                .nama_pelanggan);
                            $('#pelanggan_id').val(data.pelanggan_id);
                            $('#display_id_pelanggan_unik').text(data.id_pelanggan_unik);
                            // $('#id_pelanggan_unik').val(data.id_pelanggan_unik); // Input ID Unik di-disable atau tidak ada saat edit
                            $('#no_meter').val(data.no_meter);
                            $('#nama_pelanggan').val(data.nama_pelanggan);
                            $('#alamat').val(data.alamat);
                            $('#wilayah_id').val(data.wilayah_id);
                            $('#tarif_id').val(data.tarif_id);
                            $('#status_pelanggan').val(data.status_pelanggan);
                            $('#tanggal_registrasi').val(data.tanggal_registrasi);
                            $('#meter_awal_saat_pemasangan').val(data
                                .meter_awal_saat_pemasangan);
                            $('#tanggal_reset_meter_terakhir').val(data
                                .tanggal_reset_meter_terakhir);
                            $('#nilai_meter_saat_reset_terakhir').val(data
                                .nilai_meter_saat_reset_terakhir);
                            $('#email_kontak').val(data.email_kontak);
                            $('#no_telepon').val(data.no_telepon);
                            $('#keterangan_pelanggan_form').val(data
                                .keterangan); // ID textarea disesuaikan
                            $('#pelangganModal').modal('show');
                        } else {
                            /* ... error handling ... */
                        }
                    },
                    error: function(xhr) {
                        /* ... error handling ... */
                    }
                });
            });

            $('#data-pelanggan tbody').on('click', '.deleteButton', function() {
                // ... (Logika delete pelanggan via AJAX seperti di jawaban Master Pelanggan sebelumnya) ...
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data pelanggan ini akan dihapus!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        /* ... AJAX delete ... */
                        $.ajax({
                            url: "{{ url('/master/pelanggan/delete') }}/" + id,
                            type: 'DELETE',
                            success: function(response) {
                                /* ... handle success ... */
                                Swal.fire('Dihapus!', response.message, 'success');
                                tablePelanggan.ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                /* ... handle error ... */
                                var errorMsg = (xhr.responseJSON && xhr.responseJSON
                                        .message) ? xhr.responseJSON.message :
                                    'Gagal menghapus data.';
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                });
            });

            // Fungsi untuk membangun query string dari filter
            function buildPelangganFilterQueryString() {
                var params = {
                    filter_wilayah_id: $('#filter_wilayah_id').val(),
                    filter_status_pelanggan: $('#filter_status_pelanggan').val(),
                    filter_search_pelanggan: $('#filter_search_pelanggan')
                        .val() // Gunakan nama parameter yang konsisten
                };
                return $.param(params);
            }

            $('#btnExportExcelPelanggan').click(function(e) {
                e.preventDefault();
                var queryString = buildPelangganFilterQueryString();
                window.location.href = "{{ route('pelanggan.export_excel') }}?" + queryString;
            });

            $('#btnExportPdfPelanggan').click(function(e) {
                e.preventDefault();
                var queryString = buildPelangganFilterQueryString();
                window.open("{{ route('pelanggan.export_pdf') }}?" + queryString, '_blank');
            });

        });
    </script>
@endpush
