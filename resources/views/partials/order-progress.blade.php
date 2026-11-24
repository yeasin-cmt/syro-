@php
    $flow = ['pending' => 'Placed', 'confirmed' => 'Confirmed', 'processing' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
    $keys = array_keys($flow);
    $current = array_search($order->status, $keys, true);
@endphp
@if (in_array($order->status, ['cancelled', 'returned'], true))
    <div class="alert alert-danger rounded-0 my-3">This order was {{ strtolower($order->status_label) }}.</div>
@else
    <div class="order-progress">
        @foreach ($flow as $key => $label)
            <div class="step {{ $current !== false && $loop->index <= $current ? 'done' : '' }}">
                <div class="dot">@if ($current !== false && $loop->index <= $current)<i class="bi bi-check"></i>@endif</div>
                {{ $label }}
            </div>
        @endforeach
    </div>
@endif
