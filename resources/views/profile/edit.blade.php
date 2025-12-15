@extends('adminlte::page')

@section('title', 'Profile')

@section('content_header')
    <h1>Profile Settings</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-6">
        {{-- Profile Information --}}
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user mr-2"></i> Profile Information
                </h3>
            </div>
            <form method="POST" action="{{ route('profile.update') }}" id="profile-form">
                @csrf
                @method('PATCH')
                <div class="card-body">
                    <p class="text-muted">Update your account's profile information and email address.</p>
                    
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" 
                               class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $user->name) }}" required autofocus>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Your email address is unverified.
                            <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 align-baseline">
                                    Click here to re-send the verification email.
                                </button>
                            </form>
                        </div>

                        @if (session('status') === 'verification-link-sent')
                            <div class="alert alert-success">
                                A new verification link has been sent to your email address.
                            </div>
                        @endif
                    @endif
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                    
                    @if (session('status') === 'profile-updated')
                        <span class="text-success ml-3">
                            <i class="fas fa-check mr-1"></i> Saved!
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        {{-- Update Password --}}
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-lock mr-2"></i> Update Password
                </h3>
            </div>
            <form method="POST" action="{{ route('password.update') }}" id="password-form">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <p class="text-muted">Ensure your account is using a long, random password to stay secure.</p>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" id="current_password" 
                               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                               autocomplete="current-password">
                        @error('current_password', 'updatePassword')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" 
                               class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
                               autocomplete="new-password">
                        @error('password', 'updatePassword')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" 
                               class="form-control" autocomplete="new-password">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key mr-2"></i> Update Password
                    </button>
                    
                    @if (session('status') === 'password-updated')
                        <span class="text-success ml-3">
                            <i class="fas fa-check mr-1"></i> Password Updated!
                        </span>
                    @endif
                </div>
            </form>
        </div>

        {{-- Delete Account --}}
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-trash mr-2"></i> Delete Account
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Once your account is deleted, all of its resources and data will be permanently deleted. 
                    Before deleting your account, please download any data or information that you wish to retain.
                </p>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteAccountModal">
                    <i class="fas fa-trash mr-2"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Account Modal --}}
<div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Confirm Account Deletion
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                    <p class="text-danger font-weight-bold">All your data will be permanently deleted!</p>
                    
                    <div class="form-group">
                        <label for="delete_password">Enter your password to confirm:</label>
                        <input type="password" name="password" id="delete_password" 
                               class="form-control @error('password', 'userDeletion') is-invalid @enderror" 
                               placeholder="Password" required>
                        @error('password', 'userDeletion')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-2"></i> Yes, Delete My Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@push('js')
<script>
$(document).ready(function() {
    // Show success messages with SweetAlert if available
    @if (session('status') === 'profile-updated')
        Swal.fire({
            icon: 'success',
            title: 'Profile Updated!',
            text: 'Your profile information has been saved.',
            timer: 2000,
            showConfirmButton: false
        });
    @endif
    
    @if (session('status') === 'password-updated')
        Swal.fire({
            icon: 'success',
            title: 'Password Changed!',
            text: 'Your password has been updated successfully.',
            timer: 2000,
            showConfirmButton: false
        });
    @endif
});
</script>
@endpush