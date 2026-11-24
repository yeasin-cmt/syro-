@extends('layouts.app')

@php
    $heading = $category?->name ?? $brand?->name ?? (! empty($filters['q']) ? 'Search: “'.$filters['q'].'”' : (! empty($filters['on_sale']) ? 'Sale' : 'Shop all'));
    $action = $category ? route('shop.category', $category) : ($brand ? route('shop.brand', $brand) : route('shop'));
    $checked = fn ($key, $value) => in_array($value, (array) ($selected[$key] ?? []), true) ? 'checked' : '';
@endphp

@section('title', $heading)
@section('meta_description', $category?->description ?: 'Shop premium fashion and lifestyle at SYRO. Delivery across Bangladesh, cash on delivery available.')

@section('content')
<section class="page-hero">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('shop') }}">Shop</a></li>
                @if ($category?->parent)
                    <li class="breadcrumb-item"><a href="{{ route('shop.category', $category->parent) }}">{{ $category->parent->name }}</a></li>
                @endif
                @if ($category || $brand)
                    <li class="breadcrumb-item active" aria-current="page">{{ $category?->name ?? $brand?->name }}</li>
                @endif
            </ol>
        </nav>
        <h1>{{ $heading }}</h1>
        @if ($category?->description)<p class="text-muted mt-2 mb-0" style="max-width:60ch">{{ $category->description }}</p>@endif
        @if ($category && $category->children->where('is_active', true)->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-3">
                @foreach ($category->children->where('is_active', true) as $child)
                    <a href="{{ route('shop.category', $child) }}" class="btn btn-sm btn-outline-primary py-2 px-3">{{ $child->name }}</a>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="section pt-5">
    <div class="container">
        <div class="row g-5">
            {{-- Filters --}}
            <aside class="col-lg-3">
                <button class="btn btn-outline-primary w-100 d-lg-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="bi bi-sliders me-2"></i>Filters
                </button>
                <div class="collapse d-lg-block" id="filterCollapse">
                    <form id="shop-filters" action="{{ $action }}" method="GET" class="filter-sidebar">
                        <div class="filter-group pt-0">
                            <div class="filter-title">Search</div>
                            <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Search products">
                        </div>

                        @if (! $category)
                            <div class="filter-group">
                                <div class="filter-title">Category</div>
                                @foreach ($categories as $cat)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="category[]" value="{{ $cat->slug }}" id="cat-{{ $cat->id }}" {{ $checked('category', $cat->slug) }}>
                                        <label class="form-check-label" for="cat-{{ $cat->id }}">{{ $cat->name }}</label>
                                    </div>
                                    @foreach ($cat->children as $child)
                                        <div class="form-check ms-3">
                                            <input class="form-check-input" type="checkbox" name="category[]" value="{{ $child->slug }}" id="cat-{{ $child->id }}" {{ $checked('category', $child->slug) }}>
                                            <label class="form-check-label text-muted" for="cat-{{ $child->id }}">{{ $child->name }}</label>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        @endif

                        @if (! $brand && $brands->isNotEmpty())
                            <div class="filter-group">
                                <div class="filter-title">Brand</div>
                                @foreach ($brands as $b)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="brand[]" value="{{ $b->slug }}" id="brand-{{ $b->id }}" {{ $checked('brand', $b->slug) }}>
                                        <label class="form-check-label" for="brand-{{ $b->id }}">{{ $b->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="filter-group">
                            <div class="filter-title">Price (৳)</div>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="number" name="min_price" min="0" value="{{ $filters['min_price'] }}" class="form-control form-control-sm" placeholder="0" aria-label="Minimum price">
                                <span class="text-muted">–</span>
                                <input type="number" name="max_price" min="0" value="{{ $filters['max_price'] }}" class="form-control form-control-sm" placeholder="{{ $priceCeiling }}" aria-label="Maximum price">
                            </div>
                        </div>

                        @if ($sizes->isNotEmpty())
                            <div class="filter-group">
                                <div class="filter-title">Size</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($sizes as $size)
                                        <label class="size-option"><input type="checkbox" name="size[]" value="{{ $size }}" {{ $checked('size', $size) }}><span>{{ $size }}</span></label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($colors->isNotEmpty())
                            <div class="filter-group">
                                <div class="filter-title">Colour</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($colors as $c)
                                        <label class="color-option" title="{{ $c->color }}"><input type="checkbox" name="color[]" value="{{ $c->color }}" {{ $checked('color', $c->color) }}><span style="background: {{ $c->color_hex ?: '#ccc' }}"></span></label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="filter-group">
                            <div class="filter-title">Availability</div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="availability" value="" id="av-all" {{ empty($filters['availability']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="av-all">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="availability" value="in_stock" id="av-in" {{ $filters['availability'] === 'in_stock' ? 'checked' : '' }}>
                                <label class="form-check-label" for="av-in">In stock</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="availability" value="out_of_stock" id="av-out" {{ $filters['availability'] === 'out_of_stock' ? 'checked' : '' }}>
                                <label class="form-check-label" for="av-out">Out of stock</label>
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="on_sale" value="1" id="on-sale" {{ $filters['on_sale'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="on-sale">On sale only</label>
                            </div>
                        </div>

                        <div class="pt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Apply</button>
                            <a href="{{ $action }}" class="btn btn-outline-primary btn-sm">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>

            {{-- Results --}}
            <div class="col-lg-9">
                <div class="shop-toolbar">
                    <span class="small text-muted"><strong data-result-count class="text-dark">{{ $products->total() }}</strong> products</span>
                    <div class="d-flex align-items-center gap-2">
                        <label for="sort-select" class="small text-muted d-none d-sm-inline">Sort by</label>
                        <select id="sort-select" class="form-select" aria-label="Sort products">
                            @foreach ($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="product-grid">
                    @include('shop._grid')
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
