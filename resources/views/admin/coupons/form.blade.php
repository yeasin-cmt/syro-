@extends('layouts.admin')

@section('title', $coupon->exists ? 'Edit coupon' : 'New coupon')

@section('content')
<div class="admin-card" style="max-width: 760px">
    <form action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" method="POST" class="card-pad row g-3">
        @csrf
        @if ($coupon->exists) @method('PUT') @endif
        <div class="col-md-6"><label class="form-label">Code</label><input name="code" class="form-control text-uppercase" value="{{ old('code', $coupon->code) }}" placeholder="WELCOME10" required></div>
        <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="percent" @selected(old('type', $coupon->type) === 'percent')>Percentage</option>
                <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed (৳)</option>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Value</label><input type="number" step="0.01" min="1" name="value" class="form-control" value="{{ old('value', $coupon->value) }}" required></div>
        <div class="col-md-4"><label class="form-label">Minimum order (৳)</label><input type="number" step="0.01" min="0" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', $coupon->min_order_amount) }}"></div>
        <div class="col-md-4"><label class="form-label">Max discount (৳)</label><input type="number" step="0.01" min="0" name="max_discount" class="form-control" value="{{ old('max_discount', $coupon->max_discount) }}" placeholder="No cap"></div>
        <div class="col-md-4"><label class="form-label">Usage limit</label><input type="number" min="1" name="usage_limit" class="form-control" value="{{ old('usage_limit', $coupon->usage_limit) }}" placeholder="Unlimited"></div>
        <div class="col-md-6"><label class="form-label">Starts</label><input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}"></div>
        <div class="col-md-6"><label class="form-label">Expires</label><input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}"></div>
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $coupon->is_active))><label class="form-check-label" for="is_active">Active</label></div></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save coupon</button><a href="{{ route('admin.coupons.index') }}" class="btn btn-light">Cancel</a></div>
    </form>
</div>
@endsection
