@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm {{ ! request('status') ? 'btn-primary' : 'btn-light' }}">All ({{ $statusCounts->sum() }})</a>
    @foreach (\App\Models\Order::STATUSES as $key => $label)
        <a href="{{ route('admin.orders.index', ['status' => $key]) }}" class="btn btn-sm {{ request('status') === $key ? 'btn-primary' : 'btn-light' }}">{{ $label }} ({{ $statusCounts[$key] ?? 0 }})</a>
    @endforeach
</div>

<div class="admin-card">
    <div class="card-head">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Order #, name, phone, email" style="width: 240px">
            <select name="payment_status" class="form-select" style="width: 190px">
                <option value="">Any payment status</option>
                @foreach (\App\Models\Order::PAYMENT_STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('payment_status') === $key)>{{ $label }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width: 160px" aria-label="From date">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width: 160px" aria-label="To date">
            <button class="btn btn-outline-primary">Filter</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Items</th><th>Status</th><th>Payment</th><th class="text-end">Total</th></tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-medium">{{ $order->order_number }}</a></td>
                        <td class="small">{{ $order->created_at->format('d M Y') }}<div class="text-muted">{{ $order->created_at->format('h:i A') }}</div></td>
                        <td>{{ $order->name }}<div class="small text-muted">{{ $order->phone }} · {{ $order->city }}</div></td>
                        <td>{{ $order->items_count }}</td>
                        <td><span class="badge text-bg-{{ $order->status_color }}">{{ $order->status_label }}</span></td>
                        <td class="small">{{ $order->payment_method_label }}
                            <div class="{{ $order->payment_status === 'paid' ? 'text-success' : ($order->payment_status === 'pending_verification' ? 'text-warning' : 'text-muted') }}">{{ $order->payment_status_label }}</div></td>
                        <td class="text-end fw-medium">{{ money($order->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-pad">{{ $orders->links() }}</div>
</div>
@endsection
