@extends('account._layout')

@section('title', 'Order '.$order->order_number)
@section('account_title', 'Order '.$order->order_number)

@section('account_content')
<div class="panel mb-4">
    <div class="d-flex flex-wrap justify-content-between gap-2">
        <div>
            <div class="small text-muted">Placed {{ $order->created_at->format('d M Y, h:i A') }}</div>
            <span class="badge text-bg-{{ $order->status_color }} rounded-0 mt-1">{{ $order->status_label }}</span>
        </div>
        @if ($order->canBeCancelledByCustomer())
            <form action="{{ route('account.orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Cancel this order?')">
                @csrf
                <button class="btn btn-outline-danger btn-sm">Cancel order</button>
            </form>
        @endif
    </div>
    @include('partials.order-progress')
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-title">Items</div>
            @foreach ($order->items as $item)
                <div class="d-flex gap-3 py-3 border-bottom border-line">
                    <img src="{{ $item->product?->image_url ?? asset('images/placeholder.svg') }}" alt="" style="width:64px;aspect-ratio:4/5;object-fit:cover">
                    <div class="flex-grow-1 small">
                        @if ($item->product && $item->product->is_active && ! $item->product->trashed())
                            <a href="{{ route('product.show', $item->product) }}" class="fw-medium text-dark">{{ $item->product_name }}</a>
                        @else
                            <span class="fw-medium">{{ $item->product_name }}</span>
                        @endif
                        @if ($item->variant_label)<div class="text-muted">{{ $item->variant_label }}</div>@endif
                        <div class="text-muted">{{ money($item->price) }} × {{ $item->quantity }}</div>
                        @if ($order->status === 'delivered' && $item->product && ! $item->product->trashed())
                            <a href="{{ route('product.show', $item->product) }}#reviews" class="small text-accent">Write a review</a>
                        @endif
                    </div>
                    <div class="small fw-semibold">{{ money($item->total) }}</div>
                </div>
            @endforeach
            <div class="summary-row mt-2"><span>Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
            @if ($order->discount > 0)<div class="summary-row text-success"><span>Discount ({{ $order->coupon_code }})</span><span>−{{ money($order->discount) }}</span></div>@endif
            <div class="summary-row"><span>Delivery</span><span>{{ $order->shipping_cost > 0 ? money($order->shipping_cost) : 'Free' }}</span></div>
            <div class="summary-row total"><span>Total</span><span>{{ money($order->total) }}</span></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel mb-4 small">
            <div class="panel-title">Delivery</div>
            {{ $order->name }}<br>{{ $order->address }}<br>{{ $order->city }} {{ $order->postcode }}<br>{{ $order->phone }}
            <div class="text-muted mt-2">{{ $order->shipping_zone_label }}</div>
        </div>
        <div class="panel mb-4 small">
            <div class="panel-title">Payment</div>
            {{ $order->payment_method_label }} — <strong>{{ $order->payment_status_label }}</strong>
            @if ($order->transaction_id)<div class="text-muted">TrxID: {{ $order->transaction_id }}</div>@endif
        </div>
        <div class="panel small">
            <div class="panel-title">History</div>
            <ul class="timeline">
                @foreach ($order->histories as $h)
                    <li><strong>{{ $h->status_label }}</strong> <span class="text-muted">· {{ $h->created_at->format('d M, h:i A') }}</span>
                        @if ($h->note)<div class="text-muted">{{ $h->note }}</div>@endif</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
