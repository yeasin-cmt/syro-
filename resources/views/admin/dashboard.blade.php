@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    @foreach ([
        ['Revenue today', money($stats['revenue_today']), 'bi-cash-stack'],
        ['Revenue this month', money($stats['revenue_month']), 'bi-graph-up-arrow'],
        ['Pending orders', $stats['orders_pending'], 'bi-hourglass-split'],
        ['Customers', $stats['customers'], 'bi-people'],
    ] as [$label, $value, $icon])
        <div class="col-sm-6 col-xl-3">
            <div class="admin-kpi d-flex justify-content-between">
                <div><div class="kpi-label">{{ $label }}</div><div class="kpi-value">{{ $value }}</div></div>
                <i class="bi {{ $icon }}"></i>
            </div>
        </div>
    @endforeach
</div>

@if ($stats['bkash_to_verify'] || $stats['reviews_pending'] || $stats['messages_unread'] || $stats['low_stock'])
    <div class="d-flex flex-wrap gap-2 mb-4">
        @if ($stats['bkash_to_verify'])<a href="{{ route('admin.orders.index', ['payment_status' => 'pending_verification']) }}" class="btn btn-sm btn-warning"><i class="bi bi-phone me-1"></i>{{ $stats['bkash_to_verify'] }} bKash payments to verify</a>@endif
        @if ($stats['low_stock'])<a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ $stats['low_stock'] }} products low on stock</a>@endif
        @if ($stats['reviews_pending'])<a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-primary">{{ $stats['reviews_pending'] }} reviews awaiting approval</a>@endif
        @if ($stats['messages_unread'])<a href="{{ route('admin.messages.index') }}" class="btn btn-sm btn-outline-primary">{{ $stats['messages_unread'] }} unread messages</a>@endif
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="admin-card h-100">
            <div class="card-head"><h2>Revenue — last 30 days</h2><span class="small text-muted">Excludes cancelled &amp; returned</span></div>
            <div class="card-pad" style="height: 300px"><canvas id="revenue-chart" data-chart='@json($chart)'></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="admin-card h-100">
            <div class="card-head"><h2>Top products</h2></div>
            <table class="table admin-table">
                <tbody>
                    @forelse ($topProducts as $tp)
                        <tr><td>{{ Str::limit($tp->product_name, 32) }}</td><td class="text-end text-muted">{{ $tp->qty }} sold</td><td class="text-end">{{ money($tp->revenue) }}</td></tr>
                    @empty
                        <tr><td class="text-muted">No sales yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="admin-card">
            <div class="card-head"><h2>Recent orders</h2><a href="{{ route('admin.orders.index') }}" class="small">View all</a></div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Payment</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-medium">{{ $order->order_number }}</a><div class="small text-muted">{{ $order->created_at->diffForHumans() }}</div></td>
                                <td>{{ $order->name }}<div class="small text-muted">{{ $order->phone }}</div></td>
                                <td><span class="badge text-bg-{{ $order->status_color }}">{{ $order->status_label }}</span></td>
                                <td class="small">{{ $order->payment_method_label }}<div class="text-muted">{{ $order->payment_status_label }}</div></td>
                                <td class="text-end">{{ money($order->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="admin-card">
            <div class="card-head"><h2>Low stock</h2><a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="small">View</a></div>
            <table class="table admin-table">
                <tbody>
                    @forelse ($lowStock as $p)
                        <tr>
                            <td><img src="{{ $p->image_url }}" class="admin-thumb" alt=""></td>
                            <td><a href="{{ route('admin.products.edit', $p) }}">{{ Str::limit($p->name, 28) }}</a></td>
                            <td class="text-end"><span class="badge {{ $p->stock ? 'text-bg-warning' : 'text-bg-danger' }}">{{ $p->stock }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="text-muted">All products are well stocked.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
