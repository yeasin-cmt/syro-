@extends('layouts.app')

@section('title', 'Track your order')

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Order status</span>
        <h1>Track your order</h1>
    </div>
</section>
<section class="section pt-5">
    <div class="container" style="max-width: 820px">
        <div class="panel mb-4">
            <form action="{{ route('track.show') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label" for="order_number">Order number</label>
                    <input id="order_number" name="order_number" class="form-control text-uppercase @error('order_number') is-invalid @enderror" value="{{ old('order_number', $order->order_number ?? '') }}" placeholder="SYR-XXXXXX-XXXXX" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="phone">Mobile number</label>
                    <input id="phone" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" required>
                </div>
                <div class="col-md-3"><button class="btn btn-primary w-100">Track</button></div>
                @error('order_number')<div class="col-12 small text-danger">{{ $message }}</div>@enderror
            </form>
        </div>

        @isset($order)
            <div class="panel">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="panel-title mb-1">{{ $order->order_number }}</div>
                        <div class="small text-muted">Placed {{ $order->created_at->format('d M Y') }} · {{ $order->items->sum('quantity') }} items · {{ money($order->total) }}</div>
                    </div>
                    <span class="badge text-bg-{{ $order->status_color }} rounded-0 align-self-start">{{ $order->status_label }}</span>
                </div>
                @include('partials.order-progress')
                <ul class="timeline small">
                    @foreach ($order->histories as $h)
                        <li><strong>{{ $h->status_label }}</strong> <span class="text-muted">· {{ $h->created_at->format('d M Y, h:i A') }}</span>
                            @if ($h->note)<div class="text-muted">{{ $h->note }}</div>@endif</li>
                    @endforeach
                </ul>
            </div>
        @endisset
    </div>
</section>
@endsection
