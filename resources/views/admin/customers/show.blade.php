@extends('layouts.admin')

@section('title', $customer->name)

@section('content')
<div class="row g-3">
    <div class="col-xl-4">
        <div class="admin-card">
            <div class="card-head"><h2>Profile</h2><span class="badge {{ $customer->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $customer->is_active ? 'Active' : 'Deactivated' }}</span></div>
            <div class="card-pad small">
                <p class="mb-1 fs-6 fw-semibold">{{ $customer->name }}</p>
                <p class="mb-1"><a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a></p>
                <p class="mb-1">{{ $customer->phone ?: 'No phone' }}</p>
                <p class="text-muted">{{ $customer->address }} {{ $customer->city }}</p>
                <p class="text-muted mb-1">Joined {{ $customer->created_at->format('d M Y') }}</p>
                <p class="mb-3"><span class="badge {{ $customer->isAdmin() ? 'text-bg-dark' : 'text-bg-light' }}">{{ $customer->isAdmin() ? 'Administrator' : 'Customer' }}</span></p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit / reset password</a>
                    @if ($customer->id !== auth()->id())
                        <form action="{{ route('admin.customers.toggle', $customer) }}" method="POST" data-confirm="{{ $customer->is_active ? 'Deactivate this account? They will not be able to sign in.' : 'Re-activate this account?' }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm {{ $customer->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">{{ $customer->is_active ? 'Deactivate' : 'Activate' }}</button>
                        </form>
                        <form action="{{ route('admin.customers.destroy', $customer) }}" method="POST" data-confirm="Delete this account permanently? Their orders will be kept.">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="admin-card">
            <div class="card-head"><h2>Orders</h2></div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse ($orders as $o)
                            <tr>
                                <td><a href="{{ route('admin.orders.show', $o) }}">{{ $o->order_number }}</a></td>
                                <td class="small">{{ $o->created_at->format('d M Y') }}</td>
                                <td>{{ $o->items_count }}</td>
                                <td><span class="badge text-bg-{{ $o->status_color }}">{{ $o->status_label }}</span></td>
                                <td class="text-end">{{ money($o->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-pad">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
