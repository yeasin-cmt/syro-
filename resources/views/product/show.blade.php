@extends('layouts.app')

@section('title', $product->meta_title ?: $product->name)
@section('meta_description', (string) ($product->meta_description ?: $product->short_description ?: $product->name))

@push('head')
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:image" content="{{ url($product->image_url) }}">
    <meta property="og:type" content="product">
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'image' => $product->images->map(fn ($i) => url($i->url))->all(),
            'description' => strip_tags((string) $product->short_description),
            'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'BDT',
                'price' => $product->effective_price,
                'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
@endpush

@section('content')
<div class="container pt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            @if ($product->category->parent)
                <li class="breadcrumb-item"><a href="{{ route('shop.category', $product->category->parent) }}">{{ $product->category->parent->name }}</a></li>
            @endif
            <li class="breadcrumb-item"><a href="{{ route('shop.category', $product->category) }}">{{ $product->category->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>
</div>

<section class="section pt-4">
    <div class="container">
        <div class="row g-4 g-lg-5">
            {{-- Gallery --}}
            <div class="col-lg-7">
                <div data-gallery>
                    <div class="gallery-main">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" width="800" height="1000">
                    </div>
                    @if ($product->images->count() > 1)
                        <div class="gallery-thumbs">
                            @foreach ($product->images as $i => $img)
                                <button type="button" class="{{ $i === 0 ? 'active' : '' }}" data-src="{{ $img->url }}" aria-label="View image {{ $i + 1 }}">
                                    <img src="{{ $img->url }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Summary --}}
            <div class="col-lg-5">
                <div class="product-summary position-sticky" style="top: 100px">
                    @if ($product->brand)
                        <a href="{{ route('shop.brand', $product->brand) }}" class="eyebrow">{{ $product->brand->name }}</a>
                    @endif
                    <h1 class="mt-2 mb-3">{{ $product->name }}</h1>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="price" data-price-label>
                            @if ($product->is_on_sale)
                                <span class="price-sale">{{ money($product->sale_price) }}</span><span class="price-old">{{ money($product->price) }}</span>
                            @else
                                {{ money($product->price) }}
                            @endif
                        </div>
                        @if ($product->is_on_sale)
                            <span class="badge bg-danger rounded-0">Save {{ $product->discount_percent }}%</span>
                        @endif
                    </div>

                    @if ($reviewCount > 0)
                        <a href="#reviews" class="d-inline-flex align-items-center gap-2 small text-muted mb-3">
                            <span class="stars">@for ($s = 1; $s <= 5; $s++)<i class="bi bi-star{{ $s <= round($product->average_rating) ? '-fill' : '' }}"></i>@endfor</span>
                            {{ number_format($product->average_rating, 1) }} · {{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}
                        </a>
                    @endif

                    @if ($product->short_description)
                        <p class="text-muted mb-4">{{ $product->short_description }}</p>
                    @endif

                    <form action="{{ route('cart.store') }}" method="POST" data-add-to-cart data-variant-picker data-variants='@json($variantsJson)'>
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="variant_id" value="">

                        @if ($product->availableColors()->isNotEmpty())
                            <div class="mb-4">
                                <div class="option-label">Colour: <span class="text-muted fw-normal" data-color-name>Select</span></div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($product->availableColors() as $color)
                                        <button type="button" class="color-swatch" data-color="{{ $color['name'] }}" style="background: {{ $color['hex'] }}" title="{{ $color['name'] }}" aria-label="{{ $color['name'] }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($product->availableSizes()->isNotEmpty())
                            <div class="mb-4">
                                <div class="d-flex justify-content-between">
                                    <div class="option-label">Size: <span class="text-muted fw-normal" data-size-name>Select</span></div>
                                    <a href="#" class="small text-muted text-decoration-underline" data-bs-toggle="modal" data-bs-target="#sizeGuide">Size guide</a>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($product->availableSizes() as $size)
                                        <button type="button" class="size-swatch" data-size="{{ $size }}">{{ $size }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="small mb-3" data-stock-label>
                            @unless ($product->hasVariants())
                                @if ($product->stock > 5)<span class="text-success">In stock</span>
                                @elseif ($product->stock > 0)<span class="text-warning">Only {{ $product->stock }} left</span>
                                @else<span class="text-danger">Out of stock</span>@endif
                            @endunless
                        </div>

                        <div class="d-flex gap-2 mb-3">
                            <div class="qty-input">
                                <button type="button" data-qty="-1" aria-label="Decrease quantity">−</button>
                                <input type="number" name="quantity" value="1" min="1" max="{{ max(1, min(20, $product->stock)) }}" aria-label="Quantity">
                                <button type="button" data-qty="1" aria-label="Increase quantity">+</button>
                            </div>
                            <button type="submit" class="btn btn-primary flex-grow-1" @disabled(! $product->in_stock)>
                                {{ $product->in_stock ? 'Add to bag' : 'Sold out' }}
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-icon flex-shrink-0 {{ $inWishlist ? 'active' : '' }}" style="width:52px;height:auto"
                                    data-wishlist="{{ route('wishlist.toggle', $product) }}" data-auth="{{ auth()->check() ? 1 : 0 }}" aria-label="Save to wishlist">
                                <i class="bi {{ $inWishlist ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                            </button>
                        </div>
                    </form>

                    <div class="trust-list row g-3 my-4">
                        <div class="col-6 d-flex gap-2 align-items-center"><i class="bi bi-truck"></i> Delivery in 1–5 days</div>
                        <div class="col-6 d-flex gap-2 align-items-center"><i class="bi bi-arrow-repeat"></i> 7-day easy returns</div>
                        <div class="col-6 d-flex gap-2 align-items-center"><i class="bi bi-cash-coin"></i> Cash on delivery</div>
                        <div class="col-6 d-flex gap-2 align-items-center"><i class="bi bi-shield-check"></i> Quality guaranteed</div>
                    </div>

                    <ul class="list-unstyled meta-list mb-0">
                        <li><strong>SKU:</strong> {{ $product->sku }}</li>
                        <li><strong>Category:</strong> <a href="{{ route('shop.category', $product->category) }}" class="text-muted">{{ $product->category->name }}</a></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mt-5 pt-lg-4">
            <ul class="nav nav-tabs-syro" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-desc" type="button" role="tab">Description</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-delivery" type="button" role="tab">Delivery &amp; returns</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="reviews" data-bs-toggle="tab" data-bs-target="#tab-reviews" type="button" role="tab">Reviews ({{ $reviewCount }})</button></li>
            </ul>
            <div class="tab-content pt-4">
                <div class="tab-pane fade show active prose" id="tab-desc" role="tabpanel" style="max-width: 75ch">
                    {!! nl2br(e($product->description ?: $product->short_description)) !!}
                </div>
                <div class="tab-pane fade prose" id="tab-delivery" role="tabpanel" style="max-width: 75ch">
                    <p>Inside Dhaka: {{ money(setting('shipping_inside_dhaka')) }}, delivered in 1–3 business days. Outside Dhaka: {{ money(setting('shipping_outside_dhaka')) }}, delivered in 3–5 business days.
                        @if ((float) setting('free_shipping_threshold') > 0) Free delivery on orders over {{ money(setting('free_shipping_threshold')) }}. @endif</p>
                    <p>Unworn items with tags can be returned within 7 days. <a href="{{ route('page', 'shipping-returns') }}">Read the full policy</a>.</p>
                </div>
                <div class="tab-pane fade" id="tab-reviews" role="tabpanel">
                    <div class="row g-5">
                        <div class="col-lg-4">
                            <div class="panel">
                                <div class="d-flex align-items-end gap-2 mb-2">
                                    <span class="stat-value" style="font-family: 'Cormorant Garamond', serif; font-size: 3rem; line-height: 1">{{ number_format($product->average_rating, 1) }}</span>
                                    <span class="text-muted mb-1">/ 5</span>
                                </div>
                                <p class="small text-muted">Based on {{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}</p>
                                @for ($r = 5; $r >= 1; $r--)
                                    @php($pct = $reviewCount ? round(($ratingStats[$r] ?? 0) / $reviewCount * 100) : 0)
                                    <div class="d-flex align-items-center gap-2 small mb-1">
                                        <span style="width: 12px">{{ $r }}</span><i class="bi bi-star-fill text-accent"></i>
                                        <div class="rating-bar flex-grow-1"><div class="fill" style="width: {{ $pct }}%"></div></div>
                                        <span class="text-muted" style="width: 32px">{{ $ratingStats[$r] ?? 0 }}</span>
                                    </div>
                                @endfor
                            </div>

                            <div class="panel mt-3">
                                <div class="panel-title">Write a review</div>
                                @guest
                                    <p class="small text-muted mb-0"><a href="{{ route('login') }}" class="text-decoration-underline">Sign in</a> to review products you've purchased.</p>
                                @else
                                    @if ($canReview)
                                        @if ($userReview && ! $userReview->is_approved)
                                            <p class="small text-muted">Your review is awaiting approval. Submitting again will update it.</p>
                                        @endif
                                        <form action="{{ route('reviews.store', $product) }}" method="POST">
                                            @csrf
                                            <div class="mb-3">
                                                <label class="form-label">Rating</label>
                                                <select name="rating" class="form-select" required>
                                                    @for ($r = 5; $r >= 1; $r--)
                                                        <option value="{{ $r }}" @selected(old('rating', $userReview?->rating) == $r)>{{ $r }} {{ Str::plural('star', $r) }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Title</label>
                                                <input type="text" name="title" class="form-control" maxlength="120" value="{{ old('title', $userReview?->title) }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Your review</label>
                                                <textarea name="comment" rows="4" class="form-control @error('comment') is-invalid @enderror" required minlength="10">{{ old('comment', $userReview?->comment) }}</textarea>
                                                @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <button class="btn btn-primary w-100">Submit review</button>
                                        </form>
                                    @else
                                        <p class="small text-muted mb-0">Reviews are open to customers who have received this product.</p>
                                    @endif
                                @endguest
                            </div>
                        </div>
                        <div class="col-lg-8">
                            @forelse ($reviews as $review)
                                <div class="border-bottom border-line pb-4 mb-4">
                                    <div class="d-flex justify-content-between">
                                        <span class="stars">@for ($s = 1; $s <= 5; $s++)<i class="bi bi-star{{ $s <= $review->rating ? '-fill' : '' }}"></i>@endfor</span>
                                        <span class="small text-muted">{{ $review->created_at->format('d M Y') }}</span>
                                    </div>
                                    @if ($review->title)<h5 class="mt-2 mb-1" style="font-family: Inter, sans-serif; font-size: 1rem; font-weight: 600">{{ $review->title }}</h5>@endif
                                    <p class="mb-1">{{ $review->comment }}</p>
                                    <span class="small text-muted">{{ $review->user->name }} · <i class="bi bi-patch-check text-success"></i> Verified buyer</span>
                                </div>
                            @empty
                                <p class="text-muted">No reviews yet.</p>
                            @endforelse
                            {{ $reviews->fragment('reviews')->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@if ($related->isNotEmpty())
<section class="section bg-white border-top border-line">
    <div class="container">
        <div class="text-center mb-5">
            <span class="eyebrow">Complete the look</span>
            <h2 class="section-title mt-2">You may also like</h2>
        </div>
        <div class="row row-cols-2 row-cols-lg-4 g-3 g-lg-4">
            @foreach ($related as $product)
                <div class="col">@include('partials.product-card')</div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Size guide --}}
<div class="modal fade" id="sizeGuide" tabindex="-1" aria-labelledby="sizeGuideLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-0">
            <div class="modal-header">
                <h5 class="modal-title" id="sizeGuideLabel">Size guide (inches)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm text-center mb-0">
                    <thead><tr><th>Size</th><th>Chest</th><th>Waist</th><th>Hip</th><th>Length</th></tr></thead>
                    <tbody>
                        <tr><td>XS</td><td>34</td><td>28</td><td>35</td><td>26</td></tr>
                        <tr><td>S</td><td>36</td><td>30</td><td>37</td><td>27</td></tr>
                        <tr><td>M</td><td>38</td><td>32</td><td>39</td><td>28</td></tr>
                        <tr><td>L</td><td>40</td><td>34</td><td>41</td><td>29</td></tr>
                        <tr><td>XL</td><td>42</td><td>36</td><td>43</td><td>30</td></tr>
                        <tr><td>XXL</td><td>44</td><td>38</td><td>45</td><td>31</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
