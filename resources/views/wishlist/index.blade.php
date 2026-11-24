@extends('layouts.app')

@section('title', 'Wishlist')

@section('content')
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Saved for later</span>
        <h1>Wishlist</h1>
    </div>
</section>
<section class="section pt-5">
    <div class="container">
        @if ($products->isEmpty())
            <div class="empty-state">
                <i class="bi bi-heart"></i>
                <h2 class="mt-3">Nothing saved yet</h2>
                <p class="text-muted">Tap the heart on any product to keep it here.</p>
                <a href="{{ route('shop') }}" class="btn btn-primary mt-2">Explore the shop</a>
            </div>
        @else
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-lg-4">
                @foreach ($products as $product)
                    <div class="col">@include('partials.product-card', ['removeOnUnwish' => true])</div>
                @endforeach
            </div>
            <div class="d-flex justify-content-center mt-5">{{ $products->links() }}</div>
        @endif
    </div>
</section>
@endsection
