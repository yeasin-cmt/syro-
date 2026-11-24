@extends('layouts.admin')

@section('title', $banner->exists ? 'Edit banner' : 'Add banner')

@section('content')
<div class="admin-card" style="max-width: 820px">
    <form action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="card-pad row g-3">
        @csrf
        @if ($banner->exists) @method('PUT') @endif
        <div class="col-md-8"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $banner->title) }}" required></div>
        <div class="col-md-4"><label class="form-label">Eyebrow (small label)</label><input name="eyebrow" class="form-control" value="{{ old('eyebrow', $banner->eyebrow) }}" placeholder="New season"></div>
        <div class="col-12"><label class="form-label">Subtitle</label><input name="subtitle" class="form-control" value="{{ old('subtitle', $banner->subtitle) }}"></div>
        <div class="col-md-6"><label class="form-label">Link</label><input name="link" class="form-control" value="{{ old('link', $banner->link) }}" placeholder="/shop?on_sale=1"></div>
        <div class="col-md-6"><label class="form-label">Button text</label><input name="button_text" class="form-control" value="{{ old('button_text', $banner->button_text) }}" placeholder="Shop now"></div>
        <div class="col-md-6">
            <label class="form-label">Position</label>
            <select name="position" class="form-select">
                @foreach (\App\Models\Banner::POSITIONS as $key => $label)<option value="{{ $key }}" @selected(old('position', $banner->position) === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Sort order</label><input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $banner->sort_order ?? 0) }}"></div>
        <div class="col-12">
            <label class="form-label">Image {{ $banner->exists ? '' : '(required)' }}</label>
            <div class="form-text mb-2">Hero: 1920×1000 recommended. Promo: 1200×900.</div>
            @if ($banner->image_url)<img src="{{ $banner->image_url }}" class="d-block mb-2" style="max-height:160px" alt="">@endif
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" {{ $banner->exists ? '' : 'required' }}>
        </div>
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $banner->is_active))><label class="form-check-label" for="is_active">Active</label></div></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save banner</button><a href="{{ route('admin.banners.index') }}" class="btn btn-light">Cancel</a></div>
    </form>
</div>
@endsection
