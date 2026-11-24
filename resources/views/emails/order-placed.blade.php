<x-mail::message>
# Thank you for your order, {{ Str::before($order->name, ' ') }}

We've received your order **{{ $order->order_number }}** and will let you know as soon as it ships.

<x-mail::table>
| Item | Qty | Total |
|:-----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}{{ $item->variant_label ? ' ('.$item->variant_label.')' : '' }} | {{ $item->quantity }} | {{ money($item->total) }} |
@endforeach
| Subtotal | | {{ money($order->subtotal) }} |
@if ($order->discount > 0)
| Discount | | −{{ money($order->discount) }} |
@endif
| Delivery | | {{ $order->shipping_cost > 0 ? money($order->shipping_cost) : 'Free' }} |
| **Total** | | **{{ money($order->total) }}** |
</x-mail::table>

**Delivering to:** {{ $order->name }}, {{ $order->address }}, {{ $order->city }} — {{ $order->phone }}

**Payment:** {{ $order->payment_method_label }} ({{ $order->payment_status_label }})

<x-mail::button :url="route('track.index')" color="primary">
Track your order
</x-mail::button>

With thanks,<br>
The SYRO team
</x-mail::message>
