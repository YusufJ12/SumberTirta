@extends('layouts.master')

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h3 class="m-0 font-weight-bold text-primary">Tambah Role</h3>
            </div>
            <div class="card-body">
                <form class="form-horizontal" action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    <div class="form-group row">
                        <label for="nm_roles" class="col-md-2 control-label">Nama Role</label>
                        <div class="col-md-10">
                            <input type="text" name="nm_roles" id="nm_roles" class="form-control @error('nm_roles') is-invalid @enderror" placeholder="Masukkan Nama Role" value="{{ old('nm_roles') }}">
                            @error('nm_roles')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group row mb-0">
                        <div class="col-md-6 offset-md-2">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary">Kembali</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
