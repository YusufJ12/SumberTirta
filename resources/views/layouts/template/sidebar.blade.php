{{-- PERUBAHAN UTAMA: class "toggled" dihapus dari sini --}}
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion toggled" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ url('/home') }}">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-tint"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Sumber Tirta</div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item {{ request()->is('home') || request()->is('/') ? 'active' : '' }}">
        <a class="nav-link" href="{{ url('home') }}">
            @if (auth()->check())
                @if (auth()->user()->type == 1)
                    {{-- Administrator --}}
                    <i class="fas fa-fw fa-money-check-alt"></i>
                    <span>Input Pembayaran</span>
                @elseif (auth()->user()->type == 2)
                    {{-- Operator --}}
                    <i class="fas fa-fw fa-edit"></i>
                    <span>Input Catatan Meter</span>
                @else
                    {{-- Fallback --}}
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                @endif
            @endif
        </a>
    </li>

    {{-- MENU KHUSUS UNTUK OPERATOR --}}
    @if (auth()->check() && auth()->user()->type == 2)
        <hr class="sidebar-divider">
        <div class="sidebar-heading">
            Operasional
        </div>
        <li class="nav-item {{ request()->routeIs('monitoring.catat_meter.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('monitoring.catat_meter.index') }}">
                <i class="fas fa-fw fa-clipboard-list"></i>
                <span>Status Catat Meter</span>
            </a>
        </li>
    @endif


    {{-- MENU KHUSUS UNTUK ADMINISTRATOR --}}
    @if (auth()->check() && auth()->user()->type == 1)
        <hr class="sidebar-divider">

        {{-- <li class="nav-item {{ request()->routeIs('pembayaran.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('pembayaran.index') }}">
                <i class="fas fa-fw fa-search-dollar"></i>
                <span>Input Pembayaran Lama</span>
            </a>
        </li> --}}

        <div class="sidebar-heading">
            Proses & Keuangan
        </div>

        <li
            class="nav-item {{ request()->routeIs('pencatatan_meter.*') || request()->routeIs('monitoring.catat_meter.*') ? 'active' : '' }}">
            <a class="nav-link {{ !(request()->routeIs('pencatatan_meter.*') || request()->routeIs('monitoring.catat_meter.*')) ? 'collapsed' : '' }}"
                href="#" data-toggle="collapse" data-target="#collapsePencatatan" aria-expanded="true"
                aria-controls="collapsePencatatan">
                <i class="fas fa-fw fa-edit"></i>
                <span>Pencatatan Meter</span>
            </a>
            <div id="collapsePencatatan"
                class="collapse {{ request()->routeIs('pencatatan_meter.*') || request()->routeIs('monitoring.catat_meter.*') ? 'show' : '' }}"
                aria-labelledby="headingPencatatan" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item {{ request()->routeIs('monitoring.catat_meter.*') ? 'active' : '' }}"
                        href="{{ route('monitoring.catat_meter.index') }}">Status Catat Meter</a>
                    <a class="collapse-item {{ request()->routeIs('pencatatan_meter.*') ? 'active' : '' }}"
                        href="{{ route('pencatatan_meter.index') }}">Input Pencatatan Meter</a>
                </div>
            </div>
        </li>

        <li class="nav-item {{ request()->routeIs('pembuatan_tagihan.index') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('pembuatan_tagihan.index') }}">
                <i class="fas fa-fw fa-cogs"></i>
                <span>Proses Buat Tagihan</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('tagihan.manage.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('tagihan.manage.index') }}">
                <i class="fas fa-fw fa-list-alt"></i>
                <span>Manajemen Tagihan</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('ganti_meter.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('ganti_meter.index') }}">
                <i class="fas fa-fw fa-sync-alt"></i>
                <span>Ganti Meter</span>
            </a>
        </li>


        <hr class="sidebar-divider">
        <div class="sidebar-heading">
            Data & Sistem
        </div>

        @php
            $isLaporanActive = request()->routeIs(['laporan.tagihan.*', 'laporan.tunggakan.*', 'laporan.pembayaran.*']);
        @endphp
        <li class="nav-item {{ $isLaporanActive ? 'active' : '' }}">
            <a class="nav-link {{ !$isLaporanActive ? 'collapsed' : '' }}" href="#" data-toggle="collapse"
                data-target="#collapseLaporan" aria-expanded="{{ $isLaporanActive ? 'true' : 'false' }}"
                aria-controls="collapseLaporan">
                <i class="fas fa-fw fa-chart-area"></i>
                <span>Laporan</span>
            </a>
            <div id="collapseLaporan" class="collapse {{ $isLaporanActive ? 'show' : '' }}"
                aria-labelledby="headingLaporan" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item {{ request()->routeIs('laporan.tagihan.*') ? 'active' : '' }}"
                        href="{{ route('laporan.tagihan.index') }}">Laporan Tagihan</a>
                    <a class="collapse-item {{ request()->routeIs('laporan.tunggakan.*') ? 'active' : '' }}"
                        href="{{ route('laporan.tunggakan.index') }}">Laporan Tunggakan</a>
                    <a class="collapse-item {{ request()->routeIs('laporan.pembayaran.*') ? 'active' : '' }}"
                        href="{{ route('laporan.pembayaran.index') }}">Laporan Pembayaran</a>
                </div>
            </div>
        </li>

        @php
            $isDataDasarActive = request()->routeIs(['wilayah.*', 'tarif.*', 'pelanggan.*', 'aturan_denda.*']);
        @endphp
        <li class="nav-item {{ $isDataDasarActive ? 'active' : '' }}">
            <a class="nav-link {{ !$isDataDasarActive ? 'collapsed' : '' }}" href="#" data-toggle="collapse"
                data-target="#collapseDataDasar" aria-expanded="{{ $isDataDasarActive ? 'true' : '' }}"
                aria-controls="collapseDataDasar">
                <i class="fas fa-fw fa-archive"></i>
                <span>Data Dasar</span>
            </a>
            <div id="collapseDataDasar" class="collapse {{ $isDataDasarActive ? 'show' : '' }}"
                aria-labelledby="headingDataDasar" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item {{ request()->routeIs('wilayah.*') ? 'active' : '' }}"
                        href="{{ route('wilayah.index') }}">Master Wilayah</a>
                    <a class="collapse-item {{ request()->routeIs('tarif.*') ? 'active' : '' }}"
                        href="{{ route('tarif.index') }}">Master Tarif</a>
                    <a class="collapse-item {{ request()->routeIs('pelanggan.*') ? 'active' : '' }}"
                        href="{{ route('pelanggan.index') }}">Master Pelanggan</a>
                    <a class="collapse-item {{ request()->routeIs('aturan_denda.*') ? 'active' : '' }}"
                        href="{{ route('aturan_denda.index') }}">Master Aturan Denda</a>
                </div>
            </div>
        </li>

        @php
            $isPengaturanActive = request()->routeIs(['users.*', 'roles.*']);
        @endphp
        <li class="nav-item {{ $isPengaturanActive ? 'active' : '' }}">
            <a class="nav-link {{ !$isPengaturanActive ? 'collapsed' : '' }}" href="#" data-toggle="collapse"
                data-target="#collapsePengaturan" aria-expanded="{{ $isPengaturanActive ? 'true' : '' }}"
                aria-controls="collapsePengaturan">
                <i class="fas fa-fw fa-user-cog"></i>
                <span>Pengaturan</span>
            </a>
            <div id="collapsePengaturan" class="collapse {{ $isPengaturanActive ? 'show' : '' }}"
                aria-labelledby="headingPengaturan" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item {{ request()->routeIs('users.*') ? 'active' : '' }}"
                        href="{{ route('users.index') }}">Data Pengguna</a>
                    <a class="collapse-item {{ request()->routeIs('roles.*') ? 'active' : '' }}"
                        href="{{ route('roles.index') }}">Data Role</a>
                </div>
            </div>
        </li>
    @endif {{-- Akhir dari blok menu khusus Administrator --}}


    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
