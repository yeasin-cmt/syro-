@extends('layouts.admin')

@section('title', $brand->exists ? 'Edit brand' : 'Add brand')

@section('content')
<div class="admin-card" style="max-width: 640px">
    <form action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" method="POST" enctype="multipart/form-data" class="card-pad row g-3">
        @csrf
        @if ($brand->exists) @method('PUT') @endif
        <div class="col-12"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $brand->name) }}" required></div>
        <div class="col-12">
            <label class="form-label">Logo (optional)</label>
            @if ($brand->logo_url)<img src="{{ $brand->logo_url }}" class="d-block mb-2" style="height:48px" alt="">@endif
            <input type="file" name="logo" class="form-control" accept="image/*">
        </div>
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $brand->is_active))><label class="form-check-label" for="is_active">Active</label></div></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save</button><a href="{{ route('admin.brands.index') }}" class="btn btn-light">Cancel</a></div>
    </form>
</div>
@endsection
