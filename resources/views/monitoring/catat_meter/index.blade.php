@extends('layouts.master')

@section('title', 'Daftar Status Pencatatan Meter')

@push('styles')
    {{-- ... (style seperti sebelumnya) ... --}}
@endpush

@section('content')
    <div class="container-fluid">
        {{-- ... (Judul Halaman dan Filter Card seperti sebelumnya) ... --}}
        <h1 class="h3 mb-2 text-gray-800"><i class="fas fa-tasks mr-2"></i>Daftar Status Pencatatan Meter</h1>
        <p class="mb-4">Pantau progres pencatatan meter pelanggan berdasarkan periode, wilayah, dan status.</p>
        <div class="card shadow mb-4 filter-card">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter mr-2"></i>Filter Data</h6>
            </div>
            <div class="card-body">
                <form id="filterForm" class="form-row align-items-end">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label for="filter_tahun">Tahun Periode</label>
                        <select class="form-control form-control-sm" id="filter_tahun" name="filter_tahun">
                            @foreach ($tahuns as $tahun)
                                <option value="{{ $tahun }}"
                                    {{ $tahun == Carbon\Carbon::now()->year ? 'selected' : '' }}>{{ $tahun }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label for="filter_bulan">Bulan Periode</label>
                        <select class="form-control form-control-sm" id="filter_bulan" name="filter_bulan">
                            @foreach ($bulans as $num => $nama)
                                <option value="{{ $num }}"
                                    {{ $num == Carbon\Carbon::now()->month ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label for="filter_wilayah">Wilayah</label>
                        <select class="form-control form-control-sm" id="filter_wilayah" name="filter_wilayah">
                            <option value="">Semua Wilayah</option>
                            @foreach ($wilayahs as $wilayah)
                                <option value="{{ $wilayah->wilayah_id }}">{{ $wilayah->nama_wilayah }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <label for="filter_status">Status Pencatatan</label>
                        <select class="form-control form-control-sm" id="filter_status" name="filter_status">
                            @foreach ($statuses as $status)
                                <option value="{{ $status == 'Semua' ? '' : $status }}"
                                    {{ $status == 'Belum Dicatat' ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>


        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Pelanggan</h6>
                {{-- TOMBOL EXPORT BARU --}}
                <div>
                    <button class="btn btn-sm btn-success" id="btnExportExcelMonitoring"><i
                            class="fas fa-file-excel mr-1"></i> Export Excel</button>
                    <button class="btn btn-sm btn-danger" id="btnExportPdfMonitoring"><i class="fas fa-file-pdf mr-1"></i>
                        Export PDF</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="data-monitoring"
                        class="table table-bordered table-striped table-hover display compact responsive table-sm"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Pelanggan</th>
                                <th>Nama</th>
                                <th>No. Meter</th>
                                <th>Wilayah</th>
                                <th>Alamat</th>
                                <th class="text-right">M. Awal</th>
                                <th class="text-right">M. Akhir</th>
                                <th>Tgl Catat</th>
                                <th class="text-center">Status</th>
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
            var tableMonitoring = $('#data-monitoring').DataTable({
                // ... (definisi DataTables seperti sebelumnya) ...
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('monitoring.catat_meter.data') }}",
                    type: "GET",
                    data: function(d) {
                        d.filter_tahun = $('#filter_tahun').val();
                        d.filter_bulan = $('#filter_bulan').val();
                        d.filter_wilayah = $('#filter_wilayah').val();
                        d.filter_status = $('#filter_status').val();
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
                        data: 'alamat',
                        name: 'p.alamat',
                        orderable: false
                    },
                    {
                        data: 'meter_awal',
                        name: 'pm.meter_awal',
                        className: 'text-right'
                    },
                    {
                        data: 'meter_akhir',
                        name: 'pm.meter_akhir',
                        className: 'text-right'
                    },
                    {
                        data: 'tanggal_catat',
                        name: 'pm.tanggal_catat',
                        className: 'text-center'
                    },
                    {
                        data: 'status_pencatatan_final',
                        name: 'status_pencatatan_final',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                responsive: true,
                order: [
                    [2, 'asc']
                ], // Urutkan berdasarkan Nama Pelanggan
                language: {
                    /* ... Opsi bahasa Indonesia ... */ }
            });

            // Otomatis reload DataTables saat filter diubah
            $('#filter_tahun, #filter_bulan, #filter_wilayah, #filter_status').change(function() {
                tableMonitoring.draw();
            });

            // Fungsi untuk membangun query string dari filter
            function buildMonitoringFilterQueryString() {
                var params = {
                    filter_tahun: $('#filter_tahun').val(),
                    filter_bulan: $('#filter_bulan').val(),
                    filter_wilayah: $('#filter_wilayah').val(),
                    filter_status: $('#filter_status').val()
                };
                return $.param(params);
            }

            // Event untuk tombol export
            $('#btnExportExcelMonitoring').click(function(e) {
                e.preventDefault();
                var queryString = buildMonitoringFilterQueryString();
                window.location.href = "{{ route('monitoring.catat_meter.export_excel') }}?" + queryString;
            });

            $('#btnExportPdfMonitoring').click(function(e) {
                e.preventDefault();
                var queryString = buildMonitoringFilterQueryString();
                window.open("{{ route('monitoring.catat_meter.export_pdf') }}?" + queryString, '_blank');
            });
        });
    </script>
@endpush
