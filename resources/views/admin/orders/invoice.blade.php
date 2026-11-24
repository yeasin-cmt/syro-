<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:600|inter:400,600" rel="stylesheet">
    <style>
        body { font-family: Inter, sans-serif; color: #222; font-size: 13px; margin: 0; padding: 40px; }
        .wrap { max-width: 760px; margin: 0 auto; }
        .logo { font-family: 'Cormorant Garamond', serif; font-size: 30px; letter-spacing: .4em; font-weight: 600; }
        .muted { color: #777; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th { text-align: left; font-size: 10px; letter-spacing: .14em; text-transform: uppercase; color: #777; border-bottom: 1px solid #111; padding: 8px 0; }
        td { padding: 10px 0; border-bottom: 1px solid #eee; }
        .r { text-align: right; }
        .totals td { border: 0; padding: 4px 0; }
        .grand td { border-top: 1px solid #111; font-weight: 600; font-size: 15px; padding-top: 10px; }
        .print { position: fixed; top: 16px; right: 16px; padding: 8px 16px; background: #111; color: #fff; border: 0; cursor: pointer; }
        @media print { .print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<button class="print" onclick="window.print()">Print</button>
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
            <div class="logo">SYRO</div>
            <div class="muted" style="margin-top:6px">{{ setting('store_address') }}<br>{{ setting('store_phone') }} · {{ setting('store_email') }}</div>
        </div>
        <div class="r">
            <div style="font-size:20px;font-weight:600">INVOICE</div>
            <div>{{ $order->order_number }}</div>
            <div class="muted">{{ $order->created_at->format('d M Y') }}</div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;margin-top:32px">
        <div>
            <div class="muted" style="font-size:10px;letter-spacing:.14em;text-transform:uppercase">Bill / ship to</div>
            <strong>{{ $order->name }}</strong><br>{{ $order->address }}<br>{{ $order->city }} {{ $order->postcode }}<br>{{ $order->phone }}
        </div>
        <div class="r">
            <div class="muted" style="font-size:10px;letter-spacing:.14em;text-transform:uppercase">Payment</div>
            {{ $order->payment_method_label }}<br>{{ $order->payment_status_label }}
            @if ($order->transaction_id)<br>TrxID: {{ $order->transaction_id }}@endif
        </div>
    </div>

    <table>
        <thead><tr><th>Item</th><th>SKU</th><th class="r">Price</th><th class="r">Qty</th><th class="r">Total</th></tr></thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }} @if ($item->variant_label)<span class="muted">({{ $item->variant_label }})</span>@endif</td>
                    <td class="muted">{{ $item->sku }}</td>
                    <td class="r">{{ money($item->price) }}</td>
                    <td class="r">{{ $item->quantity }}</td>
                    <td class="r">{{ money($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals" style="width:280px;margin-left:auto;margin-top:12px">
        <tr><td class="muted">Subtotal</td><td class="r">{{ money($order->subtotal) }}</td></tr>
        @if ($order->discount > 0)<tr><td class="muted">Discount</td><td class="r">−{{ money($order->discount) }}</td></tr>@endif
        <tr><td class="muted">Delivery</td><td class="r">{{ money($order->shipping_cost) }}</td></tr>
        <tr class="grand"><td>Total</td><td class="r">{{ money($order->total) }}</td></tr>
    </table>
    <p class="muted" style="margin-top:48px;text-align:center">Thank you for shopping with SYRO.</p>
</div>
</body>
</html>
