@extends('layouts.app')

@section('content')
<section class="page-hero py-4">
    <div class="container">
        <span class="eyebrow">My account</span>
        <h1 class="h2">@yield('account_title')</h1>
    </div>
</section>
<section class="section pt-5">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <aside class="col-lg-3">
                <nav class="account-nav panel p-0">
                    <a href="{{ route('account.dashboard') }}" class="{{ request()->routeIs('account.dashboard') ? 'active' : '' }}"><i class="bi bi-grid"></i> Overview</a>
                    <a href="{{ route('account.orders') }}" class="{{ request()->routeIs('account.orders*') ? 'active' : '' }}"><i class="bi bi-box-seam"></i> Orders</a>
                    <a href="{{ route('wishlist.index') }}"><i class="bi bi-heart"></i> Wishlist</a>
                    <a href="{{ route('account.profile') }}" class="{{ request()->routeIs('account.profile') ? 'active' : '' }}"><i class="bi bi-person"></i> Profile &amp; password</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Admin panel</a>
                    @endif
                    <form action="{{ route('logout') }}" method="POST">@csrf
                        <button class="btn btn-link w-100 text-start text-dark d-flex gap-3 px-3 py-3" style="letter-spacing:0;text-transform:none;font-size:.9rem;font-weight:400"><i class="bi bi-box-arrow-right"></i> Sign out</button>
                    </form>
                </nav>
            </aside>
            <div class="col-lg-9">
                @yield('account_content')
            </div>
        </div>
    </div>
</section>
@endsection
