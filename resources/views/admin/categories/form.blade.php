@extends('layouts.admin')

@section('title', $category->exists ? 'Edit category' : 'Add category')

@section('content')
<div class="admin-card" style="max-width: 760px">
    <form action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="card-pad row g-3">
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <div class="col-md-8"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $category->name) }}" required></div>
        <div class="col-md-4"><label class="form-label">Sort order</label><input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}"></div>
        <div class="col-12">
            <label class="form-label">Parent category</label>
            <select name="parent_id" class="form-select">
                <option value="">None (top level)</option>
                @foreach ($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id) == $p->id)>{{ $p->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control">{{ old('description', $category->description) }}</textarea></div>
        <div class="col-12">
            <label class="form-label">Image (used for homepage tiles)</label>
            @if ($category->image_url)<img src="{{ $category->image_url }}" class="d-block mb-2" style="height:120px" alt="">@endif
            <input type="file" name="image" class="form-control" accept="image/*" data-preview="#cat-preview">
            <div id="cat-preview" class="mt-2"></div>
        </div>
        <div class="col-12 d-flex gap-4">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $category->is_active))><label class="form-check-label" for="is_active">Active</label></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $category->is_featured))><label class="form-check-label" for="is_featured">Featured on homepage</label></div>
        </div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary">Save</button><a href="{{ route('admin.categories.index') }}" class="btn btn-light">Cancel</a></div>
    </form>
</div>
@endsection
