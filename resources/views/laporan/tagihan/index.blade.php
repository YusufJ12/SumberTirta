@extends('layouts.master')
@section('title', 'Laporan Tagihan')

@push('styles')
    <style>
        .filter-card {
            background-color: #f8f9fc;
        }

        .summary-card h6 {
            font-size: 0.8rem;
            color: #858796;
            text-transform: uppercase;
        }

        .summary-card .h5 {
            font-weight: bold;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-chart-line mr-2"></i>Laporan Tagihan</h1>

        <div class="card shadow mb-4 filter-card">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Filter Laporan</h6>
            </div>
            <div class="card-body">
                <form id="filterLaporanTagihanForm" class="form-row align-items-end">
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_tahun_laporan">Tahun Periode</label>
                        <select class="form-control form-control-sm" id="filter_tahun_laporan" name="filter_tahun_laporan">
                            <option value="">Semua</option>
                            @foreach ($tahuns as $tahun)
                                <option value="{{ $tahun }}"
                                    {{ $tahun == \Carbon\Carbon::now()->year ? 'selected' : '' }}>{{ $tahun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_bulan_laporan">Bulan Periode</label>
                        <select class="form-control form-control-sm" id="filter_bulan_laporan" name="filter_bulan_laporan">
                            <option value="">Semua</option>
                            @foreach ($bulans as $num => $nama)
                                <option value="{{ $num }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_status_laporan">Status Tagihan</label>
                        <select class="form-control form-control-sm" id="filter_status_laporan"
                            name="filter_status_laporan">
                            @foreach ($statuses as $status)
                                <option value="{{ $status == 'Semua' ? '' : $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-6 mb-2">
                        <label for="filter_wilayah_laporan">Wilayah</label>
                        <select class="form-control form-control-sm" id="filter_wilayah_laporan"
                            name="filter_wilayah_laporan">
                            <option value="">Semua</option>
                            @foreach ($wilayahs as $wilayah)
                                <option value="{{ $wilayah->wilayah_id }}">{{ $wilayah->nama_wilayah }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-12 mb-2">
                        <label for="filter_pelanggan_laporan">ID/No.Meter/Nama</label>
                        <input type="text" class="form-control form-control-sm" id="filter_pelanggan_laporan"
                            name="filter_pelanggan_laporan" placeholder="Cari...">
                    </div>
                    <div class="col-md-2 col-sm-12 mb-2">
                        <button type="button" id="btnFilterLaporan" class="btn btn-sm btn-primary btn-block mt-3"><i
                                class="fas fa-search fa-sm"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row summary-card">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <h6>Total Tagihan (Filtered)</h6>
                                <div class="h5 text-primary" id="summary_total_tagihan">Rp 0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <h6>Total Sudah Dibayar</h6>
                                <div class="h5 text-success" id="summary_total_dibayar">Rp 0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <h6>Total Tunggakan</h6>
                                <div class="h5 text-danger" id="summary_total_tunggakan">Rp 0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-exclamation-circle fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <h6>Jumlah Tagihan</h6>
                                <div class="h5 text-info" id="summary_jumlah_tagihan">0</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-list-ol fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Detail Laporan Tagihan</h6>
                <div>
                    <button class="btn btn-sm btn-success" id="btnExportExcel"><i class="fas fa-file-excel mr-1"></i> Export
                        Excel</button>
                    <button class="btn btn-sm btn-danger" id="btnExportPdf"><i class="fas fa-file-pdf mr-1"></i> Export
                        PDF</button>
                </div>
            </div>
            <div class="card-body">
                <table id="data-laporan-tagihan" class="table table-bordered table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID Tagihan</th>
                            <th>ID Pel.</th>
                            <th>Nama Pelanggan</th>
                            <th>No. Meter</th>
                            <th>Wilayah</th>
                            <th>Periode Pemakaian</th>
                            <th>Periode Tagihan</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-right">Total Tagihan</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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

            var tableLaporanTagihan = $('#data-laporan-tagihan').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('laporan.tagihan.data') }}",
                    type: "GET",
                    data: function(d) {
                        d.filter_tahun_laporan = $('#filter_tahun_laporan').val();
                        d.filter_bulan_laporan = $('#filter_bulan_laporan').val();
                        d.filter_status_laporan = $('#filter_status_laporan').val();
                        d.filter_wilayah_laporan = $('#filter_wilayah_laporan').val();
                        d.filter_pelanggan_laporan = $('#filter_pelanggan_laporan').val();
                    },
                    dataSrc: function(json) {
                        if (json.summary) {
                            $('#summary_total_tagihan').text(json.summary.grand_total_tagihan_rp ||
                                'Rp 0');
                            $('#summary_total_dibayar').text(json.summary.grand_total_dibayar_rp ||
                                'Rp 0');
                            $('#summary_total_tunggakan').text(json.summary.grand_total_tunggakan_rp ||
                                'Rp 0');
                            $('#summary_jumlah_tagihan').text(json.summary.jumlah_tagihan || '0');
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
                        data: 'tagihan_id',
                        name: 'tg.tagihan_id'
                    },
                    {
                        data: 'id_pelanggan_unik',
                        name: 'p.id_pelanggan_unik'
                    },
                    {
                        data: 'nama_pelanggan',
                        name: 'p.nama_pelanggan'
                    },
                    {
                        data: 'no_meter',
                        name: 'p.no_meter'
                    },
                    {
                        data: 'nama_wilayah',
                        name: 'w.nama_wilayah'
                    },
                    {
                        data: 'periode_tagihan_bulan',
                        name: 'tg.periode_tagihan_bulan',
                        render: (data, type, row) =>
                            `${moment(row.periode_tagihan_tahun + '-' + data + '-01').format("MMMM")} ${row.periode_tagihan_tahun}`
                    },
                    {
                        data: 'tanggal_terbit',
                        name: 'tg.tanggal_terbit',
                        render: (data) => moment(data).format('DD-MM-YYYY')
                    },
                    {
                        data: 'tanggal_jatuh_tempo',
                        name: 'tg.tanggal_jatuh_tempo',
                        render: (data) => moment(data).format('DD-MM-YYYY')
                    },
                    {
                        data: 'total_tagihan',
                        name: 'tg.total_tagihan',
                        className: 'text-right',
                        render: $.fn.dataTable.render.number('.', ',', 0, 'Rp ')
                    },
                    {
                        data: 'status_tagihan',
                        name: 'tg.status_tagihan',
                        className: 'text-center',
                        render: function(data) {
                            let badgeClass = 'badge-light';
                            if (data == 'BelumLunas') badgeClass = 'badge-danger';
                            else if (data == 'LunasSebagian') badgeClass = 'badge-warning';
                            else if (data == 'Lunas') badgeClass = 'badge-success';
                            else if (data == 'Dibatalkan') badgeClass = 'badge-secondary';
                            return `<span class="badge ${badgeClass}">${data}</span>`;
                        }
                    }
                ],
                order: [
                    [1, 'desc']
                ],
            });

            $('#btnFilterLaporan').click(function() {
                tableLaporanTagihan.draw();
            });

            $('#filter_pelanggan_laporan').keypress(function(event) {
                if (event.which == 13) {
                    $('#btnFilterLaporan').click();
                }
            });

            function buildFilterQueryString() {
                return $('#filterLaporanTagihanForm').serialize();
            }

            $('#btnExportExcel').click(function(e) {
                e.preventDefault();
                window.location.href = "{{ route('laporan.tagihan.export_excel') }}?" +
                    buildFilterQueryString();
            });

            $('#btnExportPdf').click(function(e) {
                e.preventDefault();
                window.open("{{ route('laporan.tagihan.export_pdf') }}?" + buildFilterQueryString(),
                    '_blank');
            });
        });
    </script>
@endpush
