@extends('layouts.app')

@section('title', 'Order confirmed')

@section('content')
<section class="section">
    <div class="container" style="max-width: 760px">
        <div class="text-center mb-5">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-dark text-white mb-4" style="width:72px;height:72px"><i class="bi bi-check-lg fs-2"></i></div>
            <span class="eyebrow d-block">Thank you, {{ Str::before($order->name, ' ') }}</span>
            <h1 class="section-title mt-2">Your order is placed</h1>
            <p class="text-muted">Order <strong class="text-dark">{{ $order->order_number }}</strong>
                @if ($order->email) · A confirmation has been sent to {{ $order->email }} @endif</p>
            @if ($order->payment_method === 'bkash')
                <div class="alert alert-light border mt-3 small">We've received your bKash Transaction ID <strong>{{ $order->transaction_id }}</strong>. Our team will verify it and confirm your order shortly.</div>
            @else
                <p class="small text-muted">Please keep <strong>{{ money($order->total) }}</strong> ready for cash on delivery.</p>
            @endif
        </div>

        <div class="panel">
            <div class="panel-title">Order summary</div>
            @foreach ($order->items as $item)
                <div class="d-flex justify-content-between small py-2 border-bottom border-line">
                    <span>{{ $item->product_name }} @if ($item->variant_label)<span class="text-muted">({{ $item->variant_label }})</span>@endif × {{ $item->quantity }}</span>
                    <span>{{ money($item->total) }}</span>
                </div>
            @endforeach
            <div class="summary-row mt-2"><span>Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
            @if ($order->discount > 0)<div class="summary-row text-success"><span>Discount</span><span>−{{ money($order->discount) }}</span></div>@endif
            <div class="summary-row"><span>Delivery ({{ $order->shipping_zone_label }})</span><span>{{ $order->shipping_cost > 0 ? money($order->shipping_cost) : 'Free' }}</span></div>
            <div class="summary-row total"><span>Total</span><span>{{ money($order->total) }}</span></div>

            <div class="row mt-4 small">
                <div class="col-sm-6 mb-3">
                    <div class="form-label">Delivering to</div>
                    {{ $order->name }}<br>{{ $order->address }}<br>{{ $order->city }} {{ $order->postcode }}<br>{{ $order->phone }}
                </div>
                <div class="col-sm-6">
                    <div class="form-label">Payment</div>
                    {{ $order->payment_method_label }}<br><span class="text-muted">{{ $order->payment_status_label }}</span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
            @auth
                <a href="{{ route('account.orders.show', $order) }}" class="btn btn-primary">View order</a>
            @else
                <a href="{{ route('track.index') }}" class="btn btn-primary">Track your order</a>
            @endauth
            <a href="{{ route('shop') }}" class="btn btn-outline-primary">Continue shopping</a>
        </div>
    </div>
</section>
@endsection
