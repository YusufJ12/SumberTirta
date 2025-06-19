@extends('layouts.master')

@section('title', 'Input Pembayaran Tagihan')

@push('styles')
    <style>
        /* Style untuk Select2 dan layout form */
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

        .billing-details dt {
            font-weight: bold;
        }

        .billing-details dd {
            margin-left: 0;
            margin-bottom: .75rem;
        }

        .form-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        /* Style kustom untuk hasil Select2 */
        .select2-result-repository__title {
            font-weight: bold;
        }

        .select2-result-repository__description {
            font-size: 0.8rem;
            color: #5a5c69;
        }

        .select2-result-repository__meta {
            font-size: 0.8rem;
            color: #858796;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-money-check-alt mr-2"></i>Input Pembayaran Tagihan</h1>
        </div>
        <p class="mb-4">Cari tagihan pelanggan yang belum lunas menggunakan No. Meter, Nama, atau Wilayah, lalu catat
            pembayaran.</p>

        <div class="row">
            {{-- Kolom Kiri: Pencarian dan Detail Tagihan --}}
            <div class="col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-search mr-1"></i> Cari Tagihan Belum
                            Lunas</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="tagihan_search_select2">Cari Pelanggan (Nama)</label>
                            <select class="form-control" id="tagihan_search_select2" name="tagihan_search_select2"
                                style="width: 100%;"></select>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4" id="cardDetailTagihan" style="display:none;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-file-invoice mr-1"></i> Detail
                            Tagihan Terpilih</h6>
                    </div>
                    <div class="card-body billing-details">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">ID Tagihan</dt>
                            <dd class="col-sm-7" id="detail_tagihan_id">:</dd>
                            <dt class="col-sm-5">Nama Pelanggan</dt>
                            <dd class="col-sm-7" id="detail_nama_pelanggan">:</dd>
                            <dt class="col-sm-5">Periode</dt>
                            <dd class="col-sm-7" id="detail_periode">:</dd>
                            <dt class="col-sm-5">Jatuh Tempo</dt>
                            <dd class="col-sm-7" id="detail_jatuh_tempo">:</dd>
                            <dt class="col-sm-5">Status</dt>
                            <dd class="col-sm-7" id="detail_status_tagihan">:</dd>
                        </dl>
                        <hr class="my-2">
                        {{-- PERBAIKAN RINCIAN BIAYA DI SINI --}}
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Biaya Pemakaian</dt>
                            <dd class="col-sm-7" id="detail_biaya_pemakaian_rp">:</dd>
                            <dt class="col-sm-5">Biaya Abonemen</dt>
                            <dd class="col-sm-7" id="detail_abonemen_rp">:</dd>
                            <dt class="col-sm-5 font-weight-bold">Sub Total</dt>
                            <dd class="col-sm-7 font-weight-bold" id="detail_sub_total_tagihan_rp">:</dd>
                            <dt class="col-sm-5">Denda Tercatat</dt>
                            <dd class="col-sm-7" id="detail_denda_existing_rp">:</dd>
                            <dt class="col-sm-5 h6 font-weight-bold text-primary">Total Awal</dt>
                            <dd class="col-sm-7 h6 font-weight-bold text-primary" id="detail_total_tagihan_rp">:</dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Form Pembayaran --}}
            <div class="col-lg-7">
                <div class="card shadow mb-4" id="cardFormPembayaran" style="display:none;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-cash-register mr-1"></i> Form
                            Pembayaran</h6>
                    </div>
                    <div class="card-body">
                        <form id="formPembayaran" onsubmit="return false;">
                            @csrf
                            <input type="hidden" name="tagihan_id_hidden" id="tagihan_id_hidden">

                            <div class="form-section">
                                <h6 class="font-weight-bold text-info">Perhitungan Denda & Total</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tanggal_bayar_submit">Tanggal Bayar <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="tanggal_bayar_submit"
                                                name="tanggal_bayar_submit"
                                                value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                                            <div class="invalid-feedback" id="tanggal_bayar_submit_error"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-outline-info mb-3"
                                            id="btnHitungDenda"><i class="fas fa-calculator mr-1"></i> Hitung Denda &
                                            Total</button>
                                    </div>
                                </div>
                                <dl class="row">
                                    <dt class="col-sm-5">Denda Terhitung</dt>
                                    <dd class="col-sm-7" id="display_denda_calculated_rp">: Rp 0</dd>
                                    <dt class="col-sm-5 h5 text-danger">Total Tagihan Akhir</dt>
                                    <dd class="col-sm-7 h5 text-danger" id="display_total_akhir_rp">: Rp 0</dd>
                                </dl>
                                <input type="hidden" name="denda_final_submit" id="denda_final_submit" value="0">
                                <input type="hidden" name="total_akhir_submit" id="total_akhir_submit" value="0">
                            </div>

                            <div class="form-section">
                                <h6 class="font-weight-bold text-info">Detail Pembayaran</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="jumlah_bayar_submit">Jumlah Bayar (Rp) <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="jumlah_bayar_submit"
                                                name="jumlah_bayar_submit" step="any" min="1"
                                                placeholder="Masukkan jumlah pembayaran">
                                            <div class="invalid-feedback" id="jumlah_bayar_submit_error"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="metode_pembayaran_submit">Metode Pembayaran <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" id="metode_pembayaran_submit"
                                                name="metode_pembayaran_submit">
                                                @foreach ($metode_pembayaran as $metode)
                                                    <option value="{{ $metode }}">{{ $metode }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="referensi_pembayaran_submit">No. Referensi (Opsional)</label>
                                    <input type="text" class="form-control" id="referensi_pembayaran_submit"
                                        name="referensi_pembayaran_submit" placeholder="Misal: No. Struk Bank">
                                </div>
                                <div class="form-group">
                                    <label for="keterangan_pembayaran_submit">Keterangan Pembayaran (Opsional)</label>
                                    <textarea class="form-control" id="keterangan_pembayaran_submit" name="keterangan_pembayaran_submit" rows="2"
                                        placeholder="Catatan tambahan untuk pembayaran ini"></textarea>
                                </div>
                            </div>
                            <hr>
                            <div class="text-right">
                                <button type="button" class="btn btn-success btn-icon-split" id="btnSimpanPembayaran">
                                    <span class="icon text-white-50"><i class="fas fa-check-circle"></i></span>
                                    <span class="text">Simpan Pembayaran</span>
                                </button>
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
            moment.locale('id');
            let selectedTagihanData = null;

            if ($('meta[name="csrf-token"]').length > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            }

            // Inisialisasi Select2
            $('#tagihan_search_select2').select2({
                theme: 'bootstrap4',
                placeholder: 'Ketik untuk mencari tagihan...',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('pembayaran.search_tagihan') }}",
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            term: params.term || '',
                            page: params.page || 1
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
                    var periode = moment(data.periode_tagihan_tahun + '-' + String(data
                        .periode_tagihan_bulan).padStart(2, '0') + '-01').format('MMMM YYYY');
                    var $container = $(
                        "<div class='select2-result-repository clearfix'>" +
                        "<div class='select2-result-repository__title'>" + data.nama_pelanggan +
                        " (" + data.id_pelanggan_unik + ")</div>" +
                        "<div class='select2-result-repository__description'>" +
                        "<i class='fas fa-tachometer-alt fa-fw'></i> " + data.no_meter + " | " +
                        "<i class='fas fa-map-marker-alt fa-fw'></i> " + data.nama_wilayah +
                        "</div>" +
                        "<div class='select2-result-repository__meta'>" +
                        "<i class='fas fa-receipt fa-fw'></i> Tagihan #" + data.id + " | " +
                        "<i class='fas fa-calendar-alt fa-fw'></i> Periode: " + periode +
                        "</div>" +
                        "</div>"
                    );
                    return $container;
                },
                templateSelection: function(data) {
                    if (data.id) {
                        return `Tagihan #${data.id} - ${data.nama_pelanggan}`;
                    }
                    return 'Cari dan pilih tagihan...';
                }
            });

            // Event saat sebuah tagihan dipilih dari Select2
            $('#tagihan_search_select2').on('select2:select', function(e) {
                selectedTagihanData = e.params.data;
                populateDetailsAndForm(selectedTagihanData);
            });

            $('#tagihan_search_select2').on('select2:unselect', function(e) {
                clearDetailTagihan();
                clearFormPembayaran();
            });

            function populateDetailsAndForm(data) {
                if (!data) return;
                clearFormPembayaran();

                $('#tagihan_id_hidden').val(data.id);
                $('#detail_tagihan_id').text(': ' + data.id);
                $('#detail_id_pelanggan_unik').text(': ' + data.id_pelanggan_unik);
                $('#detail_nama_pelanggan').text(': ' + data.nama_pelanggan);
                $('#detail_no_meter').text(': ' + data.no_meter);

                let periodeFormatted = moment(data.periode_tagihan_tahun + '-' + String(data.periode_tagihan_bulan)
                    .padStart(2, '0') + '-01').format('MMMM YYYY');
                let jatuhTempoFormatted = moment(data.tanggal_jatuh_tempo).format('D MMMM YYYY');
                $('#detail_periode').text(': ' + periodeFormatted);
                $('#detail_jatuh_tempo').text(': ' + jatuhTempoFormatted);

                let statusBadgeColor = 'secondary';
                if (data.status_tagihan === 'BelumLunas') statusBadgeColor = 'danger';
                else if (data.status_tagihan === 'LunasSebagian') statusBadgeColor = 'warning';
                let statusBadge = `<span class="badge badge-${statusBadgeColor}">${data.status_tagihan}</span>`;
                $('#detail_status_tagihan').html(': ' + statusBadge);

                // PERBAIKAN RINCIAN BIAYA DI SINI
                let biayaPemakaian = parseFloat(data.biaya_pemakaian || 0);
                let abonemen = parseFloat(data.abonemen_saat_tagihan || 0);
                $('#detail_biaya_pemakaian_rp').text(': Rp ' + biayaPemakaian.toLocaleString('id-ID'));
                $('#detail_abonemen_rp').text(': Rp ' + abonemen.toLocaleString('id-ID'));

                $('#detail_sub_total_tagihan_rp').text(': Rp ' + parseFloat(data.sub_total_tagihan || 0)
                    .toLocaleString('id-ID'));
                $('#detail_denda_existing_rp').text(': Rp ' + parseFloat(data.denda_existing || 0).toLocaleString(
                    'id-ID'));
                $('#detail_total_tagihan_rp').text(': Rp ' + parseFloat(data.total_tagihan || 0).toLocaleString(
                    'id-ID'));

                $('#display_sub_total_rp').text(': Rp ' + parseFloat(data.sub_total_tagihan || 0).toLocaleString(
                    'id-ID'));

                $('#cardDetailTagihan').slideDown();
                $('#cardFormPembayaran').slideDown();

                $('#btnHitungDenda').click();
            }

            function clearFormPembayaran() {
                $('#formPembayaran')[0].reset();
                $('#tagihan_id_hidden').val('');
                $('#tanggal_bayar_submit').val('{{ Carbon\Carbon::now()->format('Y-m-d') }}');
                $('#display_denda_calculated_rp').text(': Rp 0');
                $('#display_total_akhir_rp').text(': Rp 0');
                $('#denda_final_submit').val(0);
                $('#total_akhir_submit').val(0);
                $('#formPembayaran .form-control').removeClass('is-invalid');
                $('#formPembayaran .invalid-feedback').text('').hide();
            }

            function clearDetailTagihan() {
                $('#detail_tagihan_id').text(':');
                $('#detail_id_pelanggan_unik').text(':');
                $('#detail_nama_pelanggan').text(':');
                $('#detail_no_meter').text(':');
                $('#detail_periode').text(':');
                $('#detail_jatuh_tempo').text(':');
                $('#detail_status_tagihan').html(':');
                $('#detail_sub_total_tagihan_rp').text(':');
                $('#detail_denda_existing_rp').text(':');
                $('#detail_total_tagihan_rp').text(':');
                $('#cardDetailTagihan').hide();
                $('#cardFormPembayaran').hide();
                selectedTagihanData = null;
            }

            $('#btnHitungDenda').click(function() {
                if (!selectedTagihanData) {
                    return;
                }
                var tanggalBayar = $('#tanggal_bayar_submit').val();
                if (!tanggalBayar) {
                    Swal.fire('Error', 'Tanggal bayar wajib diisi.', 'error');
                    return;
                }
                $('#btnHitungDenda').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Menghitung...');
                $.ajax({
                    url: "{{ route('pembayaran.hitung_denda') }}",
                    type: 'POST',
                    data: {
                        tagihan_id: selectedTagihanData.id,
                        tanggal_bayar: tanggalBayar
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#display_denda_calculated_rp').text(': ' + response
                                .denda_calculated_rp);
                            $('#display_total_akhir_rp').text(': ' + response
                                .total_dengan_denda_rp);
                            $('#denda_final_submit').val(response.denda_calculated);
                            $('#total_akhir_submit').val(response.total_dengan_denda);
                            $('#jumlah_bayar_submit').val(response.total_dengan_denda.toFixed(
                                0)).focus();
                        } else {
                            Swal.fire('Error', response.message || 'Gagal menghitung denda.',
                                'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                    },
                    complete: function() {
                        $('#btnHitungDenda').prop('disabled', false).html(
                            '<i class="fas fa-calculator mr-1"></i> Hitung Ulang Denda');
                    }
                });
            });

            function printReceipt(pembayaranId) {
                $('#print-iframe').remove();
                var url = "{{ url('/pembayaran/struk') }}/" + pembayaranId;
                var iframe = $('<iframe id="print-iframe" src="' + url + '" style="display: none;"></iframe>');
                $('body').append(iframe);
                iframe.on('load', function() {
                    setTimeout(function() {
                        iframe.remove();
                    }, 2000);
                });
            }

            function prosesSimpanPembayaran() {
                var formData = $('#formPembayaran').serialize();
                Swal.fire({
                    title: 'Menyimpan Pembayaran...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: "{{ route('pembayaran.store') }}",
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sukses!',
                                text: response.message,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                showDenyButton: !!response.pembayaran_id_baru,
                                denyButtonText: `<i class="fas fa-print"></i> Cetak Struk`,
                                denyButtonColor: '#1cc88a',
                                focusDeny: true
                            }).then((result) => {
                                // PERBAIKAN UTAMA DI SINI
                                if (result.isDenied) {
                                    printReceipt(response
                                        .pembayaran_id_baru); // Panggil fungsi direct print
                                }
                                // Reset form setelah dialog ditutup
                                $('#cardDetailTagihan').slideUp();
                                $('#cardFormPembayaran').slideUp();
                                $('#pelanggan_search_select2').val(null).trigger('change');
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message || 'Kesalahan saat menyimpan.'
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.close();
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            var errorMessage = '<ul>';
                            $.each(errors, function(key, value) {
                                $('#' + key.replace('_submit', '') + '_error').text(value[0])
                                    .show();
                                $('#' + key.replace('_submit', '')).addClass('is-invalid');
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
                                text: (xhr.responseJSON && xhr.responseJSON.message) ||
                                    'Tidak dapat menyimpan data.'
                            });
                        }
                    }
                });
            }

            $('#btnSimpanPembayaran').click(function() {
                if (!selectedTagihanData) {
                    Swal.fire('Error', 'Tidak ada tagihan yang dipilih.', 'error');
                    return;
                }
                prosesSimpanPembayaran();
            });
        });
    </script>
@endpush
