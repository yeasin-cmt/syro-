<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function index()
    {
        return view('orders.track');
    }

    public function show(Request $request)
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $normalize = fn (string $p) => substr(preg_replace('/\D/', '', $p), -11);

        $order = Order::with(['items', 'histories'])->where('order_number', strtoupper(trim($data['order_number'])))->first();

        if (! $order || $normalize($order->phone) !== $normalize($data['phone'])) {
            return back()->withInput()->withErrors(['order_number' => 'We could not find an order with those details.']);
        }

        return view('orders.track', compact('order'));
    }
}
