@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="small text-muted">Edit price, sale price, stock and visibility right in the table, then press <i class="bi bi-check-lg"></i> to save that row.</div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.products.trash') }}" class="btn btn-light"><i class="bi bi-trash3 me-1"></i>Trash @if ($trashCount)<span class="badge text-bg-secondary">{{ $trashCount }}</span>@endif</a>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add product</a>
    </div>
</div>

<div class="admin-card">
    <div class="card-head flex-wrap gap-2">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name or SKU" style="width: 200px">
            <select name="category" class="form-select" style="width: 180px">
                <option value="">All categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <select name="status" class="form-select" style="width: 140px">
                <option value="">Any status</option>
                <option value="active" @selected(request('status') === 'active')>Visible</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Hidden</option>
            </select>
            <select name="stock" class="form-select" style="width: 150px">
                <option value="">Any stock</option>
                <option value="low" @selected(request('stock') === 'low')>Low (1–5)</option>
                <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
            </select>
            <button class="btn btn-outline-primary">Filter</button>
            @if (request()->hasAny(['q', 'category', 'status', 'stock']))<a href="{{ route('admin.products.index') }}" class="btn btn-light">Clear</a>@endif
        </form>
    </div>

    {{-- Bulk actions (checkboxes in the table belong to this form via form="bulk-form") --}}
    <form id="bulk-form" action="{{ route('admin.products.bulk') }}" method="POST" class="card-pad border-bottom d-flex flex-wrap align-items-center gap-2 bg-light" data-confirm-bulk>
        @csrf
        <span class="small fw-semibold me-1"><span data-selected-count>0</span> selected</span>
        <select name="action" class="form-select form-select-sm" style="width: 230px" required>
            <option value="">Choose bulk action…</option>
            <optgroup label="Visibility">
                <option value="activate">Show in store</option>
                <option value="deactivate">Hide from store</option>
                <option value="feature">Mark as featured</option>
                <option value="unfeature">Remove featured</option>
            </optgroup>
            <optgroup label="Pricing">
                <option value="increase">Increase price by %</option>
                <option value="decrease">Decrease price by %</option>
                <option value="sale">Put on sale: % off</option>
                <option value="clear_sale">Remove sale price</option>
            </optgroup>
            <optgroup label="Delete">
                <option value="delete">Move to trash</option>
            </optgroup>
        </select>
        <div class="input-group input-group-sm" style="width: 110px">
            <input type="number" name="percent" min="1" max="90" step="1" class="form-control" placeholder="%">
            <span class="input-group-text">%</span>
        </div>
        <button class="btn btn-sm btn-primary">Apply</button>
    </form>

    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all"></th>
                    <th></th><th>Product</th><th>Price (BDT)</th><th>Sale (BDT)</th><th>Stock</th><th>Visible</th><th>Sold</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $p)
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="{{ $p->id }}" form="bulk-form" class="form-check-input" data-row-check aria-label="Select {{ $p->name }}"></td>
                        <td style="width: 56px"><img src="{{ $p->image_url }}" class="admin-thumb" alt=""></td>
                        <td style="min-width: 200px">
                            <a href="{{ route('admin.products.edit', $p) }}" class="fw-medium">{{ $p->name }}</a>
                            @if ($p->is_featured)<i class="bi bi-star-fill text-accent" title="Featured"></i>@endif
                            <div class="small text-muted">{{ $p->sku }} · {{ $p->category?->name }} @if ($p->variants_count) · {{ $p->variants_count }} variants @endif</div>
                            <form id="quick-{{ $p->id }}" action="{{ route('admin.products.quick', $p) }}" method="POST">@csrf @method('PATCH')</form>
                        </td>
                        <td><input type="number" name="price" form="quick-{{ $p->id }}" value="{{ (float) $p->price }}" min="0" step="1" class="form-control form-control-sm" style="width: 95px" required aria-label="Price"></td>
                        <td><input type="number" name="sale_price" form="quick-{{ $p->id }}" value="{{ $p->sale_price !== null ? (float) $p->sale_price : '' }}" min="0" step="1" class="form-control form-control-sm {{ $p->is_on_sale ? 'border-danger' : '' }}" style="width: 95px" placeholder="—" aria-label="Sale price"></td>
                        <td>
                            @if ($p->variants_count)
                                <span class="badge {{ $p->stock == 0 ? 'text-bg-danger' : ($p->stock <= 5 ? 'text-bg-warning' : 'text-bg-light') }}" title="Sum of variant stock — edit in the product page">{{ $p->stock }}</span>
                            @else
                                <input type="number" name="stock" form="quick-{{ $p->id }}" value="{{ $p->stock }}" min="0" step="1" class="form-control form-control-sm" style="width: 75px" aria-label="Stock">
                            @endif
                        </td>
                        <td>
                            <div class="form-check form-switch m-0">
                                <input type="hidden" name="is_active" value="0" form="quick-{{ $p->id }}">
                                <input type="checkbox" name="is_active" value="1" form="quick-{{ $p->id }}" class="form-check-input" @checked($p->is_active) aria-label="Visible">
                            </div>
                        </td>
                        <td>{{ $p->sold_count }}</td>
                        <td class="text-end text-nowrap">
                            <button type="submit" form="quick-{{ $p->id }}" class="btn btn-sm btn-success" title="Save row"><i class="bi bi-check-lg"></i></button>
                            <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-sm btn-light" title="Edit everything"><i class="bi bi-pencil"></i></a>
                            <a href="{{ route('product.show', $p) }}" target="_blank" class="btn btn-sm btn-light" title="View in store"><i class="bi bi-eye"></i></a>
                            <form action="{{ route('admin.products.duplicate', $p) }}" method="POST" class="d-inline">@csrf
                                <button class="btn btn-sm btn-light" title="Duplicate"><i class="bi bi-copy"></i></button>
                            </form>
                            <form action="{{ route('admin.products.destroy', $p) }}" method="POST" class="d-inline" data-confirm="Move “{{ $p->name }}” to trash? You can restore it later.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">No products found. <a href="{{ route('admin.products.create') }}">Add your first product</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-pad">{{ $products->links() }}</div>
</div>
@endsection
