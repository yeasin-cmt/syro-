@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
<section class="page-hero py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <h1 class="h2 m-0">Checkout</h1>
        <div class="checkout-steps"><span>Bag</span><span class="active">Details &amp; payment</span><span>Confirmation</span></div>
    </div>
</section>

<section class="section pt-5">
    <div class="container">
        @error('cart')<div class="alert alert-danger">{{ $message }} <a href="{{ route('cart.index') }}" class="alert-link">Review bag</a></div>@enderror

        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form" novalidate
              data-rates='@json($shippingRates)' data-subtotal="{{ $totals['subtotal'] }}" data-discount="{{ $totals['discount'] }}"
              data-threshold="{{ (float) setting('free_shipping_threshold') }}">
            @csrf
            <div class="row g-5">
                <div class="col-lg-7">
                    @guest
                        <p class="small text-muted">Have an account? <a href="{{ route('login') }}" class="text-decoration-underline">Sign in</a> for faster checkout and order tracking.</p>
                    @endguest

                    <div class="panel mb-4">
                        <div class="panel-title">1. Contact &amp; delivery</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Full name</label>
                                <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $prefill['name']) }}" required autocomplete="name">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Mobile number</label>
                                <input id="phone" type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $prefill['phone']) }}" placeholder="01XXXXXXXXX" required autocomplete="tel">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="email">Email {{ auth()->check() ? '(optional)' : '' }}</label>
                                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $prefill['email']) }}" {{ auth()->check() ? '' : 'required' }} autocomplete="email">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">We'll send your order confirmation here.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="address">Address</label>
                                <input id="address" type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $prefill['address']) }}" placeholder="House, road, area" required autocomplete="street-address">
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="city">City / District</label>
                                <input id="city" type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $prefill['city']) }}" required autocomplete="address-level2">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="postcode">Postcode (optional)</label>
                                <input id="postcode" type="text" name="postcode" class="form-control" value="{{ old('postcode', $prefill['postcode']) }}" autocomplete="postal-code">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Delivery area</label>
                                <div class="row g-2">
                                    @foreach (\App\Models\Order::SHIPPING_ZONES as $key => $label)
                                        <div class="col-sm-6">
                                            <label class="choice-card h-100">
                                                <input type="radio" name="shipping_zone" value="{{ $key }}" class="form-check-input" @checked(old('shipping_zone', $zone) === $key)>
                                                <strong>{{ $label }}</strong>
                                                <span class="float-end">{{ money($shippingRates[$key]) }}</span>
                                                <div class="small text-muted ms-4">{{ $key === 'inside_dhaka' ? '1–3 business days' : '3–5 business days' }}</div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notes">Order notes (optional)</label>
                                <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Landmark, preferred delivery time…">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-title">2. Payment</div>
                        @error('payment_method')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        <div class="d-grid gap-2">
                            @foreach ($paymentMethods as $key => $label)
                                <label class="choice-card">
                                    <input type="radio" name="payment_method" value="{{ $key }}" class="form-check-input" @checked(old('payment_method', array_key_first($paymentMethods)) === $key)>
                                    <strong>{{ $label }}</strong>
                                    <i class="bi {{ $key === 'cod' ? 'bi-cash-stack' : 'bi-phone' }} float-end text-accent"></i>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-3 small text-muted" data-payment-panel="cod">
                            Pay in cash when your order arrives. Please keep the exact amount ready.
                        </div>
                        @if (isset($paymentMethods['bkash']))
                            <div class="mt-3 d-none" data-payment-panel="bkash">
                                <div class="bg-light p-3 small mb-3">
                                    <ol class="mb-0 ps-3">
                                        <li>Open your bKash app and choose <strong>Send Money</strong>.</li>
                                        <li>Send the order total to <strong>{{ setting('bkash_number') }}</strong>.</li>
                                        <li>Enter the <strong>Transaction ID</strong> from the confirmation SMS below.</li>
                                    </ol>
                                </div>
                                <label class="form-label" for="transaction_id">bKash Transaction ID</label>
                                <input id="transaction_id" type="text" name="transaction_id" class="form-control text-uppercase @error('transaction_id') is-invalid @enderror" value="{{ old('transaction_id') }}" placeholder="e.g. 9BC4D7XK2L" maxlength="20">
                                @error('transaction_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Your order is confirmed once our team verifies the payment.</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="panel position-sticky" style="top: 100px">
                        <div class="panel-title">Your order ({{ $items->sum('quantity') }})</div>
                        @foreach ($items as $item)
                            <div class="d-flex gap-3 mb-3">
                                <div style="width: 64px; flex-shrink: 0" class="position-relative">
                                    <img src="{{ $item->product->image_url }}" alt="" class="w-100" style="aspect-ratio: 4/5; object-fit: cover">
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">{{ $item->quantity }}</span>
                                </div>
                                <div class="flex-grow-1 small">
                                    <div class="fw-medium">{{ $item->product->name }}</div>
                                    @if ($item->variant)<div class="text-muted">{{ $item->variant->label }}</div>@endif
                                </div>
                                <div class="small fw-semibold">{{ money($item->line_total) }}</div>
                            </div>
                        @endforeach
                        <hr class="border-line">
                        <div class="summary-row"><span>Subtotal</span><span>{{ money($totals['subtotal']) }}</span></div>
                        @if ($totals['discount'] > 0)
                            <div class="summary-row text-success"><span>Discount ({{ $totals['coupon']->code }})</span><span>−{{ money($totals['discount']) }}</span></div>
                        @endif
                        <div class="summary-row"><span>Delivery</span><span data-shipping>{{ $totals['shipping'] > 0 ? money($totals['shipping']) : 'Free' }}</span></div>
                        <div class="summary-row total"><span>Total</span><span data-total>{{ money($totals['total']) }}</span></div>
                        <button type="submit" class="btn btn-primary w-100 mt-4">Place order</button>
                        <p class="small text-muted text-center mt-3 mb-0">By placing your order you agree to our <a href="{{ route('page', 'terms') }}" class="text-decoration-underline">terms</a>.</p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
