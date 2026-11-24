@extends('account._layout')

@section('title', 'My orders')
@section('account_title', 'Orders')

@section('account_content')
<div class="panel">
    @if ($orders->isEmpty())
        <div class="empty-state py-4">
            <i class="bi bi-box-seam"></i>
            <h3 class="mt-3">No orders yet</h3>
            <a href="{{ route('shop') }}" class="btn btn-primary mt-2">Start shopping</a>
        </div>
    @else
        @include('account._orders-table')
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
