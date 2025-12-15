@extends('adminlte::auth.passwords.email')

@section('auth_header', __('Reset Password'))

@section('auth_body')
    <p class="login-box-msg">
        {{ __('Forgot your password? No problem. Just enter your email address and we will email you a password reset link.') }}
    </p>

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="POST">
        @csrf

        <div class="input-group mb-3">
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                   value="{{ old('email') }}" placeholder="{{ __('Email') }}" required autofocus>
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

        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane mr-2"></i> {{ __('Send Password Reset Link') }}
                </button>
            </div>
        </div>
    </form>
@endsection

@section('auth_footer')
    <p class="mb-1">
        <a href="{{ route('login') }}">
            <i class="fas fa-sign-in-alt mr-1"></i> {{ __('Back to Login') }}
        </a>
    </p>
    @if (Route::has('register'))
        <p class="mb-0">
            <a href="{{ route('register') }}">
                <i class="fas fa-user-plus mr-1"></i> {{ __('Register a new account') }}
            </a>
        </p>
    @endif
@endsection