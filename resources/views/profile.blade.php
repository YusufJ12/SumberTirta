@extends('layouts.master')

@section('title', 'Profil Saya')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">{{ __('Profil Pengguna') }}</h1>

        @if (session('success'))
            <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-left-danger" role="alert">
                <ul class="pl-4 my-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            {{-- Kolom Kiri untuk Foto Profil --}}
            <div class="col-lg-4 order-lg-2">
                <div class="card shadow mb-4">
                    <div class="card-profile-image mt-4 d-flex justify-content-center">
                        {{-- Avatar Inisial --}}
                        <div class="rounded-circle avatar-lg d-flex align-items-center justify-content-center bg-primary text-white font-weight-bold"
                            style="font-size: 60px; height: 180px; width: 180px;">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="text-center">
                                    <h5 class="font-weight-bold">{{ Auth::user()->name }}</h5>
                                    <p class="text-muted">{{ Auth::user()->role->nm_roles ?? 'User' }}</p>
                                    {{-- Asumsi ada relasi 'role' di User Model --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan untuk Form Edit --}}
            <div class="col-lg-8 order-lg-1">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Akun Saya</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('profile.update') }}" autocomplete="off">
                            @csrf
                            @method('PUT')

                            <h6 class="heading-small text-muted mb-4">Informasi Pengguna</h6>

                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-control-label" for="name">Nama<span
                                                    class="small text-danger">*</span></label>
                                            <input type="text" id="name" class="form-control" name="name"
                                                placeholder="Nama Lengkap" value="{{ old('name', $user->name) }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="form-control-label" for="username">Username<span
                                                    class="small text-danger">*</span></label>
                                            <input type="text" id="username" class="form-control" name="username"
                                                placeholder="Username (huruf kecil)"
                                                value="{{ old('username', $user->username) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label class="form-control-label" for="email">Alamat Email<span
                                                    class="small text-danger">*</span></label>
                                            <input type="email" id="email" class="form-control" name="email"
                                                placeholder="email@example.com" value="{{ old('email', $user->email) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="heading-small text-muted mb-4">Ubah Password</h6>

                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label class="form-control-label" for="current_password">Password Saat
                                                Ini</label>
                                            <input type="password" id="current_password" class="form-control"
                                                name="current_password" placeholder="Password saat ini">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label class="form-control-label" for="new_password">Password Baru</label>
                                            <input type="password" id="new_password" class="form-control"
                                                name="new_password" placeholder="Password baru">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label class="form-control-label" for="password_confirmation">Konfirmasi
                                                Password</label>
                                            <input type="password" id="password_confirmation" class="form-control"
                                                name="password_confirmation" placeholder="Konfirmasi password">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pl-lg-4">
                                <div class="row">
                                    <div class="col text-right">
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
