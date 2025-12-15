@extends('adminlte::auth.passwords.confirm')

@section('auth_header', __('Confirm Password'))

@section('auth_body')
    <p class="login-box-msg">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form action="{{ route('password.confirm') }}" method="POST">
        @csrf

        <div class="input-group mb-3">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                   placeholder="{{ __('Password') }}" required autocomplete="current-password">
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-check mr-2"></i> {{ __('Confirm') }}
                </button>
            </div>
        </div>
    </form>
@endsection

@section('auth_footer')
    <p class="mb-0">
        <a href="{{ route('dashboard') }}">
            <i class="fas fa-arrow-left mr-1"></i> {{ __('Back to Dashboard') }}
        </a>
    </p>
@endsection