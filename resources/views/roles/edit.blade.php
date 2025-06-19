@extends('layouts.master')

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                    <div class="card-header">
                        <h3 class="m-0 font-weight-bold text-primary">Edit Role</h3>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('roles.update', $role->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <label for="nm_roles" class="col-md-2 col-form-label">{{ __('Role Name') }}</label>
                            
                                <div class="col-md-10">
                                    <input id="nm_roles" type="text" class="form-control @error('nm_roles') is-invalid @enderror" name="nm_roles" value="{{ old('nm_roles', $role->nm_roles) }}" required autocomplete="nm_roles" autofocus>
                            
                                    @error('nm_roles')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            

                            <div class="form-group row mb-0">
                                <div class="col-md-6 offset-md-2">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Update Role') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
            </div>
        </div>
    </div>
@endsection
