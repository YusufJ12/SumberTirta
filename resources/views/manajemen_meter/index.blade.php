@extends('layouts.master')
@section('title', 'Manajemen Ganti Meter')

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

        .form-section label {
            font-weight: 600;
        }

        .info-pelanggan-box {
            background-color: #f8f9fc;
            border-left: 5px solid #1cc88a;
            padding: 1rem;
            border-radius: .35rem;
            margin-bottom: 1.5rem;
        }

        .info-pelanggan-box h5 {
            margin-bottom: 0.75rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-sync-alt mr-2"></i>Manajemen Ganti / Reset Meter</h1>
        <p class="mb-4">Gunakan halaman ini untuk mencatat kejadian penggantian atau reset meteran pelanggan.</p>

        <div class="row">
            <div class="col-lg-10 col-xl-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Langkah 1: Cari & Pilih Pelanggan</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="pelanggan_search_select2">Cari Pelanggan Aktif (ID Unik/No.
                                Meter/Nama/Wilayah)</label>
                            <select class="form-control" id="pelanggan_search_select2" style="width: 100%;"></select>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4" id="cardFormGantiMeter" style="display: none;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary" id="formGantiMeterTitle">Langkah 2: Input Detail
                            Penggantian</h6>
                    </div>
                    <div class="card-body">
                        <div id="info-pelanggan-terpilih" class="info-pelanggan-box">
                            {{-- Info pelanggan akan dimuat oleh JavaScript di sini --}}
                        </div>
                        <form id="formGantiMeter" onsubmit="return false;">
                            @csrf
                            <input type="hidden" id="pelanggan_id_hidden" name="pelanggan_id">

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="tanggal_ganti">Tanggal & Waktu Ganti/Reset <span
                                            class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="tanggal_ganti"
                                        name="tanggal_ganti">
                                    <div class="invalid-feedback" id="tanggal_ganti_error"></div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="nilai_meter_baru">Nilai Awal Meter Baru <span
                                            class="text-danger">*</span></label>
                                    <input type="number" step="1" class="form-control" id="nilai_meter_baru"
                                        name="nilai_meter_baru" value="0">
                                    <div class="invalid-feedback" id="nilai_meter_baru_error"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="no_meter_baru">Nomor Seri Meter Baru (Opsional)</label>
                                    <input type="text" class="form-control" id="no_meter_baru" name="no_meter_baru"
                                        placeholder="Isi jika meter fisik diganti">
                                    <small class="form-text text-muted">Jika diisi, akan menggantikan No. Meter lama
                                        pelanggan.</small>
                                    <div class="invalid-feedback" id="no_meter_baru_error"></div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="alasan">Alasan Penggantian/Reset <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="alasan" name="alasan"
                                        placeholder="Contoh: Meter Rusak">
                                    <div class="invalid-feedback" id="alasan_error"></div>
                                </div>
                            </div>

                            <div class="text-right mt-3">
                                <button type="button" class="btn btn-secondary" id="btnBatal">Batal</button>
                                <button type="button" class="btn btn-success" id="btnSimpan">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
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

            $('#pelanggan_search_select2').select2({
                theme: 'bootstrap4',
                placeholder: 'Ketik untuk mencari pelanggan...',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('ganti_meter.search_pelanggan') }}",
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
                        "<div class='font-weight-bold'>" + data.nama_pelanggan + " (" + data
                        .id_pelanggan_unik + ")</div>" +
                        "<div style='font-size:0.8rem; color: #555;'>" +
                        "No. Meter: " + data.no_meter + " | Wilayah: " + data.nama_wilayah +
                        "</div>" +
                        "</div>"
                    );
                    return $container;
                },
                templateSelection: function(data) {
                    if (data.id) {
                        return data.nama_pelanggan + ' (' + data.no_meter + ')';
                    }
                    return 'Cari Pelanggan...';
                }
            });

            // Event saat pelanggan dipilih dari Select2
            $('#pelanggan_search_select2').on('select2:select', function(e) {
                var data = e.params.data;
                if (!data.id) return;

                $('#pelanggan_id_hidden').val(data.id);

                var infoHtml = `
            <h5 class="font-weight-bold text-gray-800">${data.nama_pelanggan}</h5>
            <p class="mb-0">
                <strong>ID Pelanggan:</strong> ${data.id_pelanggan_unik} | 
                <strong>No. Meter Saat Ini:</strong> ${data.no_meter} | 
                <strong>Wilayah:</strong> ${data.nama_wilayah}
            </p>
        `;
                $('#info-pelanggan-terpilih').html(infoHtml);

                var now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                $('#tanggal_ganti').val(now.toISOString().slice(0, 16));

                $('#cardFormGantiMeter').slideDown();
                $('#tanggal_ganti').focus();
            });

            $('#btnBatal').on('click', function() {
                $('#cardFormGantiMeter').slideUp();
                $('#pelanggan_search_select2').val(null).trigger('change');
                $('#formGantiMeter')[0].reset();
            });

            $('#pelanggan_search_select2').on('select2:unselect', function() {
                $('#cardFormGantiMeter').slideUp();
                $('#formGantiMeter')[0].reset();
            });
            $('#btnSimpan').click(function() {
                var btn = $(this);
                var formData = $('#formGantiMeter').serialize();

                // Reset validasi
                $('.form-control').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

                $.ajax({
                    url: "{{ route('ganti_meter.store') }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                    icon: 'success',
                                    title: 'Sukses!',
                                    text: response.message,
                                    showConfirmButton: false,
                                    timer: 2000
                                })
                                .then(() => {
                                    $('#cardFormGantiMeter').slideUp();
                                    $('#pelanggan_search_select2').val(null).trigger(
                                        'change');
                                    $('#formGantiMeter')[0].reset();
                                });
                        } else {
                            Swal.fire('Gagal!', response.message || 'Gagal menyimpan data.',
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
                        btn.prop('disabled', false).html('Simpan Perubahan');
                    }
                });
            });
        });
    </script>
@endpush
