@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
<div class="auth-wrap">
    <div class="container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <span class="eyebrow">Welcome back</span>
                <h1 class="h2 mt-2">Sign in</h1>
            </div>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label for="password" class="form-label">Password</label>
                        @if (Route::has('password.request'))
                            <a class="small text-muted" href="{{ route('password.request') }}">Forgot?</a>
                        @endif
                    </div>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small" for="remember">Keep me signed in</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
            </form>
            <p class="text-center small text-muted mt-4 mb-0">New to SYRO? <a href="{{ route('register') }}" class="text-dark text-decoration-underline">Create an account</a></p>
        </div>
    </div>
</div>
@endsection
