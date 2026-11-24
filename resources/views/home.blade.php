@extends('layouts.app')

@section('content')

{{-- ============ HERO ============ --}}
<section class="hero">
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6500">
        @if ($heroBanners->count() > 1)
            <div class="carousel-indicators">
                @foreach ($heroBanners as $i => $b)
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-label="Slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
        <div class="carousel-inner">
            @forelse ($heroBanners as $i => $banner)
                <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                    <div class="hero-slide" style="background-image: url('{{ $banner->image_url }}')">
                        <div class="container">
                            @if ($banner->eyebrow)<span class="eyebrow hero-anim d-block mb-3">{{ $banner->eyebrow }}</span>@endif
                            @if ($i === 0)
                                <h1 class="hero-anim mb-4">{{ $banner->title }}</h1>
                            @else
                                <h2 class="hero-anim mb-4">{{ $banner->title }}</h2>
                            @endif
                            @if ($banner->subtitle)<p class="hero-anim mb-4">{{ $banner->subtitle }}</p>@endif
                            @if ($banner->link)
                                <div class="hero-anim d-flex gap-3 flex-wrap">
                                    <a href="{{ $banner->link }}" class="btn btn-light">{{ $banner->button_text ?: 'Shop now' }}</a>
                                    <a href="{{ route('shop') }}" class="btn btn-light-outline">Explore all</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="carousel-item active">
                    <div class="hero-slide" style="background: linear-gradient(120deg, #111 0%, #2b2620 100%)">
                        <div class="container">
                            <span class="eyebrow hero-anim d-block mb-3">New season</span>
                            <h1 class="hero-anim mb-4">Modern Shopping. Premium Experience.</h1>
                            <div class="hero-anim"><a href="{{ route('shop') }}" class="btn btn-light">Shop the collection</a></div>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
        @if ($heroBanners->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Previous"><span class="carousel-control-prev-icon"></span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Next"><span class="carousel-control-next-icon"></span></button>
        @endif
    </div>
</section>

{{-- ============ FEATURED CATEGORIES ============ --}}
@if ($featuredCategories->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 mb-lg-5 reveal">
            <div>
                <span class="eyebrow">Shop by category</span>
                <h2 class="section-title mt-2 mb-0">Curated for every moment</h2>
            </div>
            <a href="{{ route('shop') }}" class="link-underline d-none d-md-inline">View all</a>
        </div>
        <div class="row g-3 g-lg-4">
            @foreach ($featuredCategories as $cat)
                <div class="col-6 col-lg-3 reveal">
                    <a href="{{ route('shop.category', $cat) }}" class="category-tile">
                        @if ($cat->image_url)<img src="{{ $cat->image_url }}" alt="{{ $cat->name }}" loading="lazy">@endif
                        <div class="tile-caption">
                            <span>{{ $cat->products_count }} pieces</span>
                            <h3>{{ $cat->name }}</h3>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ NEW ARRIVALS ============ --}}
@if ($newArrivals->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 mb-lg-5 reveal">
            <div>
                <span class="eyebrow">Just landed</span>
                <h2 class="section-title mt-2 mb-0">New arrivals</h2>
            </div>
            <a href="{{ route('shop', ['sort' => 'newest']) }}" class="link-underline">Shop new</a>
        </div>
        <div class="row row-cols-2 row-cols-lg-4 g-3 g-lg-4">
            @foreach ($newArrivals as $product)
                <div class="col reveal">@include('partials.product-card')</div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ PROMO BANNERS ============ --}}
@if ($promoBanners->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <div class="row g-3 g-lg-4">
            @foreach ($promoBanners as $promo)
                <div class="col-lg-{{ $promoBanners->count() === 1 ? 12 : 6 }} reveal">
                    <a href="{{ $promo->link ?: route('shop') }}" class="promo-banner" style="background-image:url('{{ $promo->image_url }}')">
                        <div>
                            @if ($promo->eyebrow)<span class="eyebrow" style="color:#e2cfae">{{ $promo->eyebrow }}</span>@endif
                            <h3 class="mt-2 mb-2">{{ $promo->title }}</h3>
                            @if ($promo->subtitle)<p class="mb-3 opacity-75">{{ $promo->subtitle }}</p>@endif
                            <span class="link-underline text-white">{{ $promo->button_text ?: 'Discover' }}</span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ BEST SELLERS ============ --}}
@if ($bestSellers->isNotEmpty())
<section class="section bg-white border-top border-bottom border-line">
    <div class="container">
        <div class="text-center mb-4 mb-lg-5 reveal">
            <span class="eyebrow">Most loved</span>
            <h2 class="section-title mt-2 mb-0">Best sellers</h2>
        </div>
        <div class="row row-cols-2 row-cols-lg-4 g-3 g-lg-4">
            @foreach ($bestSellers as $product)
                <div class="col reveal">@include('partials.product-card')</div>
            @endforeach
        </div>
        <div class="text-center mt-5"><a href="{{ route('shop', ['sort' => 'popular']) }}" class="btn btn-outline-primary">Shop best sellers</a></div>
    </div>
</section>
@endif

{{-- ============ TRENDING (editorial split) ============ --}}
@if ($trending->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-lg-4 reveal">
                <span class="eyebrow">Trending now</span>
                <h2 class="section-title mt-2">The pieces everyone is talking about</h2>
                <p class="text-muted mb-4">Our most-viewed styles this week — refined essentials and statement pieces chosen by the SYRO community.</p>
                <a href="{{ route('shop', ['sort' => 'popular']) }}" class="link-underline">Discover trending</a>
            </div>
            <div class="col-lg-8">
                <div class="row row-cols-2 g-3 g-lg-4">
                    @foreach ($trending as $product)
                        <div class="col reveal">@include('partials.product-card')</div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============ FEATURED ============ --}}
@if ($featured->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 mb-lg-5 reveal">
            <div>
                <span class="eyebrow">Editor's edit</span>
                <h2 class="section-title mt-2 mb-0">Featured products</h2>
            </div>
            <a href="{{ route('shop') }}" class="link-underline">Shop all</a>
        </div>
        <div class="row row-cols-2 row-cols-lg-4 g-3 g-lg-4">
            @foreach ($featured as $product)
                <div class="col reveal">@include('partials.product-card')</div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ BRANDS ============ --}}
@if ($brands->isNotEmpty())
<section class="py-5 bg-white border-top border-line">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-center align-items-center gap-4 gap-lg-5">
            @foreach ($brands as $brand)
                <a href="{{ route('shop.brand', $brand) }}" class="text-uppercase text-muted fw-semibold" style="letter-spacing:.3em;font-size:.8rem">{{ $brand->name }}</a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
