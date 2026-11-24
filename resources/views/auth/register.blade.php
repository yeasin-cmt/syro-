@extends('layouts.app')

@section('title', 'Create account')

@section('content')
<div class="auth-wrap">
    <div class="container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <span class="eyebrow">Join SYRO</span>
                <h1 class="h2 mt-2">Create account</h1>
                <p class="small text-muted mb-0">Track orders, save favourites and check out faster.</p>
            </div>
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Full name</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">At least 8 characters.</div>
                </div>
                <div class="mb-4">
                    <label for="password-confirm" class="form-label">Confirm password</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Create account</button>
            </form>
            <p class="text-center small text-muted mt-4 mb-0">Already have an account? <a href="{{ route('login') }}" class="text-dark text-decoration-underline">Sign in</a></p>
        </div>
    </div>
</div>
@endsection
