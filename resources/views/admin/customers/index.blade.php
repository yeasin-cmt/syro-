@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex gap-2">
        @foreach (['' => 'Customers', 'admin' => 'Administrators', 'all' => 'Everyone'] as $key => $label)
            <a href="{{ route('admin.customers.index', array_filter(['role' => $key])) }}" class="btn btn-sm {{ request('role', '') === $key ? 'btn-primary' : 'btn-light' }}">{{ $label }}</a>
        @endforeach
    </div>
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Add user</a>
</div>

<div class="admin-card">
    <div class="card-head">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="role" value="{{ request('role') }}">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, email or phone" style="width: 260px">
            <button class="btn btn-outline-primary">Search</button>
        </form>
        <span class="small text-muted">{{ $customers->total() }} accounts</span>
    </div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th>Name</th><th>Phone</th><th>Role</th><th>Orders</th><th>Spent</th><th>Joined</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($customers as $c)
                    <tr>
                        <td><a href="{{ route('admin.customers.show', $c) }}" class="fw-medium">{{ $c->name }}</a><div class="small text-muted">{{ $c->email }}</div></td>
                        <td class="small">{{ $c->phone ?: '—' }}</td>
                        <td><span class="badge {{ $c->isAdmin() ? 'text-bg-dark' : 'text-bg-light' }}">{{ $c->isAdmin() ? 'Admin' : 'Customer' }}</span></td>
                        <td>{{ $c->orders_count }}</td>
                        <td>{{ money($c->spent ?? 0) }}</td>
                        <td class="small">{{ $c->created_at->format('d M Y') }}</td>
                        <td><span class="badge {{ $c->is_active ? 'text-bg-success' : 'text-bg-danger' }}">{{ $c->is_active ? 'Active' : 'Deactivated' }}</span></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.customers.show', $c) }}" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('admin.customers.edit', $c) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                            @if ($c->id !== auth()->id())
                                <form action="{{ route('admin.customers.destroy', $c) }}" method="POST" class="d-inline" data-confirm="Delete the account of {{ $c->name }}? Their orders will be kept.">
                                    @csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-pad">{{ $customers->links() }}</div>
</div>
@endsection
