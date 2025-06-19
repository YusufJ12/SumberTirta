@extends('layouts.master')
@section('title', 'Laporan Tunggakan Pelanggan')

@push('styles')
    <style>
        .filter-card {
            background-color: #f8f9fc;
        }

        .summary-card h6 {
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .summary-card .h5 {
            font-weight: bold;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-file-invoice-dollar mr-2"></i>Laporan Tunggakan</h1>
        <p class="mb-4">Daftar tagihan yang telah melewati tanggal jatuh tempo.</p>

        <div class="card shadow mb-4 filter-card">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Filter Laporan</h6>
            </div>
            <div class="card-body">
                <form id="filterLaporanTunggakanForm" class="form-row align-items-end">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label for="filter_wilayah_tunggakan">Wilayah</label>
                        <select class="form-control form-control-sm" id="filter_wilayah_tunggakan"
                            name="filter_wilayah_tunggakan">
                            <option value="">Semua Wilayah</option>
                            @foreach ($wilayahs as $wilayah)
                                <option value="{{ $wilayah->wilayah_id }}">{{ $wilayah->nama_wilayah }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label for="filter_usia_tunggakan">Usia Tunggakan</label>
                        <select class="form-control form-control-sm" id="filter_usia_tunggakan"
                            name="filter_usia_tunggakan">
                            @foreach ($opsi_usia_tunggakan as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <label for="filter_pelanggan_tunggakan">ID/No.Meter/Nama Pelanggan</label>
                        <input type="text" class="form-control form-control-sm" id="filter_pelanggan_tunggakan"
                            name="filter_pelanggan_tunggakan" placeholder="Cari pelanggan...">
                    </div>
                    <div class="col-md-2 col-sm-12 mb-2">
                        <button type="button" id="btnFilterLaporan" class="btn btn-sm btn-primary btn-block mt-3"><i
                                class="fas fa-search fa-sm"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row summary-card">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <h6>Total Nilai Tunggakan</h6>
                                <div class="h5 text-danger" id="summary_total_nilai_tunggakan">Rp 0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <h6>Jumlah Tagihan Menunggak</h6>
                                <div class="h5 text-warning" id="summary_jumlah_tagihan_tunggakan">0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-file-alt fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <h6>Jumlah Pelanggan Menunggak</h6>
                                <div class="h5 text-info" id="summary_jumlah_pelanggan_tunggakan">0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Detail Laporan Tunggakan</h6>
                <div>
                    <button class="btn btn-sm btn-info" id="btnExportRekap"><i class="fas fa-file-excel mr-1"></i> Rekap 5
                        Bulan Excel</button>
                    <button class="btn btn-sm btn-secondary" id="btnExportRekapPdf"><i class="fas fa-file-pdf mr-1"></i>
                        Rekap 5 Bulan PDF</button>
                    <button id="btnExportExcel" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i>
                        Excel</button>
                    <button id="btnExportPdf" class="btn btn-sm btn-danger"><i class="fas fa-file-pdf"></i> PDF</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="data-laporan-tunggakan" class="table table-bordered table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Pel.</th>
                                <th>Nama Pel.</th>
                                <th>No Meter</th>
                                <th>Wilayah</th>
                                <th>Periode Pemakaian</th>
                                <th>Periode Tagihan</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-center">Usia</th>
                                <th class="text-right">Total Tagihan</th>
                                <th class="text-right">Denda</th>
                                <th class="text-right">Total</th>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/id.js"></script>
    <script>
        $(document).ready(function() {
            moment.locale('id');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var table = $('#data-laporan-tunggakan').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('laporan.tunggakan.data') }}",
                    type: "GET",
                    data: function(d) {
                        d.filter_wilayah_tunggakan = $('#filter_wilayah_tunggakan').val();
                        d.filter_usia_tunggakan = $('#filter_usia_tunggakan').val();
                        d.filter_pelanggan_tunggakan = $('#filter_pelanggan_tunggakan').val();
                    },
                    dataSrc: function(json) {
                        if (json.summary) {
                            $('#summary_total_nilai_tunggakan').text(json.summary
                                .grand_total_sisa_tunggakan_rp || 'Rp 0');
                            $('#summary_jumlah_tagihan_tunggakan').text(json.summary
                                .jumlah_tagihan_menunggak || '0');
                            $('#summary_jumlah_pelanggan_tunggakan').text(json.summary
                                .jumlah_pelanggan_menunggak || '0');
                        }
                        return json.data;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id_pelanggan_unik',
                        name: 'id_pelanggan_unik'
                    },
                    {
                        data: 'nama_pelanggan',
                        name: 'nama_pelanggan'
                    },
                    {
                        data: 'no_meter',
                        name: 'no_meter'
                    },
                    {
                        data: 'nama_wilayah',
                        name: 'nama_wilayah'
                    },
                    {
                        data: 'periode_tagihan_bulan',
                        name: 'periode_tagihan_bulan',
                        render: (data, type, row) =>
                            `${moment(row.periode_tagihan_tahun + '-' + data + '-01').format("MMMM YYYY")}`
                    },
                    {
                        data: 'tanggal_terbit',
                        name: 'tanggal_terbit',
                        render: (data) => moment(data).format('DD-MM-YYYY')
                    },
                    {
                        data: 'tanggal_jatuh_tempo',
                        name: 'tanggal_jatuh_tempo',
                        render: (data) => moment(data).format('DD-MM-YYYY')
                    },
                    {
                        data: 'usia_tunggakan_hari',
                        name: 'usia_tunggakan_hari',
                        className: 'text-center'
                    },
                    {
                        data: 'total_tagihan_pokok',
                        name: 'total_tagihan_pokok',
                        className: 'text-right',
                        searchable: false,
                        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
                    },
                    {
                        data: 'denda_sekarang',
                        name: 'denda_sekarang',
                        className: 'text-right text-danger',
                        searchable: false,
                        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
                    },
                    {
                        data: 'total_keseluruhan',
                        name: 'total_keseluruhan',
                        className: 'text-right font-weight-bold',
                        searchable: false,
                        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
                    }
                ],
                order: [
                    [7, 'desc']
                ], // Urutkan berdasarkan Usia Tunggakan (kolom ke-8, index 7)
            });

            $('#btnFilterLaporan').click(function() {
                table.draw();
            });

            function buildFilterQueryString() {
                return $('#filterLaporanTunggakanForm').serialize();
            }
            $('#btnExportExcel').click(function(e) {
                e.preventDefault();
                window.location.href = "{{ route('laporan.tunggakan.export_excel') }}?" +
                    buildFilterQueryString();
            });
            $('#btnExportPdf').click(function(e) {
                e.preventDefault();
                window.open("{{ route('laporan.tunggakan.export_pdf') }}?" + buildFilterQueryString(),
                    '_blank');
            });
            $('#btnExportRekap').click(function(e) {
                e.preventDefault();
                var queryString = buildFilterQueryString();
                window.location.href = "{{ route('laporan.tunggakan.export_rekap') }}?" + queryString;
            });
            $('#btnExportRekapPdf').click(function(e) {
                e.preventDefault();
                window.open("{{ route('laporan.tunggakan.export_rekap_pdf') }}?" + buildFilterQueryString(),
                    '_blank');
            });
        });
    </script>
@endpush
