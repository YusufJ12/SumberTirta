@extends('layouts.master')

@section('title', 'Proses Pembuatan Tagihan')

@push('styles')
    <style>
        .preview-table th,
        .preview-table td {
            font-size: 0.85rem;
            padding: 0.5rem;
        }

        .table-responsive {
            margin-top: 1rem;
        }

        #btnProsesGenerateTagihan {
            margin-top: 1.5rem;
        }

        .filter-group label {
            font-size: 0.875rem;
            margin-bottom: .25rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-cogs mr-2"></i>Proses Pembuatan Tagihan Bulanan</h1>
        <p class="mb-4">Pilih periode (dan opsional pelanggan), tampilkan preview, lalu proses pembuatan tagihan.</p>

        {{-- Notifikasi --}}
        <div id="process-feedback"></div>


        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">1. Pilih Periode & Filter Pelanggan (Opsional)</h6>
            </div>
            <div class="card-body">
                <form id="formPeriodeTagihan"> {{-- Form ini akan disubmit oleh tombol Proses Generate via AJAX --}}
                    @csrf
                    <div class="row filter-group">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="periode_tahun_tagihan">Tahun Periode <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" id="periode_tahun_tagihan"
                                    name="periode_tahun_tagihan" required>
                                    @foreach ($tahuns as $tahun)
                                        <option value="{{ $tahun }}"
                                            {{ old('periode_tahun_tagihan', Carbon\Carbon::now()->year) == $tahun ? 'selected' : '' }}>
                                            {{ $tahun }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="periode_bulan_tagihan">Bulan Periode <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" id="periode_bulan_tagihan"
                                    name="periode_bulan_tagihan" required>
                                    @foreach ($bulans as $num => $nama)
                                        <option value="{{ $num }}"
                                            {{ old('periode_bulan_tagihan', Carbon\Carbon::now()->month) == $num ? 'selected' : '' }}>
                                            {{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="id_pelanggan_filter">Pelanggan (ID Unik/No.Meter/Nama - Opsional)</label>
                                <input type="text" class="form-control form-control-sm" id="id_pelanggan_filter"
                                    name="id_pelanggan_filter" placeholder="Kosongkan untuk semua pelanggan">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-group" style="width:100%;">
                                <button type="button" id="btnTampilkanPreview" class="btn btn-info btn-sm btn-block">
                                    <i class="fas fa-list-alt mr-1"></i> Tampilkan Preview
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4" id="cardPreviewTagihan" style="display: none;">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">2. Preview Pelanggan Akan Ditagih</h6>
            </div>
            <div class="card-body">
                <div id="infoJumlahPreview" class="alert alert-light" role="alert" style="display:none;"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped preview-table" id="tablePreview" width="100%">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>ID Pelanggan</th>
                                <th>Nama Pelanggan</th>
                                <th>No. Meter</th>
                                <th class="text-right">M.Awal</th>
                                <th class="text-right">M.Akhir</th>
                                <th class="text-right">Pemakaian (M³)</th>
                                <th class="text-right">Perkiraan Total</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPreview"></tbody>
                    </table>
                </div>
                <div class="text-right">
                    <button type="button" id="btnProsesGenerateTagihan" class="btn btn-success btn-icon-split"
                        style="display: none;">
                        <span class="icon text-white-50"><i class="fas fa-cog"></i></span>
                        <span class="text">Proses & Buat Tagihan Sesuai Preview</span>
                    </button>
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

            // Fungsi untuk menampilkan notifikasi umpan balik
            function showFeedback(type, message) {
                var alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' :
                    'alert-info');
                $('#process-feedback').html(
                    `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>`
                );
            }


            $('#btnTampilkanPreview').click(function() {
                var tahun = $('#periode_tahun_tagihan').val();
                var bulan = $('#periode_bulan_tagihan').val();
                var idPelangganFilter = $('#id_pelanggan_filter').val(); // Ambil nilai filter pelanggan
                var btn = $(this);

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memuat...');
                $('#cardPreviewTagihan').slideUp();
                $('#tbodyPreview').html('');
                $('#infoJumlahPreview').hide().removeClass('alert-success alert-warning alert-info').text(
                    '');
                $('#btnProsesGenerateTagihan').hide();
                $('#process-feedback').html(''); // Bersihkan feedback sebelumnya

                $.ajax({
                    url: "{{ route('pembuatan_tagihan.preview') }}",
                    type: 'POST',
                    data: {
                        periode_tahun_tagihan: tahun,
                        periode_bulan_tagihan: bulan,
                        id_pelanggan_filter: idPelangganFilter, // Kirim filter pelanggan
                        _token: $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            var dataPreview = response.data;
                            if (dataPreview.length > 0) {
                                $.each(dataPreview, function(index, item) {
                                    var row = `<tr>
                                <td class="text-center">${index + 1}</td>
                                <td>${item.id_pelanggan_unik}</td>
                                <td>${item.nama_pelanggan}</td>
                                <td>${item.no_meter}</td>
                                <td class="text-right">${item.meter_awal_formatted}</td>
                                <td class="text-right">${item.meter_akhir_formatted}</td>
                                <td class="text-right">${item.volume_pemakaian_formatted}</td>
                                <td class="text-right">${item.perkiraan_total_tagihan_rp}</td>
                            </tr>`;
                                    $('#tbodyPreview').append(row);
                                });
                                $('#infoJumlahPreview').addClass('alert-info').html(
                                    `Ditemukan <strong>${dataPreview.length}</strong> pelanggan yang siap ditagih untuk periode ${$('#periode_bulan_tagihan option:selected').text()} ${tahun} ${idPelangganFilter ? 'dengan filter pelanggan "' + idPelangganFilter + '"' : ''}.`
                                    ).show();
                                $('#btnProsesGenerateTagihan').show();
                            } else {
                                $('#infoJumlahPreview').addClass('alert-warning').text(response
                                    .message ||
                                    'Tidak ada data pelanggan yang siap ditagih.').show();
                            }
                            $('#cardPreviewTagihan').slideDown();
                        } else {
                            var errorMsg = 'Gagal memuat preview.';
                            if (response.errors) {
                                errorMsg = '';
                                $.each(response.errors, function(key, value) {
                                    errorMsg += value[0] + '<br>';
                                });
                                showFeedback('error',
                                    `<strong>Error Validasi!</strong><br>${errorMsg}`);
                            } else if (response.message) {
                                showFeedback('info', response.message);
                            } else {
                                showFeedback('error', errorMsg);
                            }
                        }
                    },
                    error: function(xhr) {
                        showFeedback('error',
                            'Terjadi kesalahan saat mengambil data preview. (' + xhr
                            .status + ': ' + xhr.statusText + ')');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-list-alt mr-1"></i> Tampilkan Preview');
                    }
                });
            });

            $('#btnProsesGenerateTagihan').click(function() {
                var tahun = $('#periode_tahun_tagihan').val();
                var bulan = $('#periode_bulan_tagihan').val();
                var idPelangganFilter = $('#id_pelanggan_filter')
            .val(); // Ambil filter pelanggan untuk dikirim ke proses generate
                var bulanText = $('#periode_bulan_tagihan option:selected').text();
                var btnGenerate = $(this);

                var confirmMessage =
                    `Anda akan membuat tagihan untuk pelanggan yang ditampilkan pada periode <strong>${bulanText} ${tahun}</strong>`;
                if (idPelangganFilter) {
                    confirmMessage += ` dengan filter pelanggan "<strong>${idPelangganFilter}</strong>"`;
                }
                confirmMessage += `. <br>Pastikan data sudah benar. Lanjutkan?`;


                Swal.fire({
                    title: 'Konfirmasi Proses',
                    html: confirmMessage,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Proses Sekarang!',
                    cancelButtonText: 'Batal',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        btnGenerate.prop('disabled', true).html(
                            '<i class="fas fa-spinner fa-spin"></i> Memproses...');
                        return $.ajax({
                                url: "{{ route('pembuatan_tagihan.generate') }}",
                                type: 'POST',
                                data: {
                                    periode_tahun_tagihan: tahun,
                                    periode_bulan_tagihan: bulan,
                                    id_pelanggan_filter: idPelangganFilter, // Kirim filter pelanggan
                                    _token: $('input[name="_token"]').val()
                                },
                            })
                            .catch(error => {
                                var errorMsg = 'Gagal memproses tagihan.';
                                if (error.responseJSON && error.responseJSON.message) {
                                    errorMsg = error.responseJSON.message;
                                } else if (error.responseJSON && error.responseJSON
                                    .errors) {
                                    errorMsg = '';
                                    $.each(error.responseJSON.errors, function(key, value) {
                                        errorMsg += value[0] + '<br>';
                                    });
                                } else if (error.statusText) {
                                    errorMsg = 'Error: ' + error.statusText;
                                }
                                Swal.showValidationMessage(`Request gagal: ${errorMsg}`);
                                btnGenerate.prop('disabled', false).html(
                                    '<span class="icon text-white-50"><i class="fas fa-cog"></i></span><span class="text">Proses & Buat Semua Tagihan Sekarang</span>'
                                    );
                            });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    btnGenerate.prop('disabled', false).html(
                        '<span class="icon text-white-50"><i class="fas fa-cog"></i></span><span class="text">Proses & Buat Semua Tagihan Sekarang</span>'
                        );
                    if (result.isConfirmed && result.value) {
                        if (result.value.success) {
                            showFeedback('success', result.value.message);
                            $('#cardPreviewTagihan').slideUp();
                            $('#tbodyPreview').html('');
                            $('#btnProsesGenerateTagihan').hide();
                        } else {
                            showFeedback('error', result.value.message ||
                                'Terjadi kesalahan saat proses.');
                        }
                    }
                });
            });
        });
    </script>
@endpush
