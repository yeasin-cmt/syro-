@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
<div class="auth-wrap">
    <div class="container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <span class="eyebrow">Account help</span>
                <h1 class="h2 mt-2">Reset password</h1>
                <p class="small text-muted mb-0">Enter your email and we'll send you a reset link.</p>
            </div>
            @if (session('status'))
                <div class="alert alert-success small" role="alert">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-4">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary w-100">Send reset link</button>
            </form>
            <p class="text-center small mt-4 mb-0"><a href="{{ route('login') }}" class="text-muted">Back to sign in</a></p>
        </div>
    </div>
</div>
@endsection
