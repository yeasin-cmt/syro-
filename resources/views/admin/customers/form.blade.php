@extends('layouts.admin')

@section('title', $user->exists ? 'Edit user' : 'Add user')

@section('content')
<div class="admin-card" style="max-width: 820px">
    <form action="{{ $user->exists ? route('admin.customers.update', $user) : route('admin.customers.store') }}" method="POST" class="card-pad row g-3" autocomplete="off">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="col-md-6"><label class="form-label">Full name</label><input name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $user->phone) }}"></div>
        <div class="col-md-6"><label class="form-label">City</label><input name="city" class="form-control" value="{{ old('city', $user->city) }}"></div>
        <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $user->address) }}"></div>
        <div class="col-md-6">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="customer" @selected(old('role', $user->role) === 'customer')>Customer — can shop</option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrator — full access to this panel</option>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $user->exists ? $user->is_active : true))><label class="form-check-label" for="is_active">Account active (can sign in)</label></div>
        </div>
        <div class="col-12"><hr class="my-1"><div class="small text-muted">{{ $user->exists ? 'Set a new password only if you want to reset it. Leave empty to keep the current password.' : 'Choose a password (at least 8 characters) and share it with the user securely.' }}</div></div>
        <div class="col-md-6"><label class="form-label">{{ $user->exists ? 'New password' : 'Password' }}</label><input type="password" name="password" class="form-control" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}></div>
        <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" autocomplete="new-password"></div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">{{ $user->exists ? 'Save changes' : 'Create account' }}</button>
            <a href="{{ $user->exists ? route('admin.customers.show', $user) : route('admin.customers.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
