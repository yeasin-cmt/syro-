@extends('layouts.app')

@section('title', 'Your bag')

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Shopping bag</span>
        <h1>Your bag</h1>
    </div>
</section>

<section class="section pt-5">
    <div class="container">
        @if ($items->isEmpty())
            <div class="empty-state">
                <i class="bi bi-bag"></i>
                <h2 class="mt-3">Your bag is empty</h2>
                <p class="text-muted">Discover new arrivals and timeless essentials.</p>
                <a href="{{ route('shop') }}" class="btn btn-primary mt-2">Continue shopping</a>
            </div>
        @else
            <div class="row g-5">
                <div class="col-lg-8">
                    @error('quantity')<div class="alert alert-danger">{{ $message }}</div>@enderror
                    @foreach ($items as $item)
                        <div class="cart-line">
                            <a href="{{ route('product.show', $item->product) }}" class="thumb"><img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}"></a>
                            <div>
                                <h3 class="h6 mb-1" style="font-family: Inter, sans-serif; font-weight: 500"><a href="{{ route('product.show', $item->product) }}" class="text-dark">{{ $item->product->name }}</a></h3>
                                @if ($item->variant)<div class="small text-muted mb-1">{{ $item->variant->label }}</div>@endif
                                <div class="small mb-3">{{ money($item->unit_price) }}</div>
                                <div class="d-flex align-items-center gap-3">
                                    <form action="{{ route('cart.update', $item->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <div class="qty-input" style="width: 120px">
                                            <button type="button" data-qty="-1" aria-label="Decrease">−</button>
                                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ max(1, min(20, $item->available_stock)) }}" data-autosubmit aria-label="Quantity">
                                            <button type="button" data-qty="1" aria-label="Increase">+</button>
                                        </div>
                                    </form>
                                    <form action="{{ route('cart.destroy', $item->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-link p-0 small text-muted text-decoration-underline" style="letter-spacing:0;text-transform:none">Remove</button>
                                    </form>
                                </div>
                                @if ($item->quantity > $item->available_stock)
                                    <div class="small text-danger mt-2">Only {{ $item->available_stock }} available — please reduce the quantity.</div>
                                @endif
                            </div>
                            <div class="line-total fw-semibold text-end">{{ money($item->line_total) }}</div>
                        </div>
                    @endforeach
                    <a href="{{ route('shop') }}" class="link-underline d-inline-block mt-4">Continue shopping</a>
                </div>

                <div class="col-lg-4">
                    <div class="panel position-sticky" style="top: 100px">
                        <div class="panel-title">Order summary</div>

                        @if ($totals['coupon'])
                            <div class="d-flex justify-content-between align-items-center bg-light p-2 mb-3 small">
                                <span><i class="bi bi-tag me-1"></i><strong>{{ $totals['coupon']->code }}</strong> — {{ $totals['coupon']->label }}</span>
                                <form action="{{ route('cart.coupon.remove') }}" method="POST">@csrf @method('DELETE')
                                    <button class="btn btn-link btn-sm p-0 text-muted" aria-label="Remove coupon"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('cart.coupon.apply') }}" method="POST" class="mb-3">
                                @csrf
                                <div class="input-group">
                                    <input type="text" name="coupon" class="form-control text-uppercase @error('coupon') is-invalid @enderror" placeholder="Coupon code" value="{{ old('coupon') }}" aria-label="Coupon code">
                                    <button class="btn btn-outline-primary px-3">Apply</button>
                                </div>
                                @error('coupon')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                            </form>
                        @endif

                        <div class="summary-row"><span>Subtotal</span><span>{{ money($totals['subtotal']) }}</span></div>
                        @if ($totals['discount'] > 0)
                            <div class="summary-row text-success"><span>Discount</span><span>−{{ money($totals['discount']) }}</span></div>
                        @endif
                        <div class="summary-row">
                            <span>Delivery
                                <form method="GET" class="d-inline">
                                    <select name="zone" class="form-select form-select-sm d-inline w-auto ms-1 py-0" onchange="this.form.submit()" aria-label="Delivery area">
                                        @foreach (\App\Models\Order::SHIPPING_ZONES as $key => $label)
                                            <option value="{{ $key }}" @selected($zone === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </span>
                            <span>{{ $totals['shipping'] > 0 ? money($totals['shipping']) : 'Free' }}</span>
                        </div>
                        <div class="summary-row total"><span>Total</span><span>{{ money($totals['total']) }}</span></div>

                        @php($threshold = (float) setting('free_shipping_threshold'))
                        @if ($threshold > 0 && $totals['subtotal'] - $totals['discount'] < $threshold)
                            <p class="small text-muted mt-2 mb-0">Add {{ money($threshold - ($totals['subtotal'] - $totals['discount'])) }} more for free delivery.</p>
                        @endif

                        <a href="{{ route('checkout.index') }}" class="btn btn-primary w-100 mt-4">Proceed to checkout</a>
                        <p class="small text-muted text-center mt-3 mb-0"><i class="bi bi-lock me-1"></i>Secure checkout · Cash on delivery &amp; bKash</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
