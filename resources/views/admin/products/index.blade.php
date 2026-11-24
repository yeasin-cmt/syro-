@extends('layouts.admin')

@section('title', 'Products')

@section('content')
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
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Hidden</option>
            </select>
            <select name="stock" class="form-select" style="width: 150px">
                <option value="">Any stock</option>
                <option value="low" @selected(request('stock') === 'low')>Low (1–5)</option>
                <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
            </select>
            <button class="btn btn-outline-primary">Filter</button>
        </form>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add product</a>
    </div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th></th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Sold</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($products as $p)
                    <tr>
                        <td style="width: 60px"><img src="{{ $p->image_url }}" class="admin-thumb" alt=""></td>
                        <td>
                            <a href="{{ route('admin.products.edit', $p) }}" class="fw-medium">{{ $p->name }}</a>
                            <div class="small text-muted">{{ $p->sku }} @if ($p->brand) · {{ $p->brand->name }} @endif @if ($p->variants_count) · {{ $p->variants_count }} variants @endif</div>
                        </td>
                        <td class="small">{{ $p->category?->name }}</td>
                        <td>@if ($p->is_on_sale)<span class="text-danger">{{ money($p->sale_price) }}</span> <del class="small text-muted">{{ money($p->price) }}</del>@else{{ money($p->price) }}@endif</td>
                        <td><span class="badge {{ $p->stock == 0 ? 'text-bg-danger' : ($p->stock <= 5 ? 'text-bg-warning' : 'text-bg-light') }}">{{ $p->stock }}</span></td>
                        <td>{{ $p->sold_count }}</td>
                        <td>
                            <span class="badge {{ $p->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $p->is_active ? 'Active' : 'Hidden' }}</span>
                            @if ($p->is_featured)<i class="bi bi-star-fill text-accent" title="Featured"></i>@endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('product.show', $p) }}" target="_blank" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.products.destroy', $p) }}" method="POST" class="d-inline" data-confirm="Delete “{{ $p->name }}”? It will be removed from the store but kept in order history.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-pad">{{ $products->links() }}</div>
</div>
@endsection
