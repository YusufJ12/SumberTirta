@extends('layouts.master')
@section('title', 'Input Pencatatan Meter')

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
            border-left: 5px solid #4e73df;
            padding: 1.25rem;
            border-radius: .35rem;
        }

        .info-pelanggan-box dt {
            font-weight: bold;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #858796;
        }

        .info-pelanggan-box dd {
            margin-left: 0;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .form-section label {
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-edit mr-2"></i>Input Pencatatan Meter</h1>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Langkah 1: Cari & Pilih Pelanggan</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="pelanggan_search_select2">Cari Pelanggan (Nama)</label>
                    <select class="form-control" id="pelanggan_search_select2" style="width: 100%;"></select>
                    <small class="form-text text-muted">Mulai ketik untuk mencari pelanggan aktif yang pencatatannya belum
                        final.</small>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4" id="cardFormPencatatan" style="display: none;">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-keyboard mr-1"></i> Langkah 2: Input Detail
                    Pencatatan</h6>
            </div>
            <div class="card-body">
                <form id="formPencatatanMeter" onsubmit="return false;"> {{-- Cegah submit form tradisional --}}
                    @csrf
                    <input type="hidden" id="pelanggan_id_hidden" name="pelanggan_id">
                    <input type="hidden" id="periode_tahun_hidden" name="periode_tahun">
                    <input type="hidden" id="periode_bulan_hidden" name="periode_bulan">

                    {{-- Info Pelanggan & Periode --}}
                    <div class="info-pelanggan-box mb-4">
                        <h5 id="form_nama_pelanggan" class="font-weight-bold text-gray-800"></h5>
                        <dl class="row mb-0">
                            <dt class="col-sm-3">ID Pelanggan</dt>
                            <dd class="col-sm-9" id="info_id_pelanggan"></dd>
                            <dt class="col-sm-3">Alamat</dt>
                            <dd class="col-sm-9" id="info_alamat"></dd>
                            <dt class="col-sm-3">Periode Input</dt>
                            <dd class="col-sm-9 font-weight-bold text-success" id="info_periode"></dd>
                        </dl>
                    </div>

                    {{-- Form Input --}}
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Meter Awal (M³)</label>
                                <input type="number" class="form-control" id="meter_awal" name="meter_awal" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="meter_akhir">Meter Akhir (M³) <span class="text-danger">*</span></label>
                                <input type="number" step="1" class="form-control" id="meter_akhir"
                                    name="meter_akhir" placeholder="0">
                                <div class="invalid-feedback" id="meter_akhir_error"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Pemakaian (M³)</label>
                                <input type="text" class="form-control" id="pemakaian_display" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tanggal_catat">Tanggal Catat <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_catat" name="tanggal_catat"
                                    value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                                <div class="invalid-feedback" id="tanggal_catat_error"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <input type="text" class="form-control" id="keterangan" name="keterangan"
                            placeholder="Kondisi meter, dll. (Opsional)">
                        <div class="invalid-feedback" id="keterangan_error"></div>
                    </div>

                    <div class="text-right mt-4">
                        <button type="button" class="btn btn-secondary" id="btnBatalInput"><i
                                class="fas fa-times mr-1"></i>Batal</button>
                        <button type="button" class="btn btn-success btn-icon-split" id="btnSimpanPencatatan">
                            <span class="icon text-white-50"><i class="fas fa-save"></i></span>
                            <span class="text">Simpan Pencatatan</span>
                        </button>
                    </div>
                </form>
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

            $('#pelanggan_search_select2').select2({
                theme: 'bootstrap4',
                placeholder: 'Ketik untuk mencari pelanggan...',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('pencatatan_meter.search_pelanggan') }}",
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            term: params.term || ''
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }
                    var $container = $(
                        "<div>" +
                        "<div class='font-weight-bold'>" + data.nama_pelanggan + "</div>" +
                        "<div style='font-size:0.8rem;'>" +
                        "No. Meter: " + data.no_meter + " | Wilayah: " + data.nama_wilayah +
                        "</div>" +
                        "</div>"
                    );
                    return $container;
                },
                templateSelection: function(data) {
                    return data.text || 'Cari Pelanggan...';
                }
            });

            $('#pelanggan_search_select2').on('select2:select', function(e) {
                var pelangganId = e.params.data.id;
                $('#cardFormPencatatan').slideUp();

                $.ajax({
                    url: "{{ route('pencatatan_meter.get_detail') }}",
                    type: 'GET',
                    data: {
                        pelanggan_id: pelangganId
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Memuat Detail...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            var pelanggan = response.data;
                            $('#pelanggan_id_hidden').val(pelanggan.pelanggan_id);
                            $('#periode_tahun_hidden').val(pelanggan.periode_target_tahun);
                            $('#periode_bulan_hidden').val(pelanggan.periode_target_bulan);

                            $('#form_nama_pelanggan').text(pelanggan.nama_pelanggan);
                            $('#info_id_pelanggan').text(': ' + pelanggan.id_pelanggan_unik);
                            $('#info_alamat').text(': ' + pelanggan.alamat);
                            $('#info_periode').text(': ' + pelanggan.periode_target_formatted);

                            $('#meter_awal').val(parseFloat(pelanggan.meter_awal_final || 0)
                                .toFixed(0));
                            $('#meter_akhir').val('').focus();
                            $('#pemakaian_display').val('0');
                            $('#tanggal_catat').val(
                                '{{ Carbon\Carbon::now()->format('Y-m-d') }}');
                            $('#keterangan').val('');
                            $('#formPencatatanMeter .form-control').removeClass('is-invalid');
                            $('#formPencatatanMeter .invalid-feedback').text('');

                            $('#cardFormPencatatan').slideDown();
                        } else {
                            Swal.fire('Informasi', response.message ||
                                'Gagal memuat detail pelanggan.', 'info');
                            $('#pelanggan_search_select2').val(null).trigger('change');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                    }
                });
            });

            $('#btnBatalInput, #pelanggan_search_select2').on('select2:unselect', function(e) {
                $('#cardFormPencatatan').slideUp();
                $('#pelanggan_search_select2').val(null).trigger('change');
            });

            $('#meter_akhir').on('input change keyup', function() {
                var meterAwal = parseFloat($('#meter_awal').val()) || 0;
                var meterAkhir = parseFloat($(this).val()) || 0;
                var pemakaian = (meterAkhir >= meterAwal) ? meterAkhir - meterAwal : 0;
                $('#pemakaian_display').val(pemakaian.toFixed(0));
            });

            $('#btnSimpanPencatatan').click(function() {
                var btn = $(this);
                var formData = {
                    _token: $('input[name="_token"]').val(),
                    pelanggan_id: $('#pelanggan_id_hidden').val(),
                    periode_tahun: $('#periode_tahun_hidden').val(),
                    periode_bulan: $('#periode_bulan_hidden').val(),
                    meter_awal: $('#meter_awal').val(),
                    meter_akhir: $('#meter_akhir').val(),
                    tanggal_catat: $('#tanggal_catat').val(),
                    keterangan: $('#keterangan').val()
                };

                if (formData.meter_akhir === "" || formData.tanggal_catat === "") {
                    Swal.fire('Perhatian!', 'Meter Akhir dan Tanggal Catat wajib diisi.', 'warning');
                    return;
                }

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
                $('#formPencatatanMeter .form-control').removeClass('is-invalid');
                $('#formPencatatanMeter .invalid-feedback').text('');

                $.ajax({
                    url: "{{ route('pencatatan_meter.store_single') }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                    icon: 'success',
                                    title: 'Sukses!',
                                    text: response.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                })
                                .then(() => {
                                    $('#cardFormPencatatan').slideUp();
                                    $('#pelanggan_search_select2').val(null).trigger(
                                        'change');
                                });
                        } else {
                            Swal.fire('Gagal!', response.message || 'Gagal menyimpan.',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                $('#' + key + '_error').text(value[0]).show();
                                $('#' + key).addClass('is-invalid');
                            });
                        } else {
                            Swal.fire('Error!', (xhr.responseJSON && xhr.responseJSON
                                .message) || 'Terjadi kesalahan sistem.', 'error');
                        }
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<span class="icon text-white-50"><i class="fas fa-save"></i></span><span class="text">Simpan Pencatatan</span>'
                        );
                    }
                });
            });
        });
    </script>
@endpush
