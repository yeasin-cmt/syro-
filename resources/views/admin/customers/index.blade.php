@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
<div class="admin-card">
    <div class="card-head">
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, email or phone" style="width: 260px">
            <button class="btn btn-outline-primary">Search</button>
        </form>
        <span class="small text-muted">{{ $customers->total() }} customers</span>
    </div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th>Customer</th><th>Phone</th><th>Orders</th><th>Spent</th><th>Joined</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($customers as $c)
                    <tr>
                        <td><a href="{{ route('admin.customers.show', $c) }}" class="fw-medium">{{ $c->name }}</a><div class="small text-muted">{{ $c->email }}</div></td>
                        <td class="small">{{ $c->phone ?: '—' }}</td>
                        <td>{{ $c->orders_count }}</td>
                        <td>{{ money($c->spent ?? 0) }}</td>
                        <td class="small">{{ $c->created_at->format('d M Y') }}</td>
                        <td><span class="badge {{ $c->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $c->is_active ? 'Active' : 'Deactivated' }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.customers.show', $c) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-pad">{{ $customers->links() }}</div>
</div>
@endsection
