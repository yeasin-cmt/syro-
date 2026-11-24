@php($inWishlist = isset($wishlistIds) && in_array($product->id, $wishlistIds))
<article class="product-card">
    <a href="{{ route('product.show', $product) }}" class="product-media" aria-label="{{ $product->name }}">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" width="800" height="1000">
        @if ($product->hover_image_url)
            <img src="{{ $product->hover_image_url }}" alt="" class="img-hover" loading="lazy" width="800" height="1000">
        @endif
    </a>

    <div class="product-badges">
        @if (! $product->in_stock)
            <span class="badge-syro soldout">Sold out</span>
        @elseif ($product->is_on_sale)
            <span class="badge-syro sale">−{{ $product->discount_percent }}%</span>
        @endif
        @if ($product->is_new_arrival && $product->in_stock)
            <span class="badge-syro">New</span>
        @endif
    </div>

    <button type="button" class="wishlist-btn {{ $inWishlist ? 'active' : '' }}"
            data-wishlist="{{ route('wishlist.toggle', $product) }}" data-auth="{{ auth()->check() ? 1 : 0 }}"
            @if (! empty($removeOnUnwish)) data-remove-card="1" @endif
            aria-label="Save {{ $product->name }} to wishlist">
        <i class="bi {{ $inWishlist ? 'bi-heart-fill' : 'bi-heart' }}"></i>
    </button>

    @if ($product->in_stock)
        <div class="product-actions">
            @if ($product->relationLoaded('variants') ? $product->variants->isNotEmpty() : $product->variants()->exists())
                <a href="{{ route('product.show', $product) }}" class="btn">Choose options</a>
            @else
                <form action="{{ route('cart.store') }}" method="POST" class="w-100 d-flex" data-add-to-cart>
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" class="btn">Add to bag</button>
                </form>
            @endif
        </div>
    @endif

    <div class="product-info">
        @if ($product->relationLoaded('brand') && $product->brand)
            <div class="product-brand">{{ $product->brand->name }}</div>
        @endif
        <h3 class="product-name"><a href="{{ route('product.show', $product) }}">{{ $product->name }}</a></h3>
        <div class="d-flex justify-content-between align-items-center">
            <div class="price">
                @if ($product->is_on_sale)
                    <span class="price-sale">{{ money($product->sale_price) }}</span><span class="price-old">{{ money($product->price) }}</span>
                @else
                    {{ money($product->price) }}
                @endif
            </div>
            @if (($product->approved_reviews_avg_rating ?? 0) > 0)
                <span class="stars" title="{{ number_format($product->approved_reviews_avg_rating, 1) }} out of 5"><i class="bi bi-star-fill"></i> {{ number_format($product->approved_reviews_avg_rating, 1) }}</span>
            @endif
        </div>
    </div>
</article>
