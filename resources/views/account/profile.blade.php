@extends('account._layout')

@section('title', 'Profile')
@section('account_title', 'Profile & password')

@section('account_content')
<div class="panel mb-4">
    <div class="panel-title">Personal details</div>
    <form action="{{ route('account.profile.update') }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Full name</label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">Mobile</label>
                <input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="01XXXXXXXXX">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="city">City</label>
                <input id="city" name="city" class="form-control" value="{{ old('city', $user->city) }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="address">Default delivery address</label>
                <input id="address" name="address" class="form-control" value="{{ old('address', $user->address) }}">
            </div>
        </div>
        <button class="btn btn-primary mt-4">Save details</button>
    </form>
</div>

<div class="panel">
    <div class="panel-title">Change password</div>
    <form action="{{ route('account.password.update') }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="current_password">Current password</label>
                <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="password">New password</label>
                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="password_confirmation">Confirm</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
            </div>
        </div>
        <button class="btn btn-outline-primary mt-4">Update password</button>
    </form>
</div>
@endsection
