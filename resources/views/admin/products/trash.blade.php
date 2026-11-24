@extends('layouts.admin')

@section('title', 'Product trash')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="small text-muted">Deleted products are kept here. Restore them, or delete permanently (past orders keep the product name and price).</div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-light">Back to products</a>
</div>
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th></th><th>Product</th><th>Price</th><th>Deleted</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($products as $p)
                    <tr>
                        <td style="width:56px"><img src="{{ $p->image_url }}" class="admin-thumb" alt=""></td>
                        <td class="fw-medium">{{ $p->name }}<div class="small text-muted">{{ $p->sku }} · {{ $p->category?->name }}</div></td>
                        <td>{{ money($p->price) }}</td>
                        <td class="small">{{ $p->deleted_at->format('d M Y, h:i A') }}</td>
                        <td class="text-end text-nowrap">
                            <form action="{{ route('admin.products.restore', $p->id) }}" method="POST" class="d-inline">@csrf @method('PATCH')
                                <button class="btn btn-sm btn-success"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button>
                            </form>
                            <form action="{{ route('admin.products.force-delete', $p->id) }}" method="POST" class="d-inline" data-confirm="Permanently delete “{{ $p->name }}” and its images? This cannot be undone.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete forever</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">Trash is empty.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-pad">{{ $products->links() }}</div>
</div>
@endsection
