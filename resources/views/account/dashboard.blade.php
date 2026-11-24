@extends('account._layout')

@section('title', 'My account')
@section('account_title', 'Hello, '.Str::before($user->name, ' '))

@section('account_content')
<div class="row g-3 mb-4">
    <div class="col-sm-4"><div class="stat-card"><div class="stat-value">{{ $stats['orders'] }}</div><div class="stat-label mt-2">Orders</div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-value">{{ money($stats['spent']) }}</div><div class="stat-label mt-2">Total spent</div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-value">{{ $stats['wishlist'] }}</div><div class="stat-label mt-2">Saved items</div></div></div>
</div>

<div class="panel">
    <div class="d-flex justify-content-between align-items-center">
        <div class="panel-title mb-0">Recent orders</div>
        <a href="{{ route('account.orders') }}" class="link-underline">All orders</a>
    </div>
    @if ($recentOrders->isEmpty())
        <p class="text-muted mt-3 mb-0">You haven't placed any orders yet. <a href="{{ route('shop') }}" class="text-decoration-underline">Start shopping</a>.</p>
    @else
        <div class="mt-3">@include('account._orders-table', ['orders' => $recentOrders])</div>
    @endif
</div>

<div class="panel mt-4">
    <div class="panel-title">Account details</div>
    <p class="mb-1">{{ $user->name }}</p>
    <p class="mb-1 text-muted">{{ $user->email }}</p>
    @if ($user->phone)<p class="mb-1 text-muted">{{ $user->phone }}</p>@endif
    @if ($user->address)<p class="mb-0 text-muted">{{ $user->address }}, {{ $user->city }}</p>@endif
    <a href="{{ route('account.profile') }}" class="link-underline d-inline-block mt-3">Edit details</a>
</div>
@endsection
