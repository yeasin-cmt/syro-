@extends('layouts.admin')

@section('title', 'Order '.$order->order_number)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <span class="badge text-bg-{{ $order->status_color }} fs-6">{{ $order->status_label }}</span>
        <span class="text-muted small ms-2">Placed {{ $order->created_at->format('d M Y, h:i A') }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i>Invoice</a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-light">Back to orders</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="admin-card mb-3">
            <div class="card-head"><h2>Items</h2></div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th></th><th>Product</th><th>Price</th><th>Qty</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td style="width:60px"><img src="{{ $item->product?->image_url ?? asset('images/placeholder.svg') }}" class="admin-thumb" alt=""></td>
                                <td>
                                    @if ($item->product && ! $item->product->trashed())
                                        <a href="{{ route('admin.products.edit', $item->product) }}">{{ $item->product_name }}</a>
                                    @else
                                        {{ $item->product_name }}
                                    @endif
                                    <div class="small text-muted">{{ $item->sku }} @if ($item->variant_label) · {{ $item->variant_label }} @endif</div>
                                </td>
                                <td>{{ money($item->price) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td class="text-end">{{ money($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end text-muted">Subtotal</td><td class="text-end">{{ money($order->subtotal) }}</td></tr>
                        @if ($order->discount > 0)<tr><td colspan="4" class="text-end text-muted">Discount ({{ $order->coupon_code }})</td><td class="text-end text-success">−{{ money($order->discount) }}</td></tr>@endif
                        <tr><td colspan="4" class="text-end text-muted">Delivery ({{ $order->shipping_zone_label }})</td><td class="text-end">{{ money($order->shipping_cost) }}</td></tr>
                        <tr><td colspan="4" class="text-end fw-semibold">Total</td><td class="text-end fw-semibold">{{ money($order->total) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-head"><h2>History</h2></div>
            <div class="card-pad">
                <ul class="timeline small mb-0">
                    @foreach ($order->histories as $h)
                        <li><strong>{{ $h->status_label }}</strong> <span class="text-muted">· {{ $h->created_at->format('d M Y, h:i A') }} @if ($h->user) · by {{ $h->user->name }} @endif</span>
                            @if ($h->note)<div class="text-muted">{{ $h->note }}</div>@endif</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="admin-card mb-3">
            <div class="card-head"><h2>Update status</h2></div>
            <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="card-pad">
                @csrf @method('PATCH')
                <select name="status" class="form-select mb-2">
                    @foreach (\App\Models\Order::STATUSES as $key => $label)<option value="{{ $key }}" @selected($order->status === $key)>{{ $label }}</option>@endforeach
                </select>
                <input name="note" class="form-control mb-2" placeholder="Note (optional, visible to customer)" maxlength="255">
                <button class="btn btn-primary w-100">Update status</button>
                <div class="form-text">Cancelling or returning restores stock automatically.</div>
            </form>
        </div>

        <div class="admin-card mb-3">
            <div class="card-head"><h2>Payment</h2></div>
            <div class="card-pad">
                <p class="mb-1"><strong>{{ $order->payment_method_label }}</strong></p>
                @if ($order->transaction_id)
                    <p class="mb-2 small">bKash TrxID: <code class="fs-6">{{ $order->transaction_id }}</code></p>
                @endif
                <form action="{{ route('admin.orders.payment', $order) }}" method="POST" class="d-flex gap-2">
                    @csrf @method('PATCH')
                    <select name="payment_status" class="form-select">
                        @foreach (\App\Models\Order::PAYMENT_STATUSES as $key => $label)<option value="{{ $key }}" @selected($order->payment_status === $key)>{{ $label }}</option>@endforeach
                    </select>
                    <button class="btn btn-outline-primary">Save</button>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-head"><h2>Customer</h2></div>
            <div class="card-pad small">
                <p class="mb-1 fw-semibold">{{ $order->name }}</p>
                <p class="mb-1"><a href="tel:{{ $order->phone }}">{{ $order->phone }}</a></p>
                @if ($order->email)<p class="mb-2"><a href="mailto:{{ $order->email }}">{{ $order->email }}</a></p>@endif
                <p class="mb-2 text-muted">{{ $order->address }}<br>{{ $order->city }} {{ $order->postcode }}</p>
                @if ($order->notes)<div class="bg-light p-2 mb-2"><strong>Note:</strong> {{ $order->notes }}</div>@endif
                @if ($order->user)
                    <a href="{{ route('admin.customers.show', $order->user) }}">View customer profile →</a>
                @else
                    <span class="text-muted">Guest checkout</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
