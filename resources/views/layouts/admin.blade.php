<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') — SYRO Admin</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:500,600|inter:400,500,600" rel="stylesheet">
    @vite(['resources/scss/app.scss', 'resources/js/admin.js'])
</head>
<body class="admin-body">
@php
    $pendingOrders = \App\Models\Order::where('status', 'pending')->count();
    $pendingReviews = \App\Models\Review::where('is_approved', false)->count();
    $unreadMessages = \App\Models\ContactMessage::where('is_read', false)->count();
    $link = fn ($route, $pattern = null) => request()->routeIs($pattern ?? $route) ? 'active' : '';
@endphp

<aside class="admin-sidebar" id="admin-sidebar">
    <div class="px-4 py-4 border-bottom" style="border-color: rgba(255,255,255,.08) !important">
        <a href="{{ route('admin.dashboard') }}" class="syro-logo">SYRO</a>
        <div class="small mt-1" style="letter-spacing:.2em;font-size:.6rem;color:#B69A70">ADMIN</div>
    </div>
    <nav class="pb-4">
        <div class="nav-section">Overview</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-item-link {{ $link('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="{{ route('admin.orders.index') }}" class="nav-item-link {{ $link('', 'admin.orders.*') }}"><i class="bi bi-receipt"></i> Orders @if ($pendingOrders)<span class="badge">{{ $pendingOrders }}</span>@endif</a>

        <div class="nav-section">Catalogue</div>
        <a href="{{ route('admin.products.index') }}" class="nav-item-link {{ $link('', 'admin.products.*') }}"><i class="bi bi-box-seam"></i> Products</a>
        <a href="{{ route('admin.categories.index') }}" class="nav-item-link {{ $link('', 'admin.categories.*') }}"><i class="bi bi-diagram-3"></i> Categories</a>
        <a href="{{ route('admin.brands.index') }}" class="nav-item-link {{ $link('', 'admin.brands.*') }}"><i class="bi bi-award"></i> Brands</a>
        <a href="{{ route('admin.reviews.index') }}" class="nav-item-link {{ $link('', 'admin.reviews.*') }}"><i class="bi bi-star"></i> Reviews @if ($pendingReviews)<span class="badge">{{ $pendingReviews }}</span>@endif</a>

        <div class="nav-section">Customers &amp; marketing</div>
        <a href="{{ route('admin.customers.index') }}" class="nav-item-link {{ $link('', 'admin.customers.*') }}"><i class="bi bi-people"></i> Customers</a>
        <a href="{{ route('admin.coupons.index') }}" class="nav-item-link {{ $link('', 'admin.coupons.*') }}"><i class="bi bi-ticket-perforated"></i> Coupons</a>
        <a href="{{ route('admin.banners.index') }}" class="nav-item-link {{ $link('', 'admin.banners.*') }}"><i class="bi bi-images"></i> Banners</a>
        <a href="{{ route('admin.subscribers.index') }}" class="nav-item-link {{ $link('', 'admin.subscribers.*') }}"><i class="bi bi-envelope-paper"></i> Subscribers</a>
        <a href="{{ route('admin.messages.index') }}" class="nav-item-link {{ $link('', 'admin.messages.*') }}"><i class="bi bi-chat-left-text"></i> Messages @if ($unreadMessages)<span class="badge">{{ $unreadMessages }}</span>@endif</a>

        <div class="nav-section">System</div>
        <a href="{{ route('admin.settings.edit') }}" class="nav-item-link {{ $link('', 'admin.settings.*') }}"><i class="bi bi-gear"></i> Settings</a>
        <a href="{{ route('home') }}" class="nav-item-link" target="_blank"><i class="bi bi-box-arrow-up-right"></i> View store</a>
    </nav>
</aside>

<div class="admin-main">
    <header class="admin-topbar justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-link text-dark d-lg-none p-0 fs-4" onclick="document.getElementById('admin-sidebar').classList.toggle('open')" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
            <h1 class="admin-page-title">@yield('title', 'Dashboard')</h1>
        </div>
        <div class="dropdown">
            <button class="btn btn-link text-dark text-decoration-none dropdown-toggle p-0" data-bs-toggle="dropdown" style="letter-spacing:0;text-transform:none;font-size:.88rem;font-weight:500">
                <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('account.profile') }}">My profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><form action="{{ route('logout') }}" method="POST">@csrf<button class="dropdown-item">Sign out</button></form></li>
            </ul>
        </div>
    </header>

    <main class="admin-content">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    @foreach (['success', 'error'] as $type)
        @if (session($type))
            <div class="toast toast-syro {{ $type === 'error' ? 'error' : '' }}" role="status">
                <div class="d-flex"><div class="toast-body">{{ session($type) }}</div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div>
            </div>
        @endif
    @endforeach
</div>
</body>
</html>
