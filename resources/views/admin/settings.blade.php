@extends('layouts.admin')

@section('title', 'Store settings')

@section('content')
@include('admin.partials.branding')

<form action="{{ route('admin.settings.update') }}" method="POST" style="max-width: 980px">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="admin-card h-100">
                <div class="card-head"><h2>Store</h2></div>
                <div class="card-pad row g-3">
                    <div class="col-12"><label class="form-label">Store name</label><input name="store_name" class="form-control" value="{{ old('store_name', $s['store_name']) }}" required></div>
                    <div class="col-12"><label class="form-label">Tagline</label><input name="store_tagline" class="form-control" value="{{ old('store_tagline', $s['store_tagline']) }}"></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="store_email" class="form-control" value="{{ old('store_email', $s['store_email']) }}" required></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input name="store_phone" class="form-control" value="{{ old('store_phone', $s['store_phone']) }}" required></div>
                    <div class="col-12"><label class="form-label">Address</label><input name="store_address" class="form-control" value="{{ old('store_address', $s['store_address']) }}"></div>
                    <div class="col-12"><label class="form-label">Announcement bar text</label><input name="announcement_text" class="form-control" value="{{ old('announcement_text', $s['announcement_text']) }}" placeholder="Leave empty to hide"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="admin-card mb-3">
                <div class="card-head"><h2>Delivery charges (BDT)</h2></div>
                <div class="card-pad row g-3">
                    <div class="col-md-4"><label class="form-label">Inside Dhaka</label><input type="number" min="0" step="1" name="shipping_inside_dhaka" class="form-control" value="{{ old('shipping_inside_dhaka', $s['shipping_inside_dhaka']) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Outside Dhaka</label><input type="number" min="0" step="1" name="shipping_outside_dhaka" class="form-control" value="{{ old('shipping_outside_dhaka', $s['shipping_outside_dhaka']) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Free over</label><input type="number" min="0" step="1" name="free_shipping_threshold" class="form-control" value="{{ old('free_shipping_threshold', $s['free_shipping_threshold']) }}" placeholder="0 = never"></div>
                </div>
            </div>
            <div class="admin-card">
                <div class="card-head"><h2>Payment methods</h2></div>
                <div class="card-pad row g-3">
                    <div class="col-12">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="cod_enabled" value="1" id="cod_enabled" @checked(old('cod_enabled', $s['cod_enabled']) == '1')><label class="form-check-label" for="cod_enabled">Cash on Delivery</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="bkash_enabled" value="1" id="bkash_enabled" @checked(old('bkash_enabled', $s['bkash_enabled']) == '1')><label class="form-check-label" for="bkash_enabled">bKash (Send Money + Transaction ID)</label></div>
                    </div>
                    <div class="col-12"><label class="form-label">bKash number customers pay to</label><input name="bkash_number" class="form-control" value="{{ old('bkash_number', $s['bkash_number']) }}" placeholder="01XXXXXXXXX"></div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="admin-card">
                <div class="card-head"><h2>Social links</h2></div>
                <div class="card-pad row g-3">
                    @foreach (['facebook', 'instagram', 'youtube', 'tiktok'] as $net)
                        <div class="col-md-6"><label class="form-label">{{ ucfirst($net) }}</label><input type="url" name="social_{{ $net }}" class="form-control" value="{{ old('social_'.$net, $s['social_'.$net]) }}" placeholder="https://"></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <button class="btn btn-primary mt-3">Save settings</button>
</form>
@endsection
