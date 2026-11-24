<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead class="small text-uppercase text-muted" style="letter-spacing:.1em">
            <tr><th>Order</th><th>Date</th><th>Items</th><th>Status</th><th class="text-end">Total</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td class="fw-medium">{{ $order->order_number }}</td>
                    <td class="small">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="small">{{ $order->items_count }}</td>
                    <td><span class="badge text-bg-{{ $order->status_color }} rounded-0">{{ $order->status_label }}</span></td>
                    <td class="text-end">{{ money($order->total) }}</td>
                    <td class="text-end"><a href="{{ route('account.orders.show', $order) }}" class="small text-decoration-underline">View</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
