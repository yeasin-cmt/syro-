@extends('layouts.admin')

@section('title', 'Store settings')

@section('content')
@php
    $slots = [
        'logo' => ['Main logo', 'Header, mobile menu, invoices. Use a dark logo on a transparent background (PNG/SVG).', 'png,jpg,jpeg,webp,svg', false],
        'logo_light' => ['Logo for dark backgrounds', 'Footer and admin sidebar. Optional — without it the main logo is shown in white.', 'png,jpg,jpeg,webp,svg', true],
        'favicon' => ['Favicon', 'The small icon in the browser tab. Square PNG (512×512), SVG or ICO.', 'png,svg,ico', false],
    ];
@endphp

{{-- Logo & branding: upload / replace / delete --}}
<form action="{{ route('admin.settings.branding') }}" method="POST" enctype="multipart/form-data" class="mb-4" style="max-width: 980px">
    @csrf
    <div class="admin-card">
        <div class="card-head"><h2>Logo &amp; branding</h2></div>
        <div class="card-pad">
            <div class="row g-3">
                @foreach ($slots as $slot => [$label, $help, $types, $dark])
                    @php $path = $s[$slot]; @endphp
                    <div class="col-md-4">
                        <div class="brand-slot">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="small">{{ $label }}</strong>
                                @if ($path)<span class="badge text-bg-success">Uploaded</span>@else<span class="badge text-bg-light">Not set</span>@endif
                            </div>
                            <div class="brand-preview {{ $dark ? 'on-dark' : '' }}" id="preview-{{ $slot }}">
                                @if ($path)
                                    <img src="{{ media_url($path) }}" alt="{{ $label }}">
                                @elseif ($slot === 'logo_light' && $s['logo'])
                                    <img src="{{ media_url($s['logo']) }}" alt="" class="logo-invert" title="Main logo shown in white">
                                @else
                                    <span class="empty">{{ $slot === 'favicon' ? 'Default icon' : 'Store name shown as text' }}</span>
                                @endif
                            </div>
                            <p class="small text-muted mb-0">{{ $help }}</p>
                            <input type="file" name="{{ $slot }}" class="form-control form-control-sm @error($slot) is-invalid @enderror"
                                   accept="{{ collect(explode(',', $types))->map(fn ($t) => '.'.$t)->implode(',') }}" data-preview="#preview-{{ $slot }}">
                            @error($slot)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @if ($path)
                                <button type="submit" form="delete-{{ $slot }}" class="btn btn-sm btn-outline-danger mt-auto">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-3 align-items-end mt-1">
                <div class="col-md-4">
                    <label class="form-label" for="logo_height">Logo height in header: <span id="logo-height-value">{{ old('logo_height', $s['logo_height']) }}</span> px</label>
                    <input type="range" class="form-range" min="20" max="120" step="2" id="logo_height" name="logo_height"
                           value="{{ old('logo_height', $s['logo_height']) }}"
                           oninput="document.getElementById('logo-height-value').textContent = this.value">
                </div>
                <div class="col-md-8 text-md-end">
                    <button class="btn btn-primary"><i class="bi bi-upload"></i> Save logo settings</button>
                </div>
            </div>
            <p class="small text-muted mt-2 mb-0">Choose a file to upload or replace an image, then click <strong>Save logo settings</strong>. Max 2 MB (favicon 512 KB).</p>
        </div>
    </div>
</form>

@foreach (array_keys($slots) as $slot)
    @if ($s[$slot])
        <form id="delete-{{ $slot }}" action="{{ route('admin.settings.branding.destroy', $slot) }}" method="POST" class="d-none"
              data-confirm="Delete the {{ strtolower($slots[$slot][0]) }}? The site will show {{ $slot === 'favicon' ? 'the default icon' : 'the store name as text' }} instead.">
            @csrf @method('DELETE')
        </form>
    @endif
@endforeach

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
