@extends('layouts.admin')

@section('title', 'Coupons')

@section('content')
<div class="admin-card">
    <div class="card-head"><h2>Discount codes</h2><a href="{{ route('admin.coupons.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New coupon</a></div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th>Code</th><th>Discount</th><th>Min. order</th><th>Used</th><th>Valid</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($coupons as $c)
                    @php($expired = $c->expires_at && $c->expires_at->isPast())
                    <tr>
                        <td><code class="fs-6 text-dark">{{ $c->code }}</code></td>
                        <td>{{ $c->label }}@if ($c->max_discount)<div class="small text-muted">max {{ money($c->max_discount) }}</div>@endif</td>
                        <td>{{ $c->min_order_amount > 0 ? money($c->min_order_amount) : '—' }}</td>
                        <td>{{ $c->used_count }}{{ $c->usage_limit ? ' / '.$c->usage_limit : '' }}</td>
                        <td class="small">{{ $c->starts_at?->format('d M Y') ?? 'Now' }} → {{ $c->expires_at?->format('d M Y') ?? 'No expiry' }}</td>
                        <td><span class="badge {{ ! $c->is_active || $expired ? 'text-bg-secondary' : 'text-bg-success' }}">{{ $expired ? 'Expired' : ($c->is_active ? 'Active' : 'Disabled') }}</span></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.coupons.edit', $c) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.coupons.destroy', $c) }}" method="POST" class="d-inline" data-confirm="Delete coupon {{ $c->code }}?">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No coupons yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-pad">{{ $coupons->links() }}</div>
</div>
@endsection
