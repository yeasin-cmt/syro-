@extends('layouts.admin')

@section('title', $product->exists ? 'Edit product' : 'Add product')

@php
    $variantRows = old('variants', $product->exists ? $product->variants->map(fn ($v) => $v->only(['id', 'size', 'color', 'color_hex', 'sku', 'stock', 'price']))->all() : []);
@endphp

@section('content')
<form action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="admin-card mb-3">
                <div class="card-head"><h2>Details</h2>@if ($product->exists)<a href="{{ route('product.show', $product) }}" target="_blank" class="small">View in store <i class="bi bi-box-arrow-up-right"></i></a>@endif</div>
                <div class="card-pad row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SKU</label>
                        <input name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Short description</label>
                        <input name="short_description" class="form-control" maxlength="500" value="{{ old('short_description', $product->short_description) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Full description</label>
                        <textarea name="description" rows="7" class="form-control">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card mb-3">
                <div class="card-head"><h2>Images</h2><span class="small text-muted">JPG, PNG or WebP, max 4 MB each. First image is the main image.</span></div>
                <div class="card-pad">
                    @if ($product->exists && $product->images->isNotEmpty())
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            @foreach ($product->images as $img)
                                <div class="text-center">
                                    <img src="{{ $img->url }}" alt="" style="width:90px;height:112px;object-fit:cover" class="d-block mb-1 {{ $loop->first ? 'border border-2 border-dark' : '' }}">
                                    @if ($loop->first)
                                        <div class="small fw-semibold">Main</div>
                                    @else
                                        <button type="submit" form="main-image-{{ $img->id }}" class="btn btn-sm btn-link p-0 d-block mx-auto">Make main</button>
                                    @endif
                                    <button type="submit" form="delete-image-{{ $img->id }}" class="btn btn-sm btn-link text-danger p-0">Remove</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple data-preview="#image-preview">
                    <div id="image-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
                </div>
            </div>

            <div class="admin-card mb-3">
                <div class="card-head"><h2>Variants (size / colour)</h2><button type="button" class="btn btn-sm btn-outline-primary" id="add-variant"><i class="bi bi-plus"></i> Add variant</button></div>
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead><tr><th>Size</th><th>Colour</th><th>Swatch</th><th>SKU</th><th>Stock</th><th>Price override</th><th></th></tr></thead>
                        <tbody id="variant-rows">
                            @foreach ($variantRows as $i => $v)
                                @include('admin.products._variant-row', ['i' => $i, 'v' => $v])
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-pad pt-0 small text-muted">When variants exist, total product stock is calculated automatically from variant stock. Leave price override empty to use the product price.</div>
                <template id="variant-template">@include('admin.products._variant-row', ['i' => '__INDEX__', 'v' => []])</template>
            </div>

            <div class="admin-card">
                <div class="card-head"><h2>SEO</h2></div>
                <div class="card-pad row g-3">
                    <div class="col-12"><label class="form-label">Meta title</label><input name="meta_title" class="form-control" value="{{ old('meta_title', $product->meta_title) }}"></div>
                    <div class="col-12"><label class="form-label">Meta description</label><textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $product->meta_description) }}</textarea></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="admin-card mb-3">
                <div class="card-head"><h2>Visibility</h2></div>
                <div class="card-pad">
                    @foreach (['is_active' => 'Visible in store', 'is_featured' => 'Featured product', 'is_new_arrival' => 'New arrival', 'is_trending' => 'Trending'] as $flag => $label)
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="{{ $flag }}" value="1" id="{{ $flag }}" @checked(old($flag, $product->$flag))>
                            <label class="form-check-label" for="{{ $flag }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="admin-card mb-3">
                <div class="card-head"><h2>Pricing &amp; stock</h2></div>
                <div class="card-pad row g-3">
                    <div class="col-6"><label class="form-label">Price (BDT)</label><input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $product->price) }}" required></div>
                    <div class="col-6"><label class="form-label">Sale price (BDT)</label><input type="number" step="0.01" min="0" name="sale_price" class="form-control" value="{{ old('sale_price', $product->sale_price) }}"></div>
                    <div class="col-12">
                        <label class="form-label">Stock</label>
                        <input type="number" min="0" name="stock" class="form-control" value="{{ old('stock', $product->stock ?? 0) }}" @if (count($variantRows)) readonly title="Calculated from variants" @endif>
                    </div>
                </div>
            </div>

            <div class="admin-card mb-3">
                <div class="card-head"><h2>Organisation</h2></div>
                <div class="card-pad row g-3">
                    <div class="col-12">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select…</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->parent ? $c->parent->name.' › ' : '' }}{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" class="form-select">
                            <option value="">No brand</option>
                            @foreach ($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id) == $b->id)>{{ $b->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary w-100 mb-2">{{ $product->exists ? 'Save changes' : 'Create product' }}</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-light w-100">Cancel</a>
        </div>
    </div>
</form>

{{-- Image delete forms live outside the main form (forms cannot be nested). --}}
@if ($product->exists)
    @foreach ($product->images as $img)
        <form id="delete-image-{{ $img->id }}" action="{{ route('admin.products.images.destroy', [$product, $img]) }}" method="POST" data-confirm="Remove this image?">
            @csrf @method('DELETE')
        </form>
        <form id="main-image-{{ $img->id }}" action="{{ route('admin.products.images.main', [$product, $img]) }}" method="POST">
            @csrf @method('PATCH')
        </form>
    @endforeach

    <div class="admin-card mt-3" style="max-width: 100%">
        <div class="card-head"><h2>More actions</h2></div>
        <div class="card-pad d-flex flex-wrap gap-2">
            <form action="{{ route('admin.products.duplicate', $product) }}" method="POST">@csrf
                <button class="btn btn-outline-primary"><i class="bi bi-copy me-1"></i>Duplicate product</button>
            </form>
            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" data-confirm="Move “{{ $product->name }}” to trash? You can restore it from the trash later.">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete product</button>
            </form>
        </div>
    </div>
@endif
@endsection
