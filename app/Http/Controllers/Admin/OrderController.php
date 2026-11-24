<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::withCount('items')
            ->when($request->q, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('order_number', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->payment_status, fn ($q, $s) => $q->where('payment_status', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statusCounts = Order::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.orders.index', compact('orders', 'statusCounts'));
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load(['items.product.images', 'histories.user', 'user'])]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $order->transitionTo($data['status'], $data['note'] ?? null, $request->user()->id);

        // Cash on delivery is collected on delivery.
        if ($data['status'] === 'delivered' && $order->payment_method === 'cod' && $order->payment_status === 'unpaid') {
            $order->update(['payment_status' => 'paid']);
        }

        return back()->with('success', "Order status set to {$order->status_label}.");
    }

    public function updatePayment(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUSES))],
        ]);

        $order->update($data);
        $order->histories()->create([
            'status' => $order->status,
            'note' => 'Payment marked as '.$order->payment_status_label.'.',
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Payment status updated.');
    }

    /**
     * Delete an order (e.g. a test or spam order). Stock is returned unless it was already restored.
     */
    public function destroy(Order $order)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
            if (! in_array($order->status, Order::RESTOCKED_STATUSES, true)) {
                $order->transitionTo('cancelled', 'Order deleted by admin.');
            }
            $order->delete();
        });

        return redirect()->route('admin.orders.index')->with('success', "Order {$order->order_number} deleted and stock restored.");
    }

    public function invoice(Order $order)
    {
        return view('admin.orders.invoice', ['order' => $order->load('items')]);
    }
}
