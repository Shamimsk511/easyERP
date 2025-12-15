@extends('adminlte::auth.passwords.reset')

@section('auth_header', __('Reset Password'))

@section('auth_body')
    <p class="login-box-msg">
        {{ __('Enter your new password below.') }}
    </p>

    <form action="{{ route('password.store') }}" method="POST">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="input-group mb-3">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                   value="{{ old('email', $request->email) }}" placeholder="{{ __('Email') }}" required autofocus autocomplete="username">
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope"></span>
                </div>
            </div>
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                   placeholder="{{ __('Password') }}" required autocomplete="new-password">
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

        <div class="input-group mb-3">
            <input type="password" name="password_confirmation" class="form-control" 
                   placeholder="{{ __('Confirm Password') }}" required autocomplete="new-password">
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-key mr-2"></i> {{ __('Reset Password') }}
                </button>
            </div>
        </div>
    </form>
@endsection

@section('auth_footer')
    <p class="mb-0">
        <a href="{{ route('login') }}">
            <i class="fas fa-sign-in-alt mr-1"></i> {{ __('Back to Login') }}
        </a>
    </p>
@endsection