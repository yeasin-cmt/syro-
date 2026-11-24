<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — {{ setting('store_name') }}@else{{ setting('store_name') }} — {{ setting('store_tagline') }}@endif</title>
    <meta name="description" content="@yield('meta_description', 'SYRO — premium fashion and lifestyle in Bangladesh. Modern shopping, premium experience.')">
    <meta name="theme-color" content="#111111">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600|inter:400,500,600" rel="stylesheet">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    <a href="#main" class="visually-hidden-focusable position-absolute p-2 bg-white">Skip to content</a>

    @if (setting('announcement_text'))
        <div class="announcement-bar text-center">
            <div class="container">{{ setting('announcement_text') }}</div>
        </div>
    @endif

    <header class="site-header">
        <div class="container position-relative">
            <div class="header-inner">
                {{-- Left: mobile menu toggle / desktop nav --}}
                <div class="d-flex align-items-center">
                    <button class="icon-link btn d-lg-none p-0 fs-4" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile-menu" aria-label="Open menu">
                        <i class="bi bi-list"></i>
                    </button>
                    <ul class="nav main-nav d-none d-lg-flex">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('shop') ? 'active' : '' }}" href="{{ route('shop') }}">Shop</a></li>
                        <li class="nav-item has-mega">
                            <a class="nav-link {{ request()->routeIs('shop.category') ? 'active' : '' }}" href="{{ route('shop') }}">Collections</a>
                            <div class="mega-menu">
                                <div class="container">
                                    <div class="row g-4">
                                        @foreach ($navCategories->take(4) as $cat)
                                            <div class="col-lg-3">
                                                <h6><a href="{{ route('shop.category', $cat) }}" class="p-0 text-dark">{{ $cat->name }}</a></h6>
                                                @foreach ($cat->children as $child)
                                                    <a href="{{ route('shop.category', $child) }}">{{ $child->name }}</a>
                                                @endforeach
                                                <a href="{{ route('shop.category', $cat) }}" class="mt-2 text-accent small">View all</a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('shop', ['sort' => 'newest']) }}">New In</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('shop', ['on_sale' => 1]) }}">Sale</a></li>
                    </ul>
                </div>

                {{-- Centre: logo --}}
                <a href="{{ route('home') }}" class="syro-logo" aria-label="SYRO home">SYRO</a>

                {{-- Right: icons --}}
                <div class="header-icons d-flex justify-content-end align-items-center">
                    <button type="button" class="icon-link" data-search-open aria-label="Search"><i class="bi bi-search"></i></button>
                    <a href="{{ auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('account.dashboard')) : route('login') }}" class="icon-link d-none d-sm-inline-flex" aria-label="Account"><i class="bi bi-person"></i></a>
                    <a href="{{ route('wishlist.index') }}" class="icon-link d-none d-sm-inline-flex" aria-label="Wishlist">
                        <i class="bi bi-heart"></i><span class="badge-count" data-wishlist-count data-count="{{ $wishlistCount }}">{{ $wishlistCount }}</span>
                    </a>
                    <a href="{{ route('cart.index') }}" class="icon-link" aria-label="Shopping bag">
                        <i class="bi bi-bag"></i><span class="badge-count" data-cart-count data-count="{{ $cartCount }}">{{ $cartCount }}</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Search overlay --}}
    <div id="search-overlay" class="search-overlay" data-suggest="{{ route('search.suggest') }}" role="dialog" aria-label="Search">
        <div class="container" style="max-width: 820px">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="eyebrow">Search SYRO</span>
                <button class="btn-close" data-search-close aria-label="Close search"></button>
            </div>
            <form action="{{ route('shop') }}" method="GET">
                <input type="search" name="q" class="search-input" placeholder="What are you looking for?" autocomplete="off" aria-label="Search products">
            </form>
            <div class="search-results mt-3"></div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobile-menu" aria-label="Menu">
        <div class="offcanvas-header border-bottom">
            <span class="syro-logo" style="font-size:1.4rem">SYRO</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="d-flex flex-column gap-1 account-nav">
                <a href="{{ route('shop') }}">Shop all</a>
                @foreach ($navCategories as $cat)
                    <a href="{{ route('shop.category', $cat) }}">{{ $cat->name }}</a>
                @endforeach
                <a href="{{ route('shop', ['on_sale' => 1]) }}" class="text-danger">Sale</a>
                <hr>
                <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}"><i class="bi bi-person"></i> {{ auth()->check() ? 'My account' : 'Sign in' }}</a>
                <a href="{{ route('wishlist.index') }}"><i class="bi bi-heart"></i> Wishlist</a>
                <a href="{{ route('track.index') }}"><i class="bi bi-truck"></i> Track order</a>
                <a href="{{ route('contact') }}"><i class="bi bi-envelope"></i> Contact</a>
            </nav>
        </div>
    </div>

    <main id="main">
        @yield('content')
    </main>

    {{-- Service strip --}}
    <section class="features-strip">
        <div class="container">
            <div class="row">
                <div class="col-6 col-lg-3"><div class="feature"><i class="bi bi-truck"></i><div><strong>Nationwide delivery</strong><span>All 64 districts</span></div></div></div>
                <div class="col-6 col-lg-3"><div class="feature"><i class="bi bi-arrow-repeat"></i><div><strong>7-day returns</strong><span>Simple &amp; hassle-free</span></div></div></div>
                <div class="col-6 col-lg-3"><div class="feature"><i class="bi bi-cash-coin"></i><div><strong>Cash on delivery</strong><span>Or pay with bKash</span></div></div></div>
                <div class="col-6 col-lg-3"><div class="feature"><i class="bi bi-shield-check"></i><div><strong>Authentic quality</strong><span>Checked by hand</span></div></div></div>
            </div>
        </div>
    </section>

    {{-- Newsletter --}}
    <section class="newsletter section py-5">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <span class="eyebrow">The SYRO Letter</span>
                    <h2 class="section-title mt-2 mb-1">New arrivals, first.</h2>
                    <p class="text-muted mb-0">Early access to drops, private sales and style notes. No spam — ever.</p>
                </div>
                <div class="col-lg-6">
                    <form action="{{ route('newsletter.store') }}" method="POST" class="d-flex gap-3" data-newsletter>
                        @csrf
                        <input type="email" name="email" class="form-control" placeholder="Your email address" required aria-label="Email address">
                        <button class="btn btn-primary flex-shrink-0" type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <a href="{{ route('home') }}" class="syro-logo d-inline-block mb-3">SYRO</a>
                    <p class="mb-4" style="max-width: 32ch">{{ setting('store_tagline') }} Considered fashion and lifestyle pieces, delivered across Bangladesh.</p>
                    <div class="social d-flex gap-2">
                        @foreach (['facebook' => 'facebook', 'instagram' => 'instagram', 'youtube' => 'youtube', 'tiktok' => 'tiktok'] as $key => $icon)
                            @if (setting("social_{$key}"))
                                <a href="{{ setting("social_{$key}") }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($key) }}"><i class="bi bi-{{ $icon }}"></i></a>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <h6>Shop</h6>
                    <ul class="list-unstyled">
                        @foreach ($navCategories->take(5) as $cat)
                            <li><a href="{{ route('shop.category', $cat) }}">{{ $cat->name }}</a></li>
                        @endforeach
                        <li><a href="{{ route('shop', ['on_sale' => 1]) }}">Sale</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6>Help</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('track.index') }}">Track order</a></li>
                        <li><a href="{{ route('page', 'shipping-returns') }}">Shipping &amp; returns</a></li>
                        <li><a href="{{ route('page', 'faq') }}">FAQ</a></li>
                        <li><a href="{{ route('contact') }}">Contact us</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6>SYRO</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('page', 'about') }}">About</a></li>
                        <li><a href="{{ route('page', 'privacy') }}">Privacy</a></li>
                        <li><a href="{{ route('page', 'terms') }}">Terms</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6>Contact</h6>
                    <ul class="list-unstyled">
                        <li>{{ setting('store_phone') }}</li>
                        <li><a href="mailto:{{ setting('store_email') }}">{{ setting('store_email') }}</a></li>
                        <li class="small">{{ setting('store_address') }}</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <span>&copy; {{ date('Y') }} {{ setting('store_name') }}. All rights reserved.</span>
                <div class="d-flex gap-2">
                    <span class="pay-chip">Cash on Delivery</span>
                    <span class="pay-chip">bKash</span>
                </div>
            </div>
        </div>
    </footer>

    <div id="toast-holder" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080"></div>
    @foreach (['success', 'error'] as $type)
        @if (session($type))
            <span hidden data-flash="{{ session($type) }}" data-type="{{ $type }}"></span>
        @endif
    @endforeach
    @if (session('status'))
        <span hidden data-flash="{{ session('status') }}" data-type="success"></span>
    @endif

    @stack('scripts')
</body>
</html>
