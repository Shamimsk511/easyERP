@extends('adminlte::auth.verify')

@section('auth_header', __('Verify Your Email Address'))

@section('auth_body')
    <p class="login-box-msg">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success" role="alert">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="row">
        <div class="col-12 mb-3">
            <form action="{{ route('verification.send') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-envelope mr-2"></i> {{ __('Resend Verification Email') }}
                </button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-sign-out-alt mr-2"></i> {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
@endsection