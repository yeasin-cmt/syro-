<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlaced;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function index(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your bag is empty.');
        }

        $user = $request->user();
        $lastOrder = $user?->orders()->first();
        $zone = old('shipping_zone', $lastOrder?->shipping_zone ?? 'inside_dhaka');

        return view('checkout.index', [
            'items' => $this->cart->items(),
            'totals' => $this->cart->totals($zone),
            'zone' => $zone,
            'paymentMethods' => $this->enabledPaymentMethods(),
            'shippingRates' => [
                'inside_dhaka' => (float) setting('shipping_inside_dhaka'),
                'outside_dhaka' => (float) setting('shipping_outside_dhaka'),
            ],
            'prefill' => [
                'name' => $user?->name ?? $lastOrder?->name,
                'email' => $user?->email,
                'phone' => $user?->phone ?? $lastOrder?->phone,
                'address' => $user?->address ?? $lastOrder?->address,
                'city' => $user?->city ?? $lastOrder?->city,
                'postcode' => $lastOrder?->postcode,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $methods = array_keys($this->enabledPaymentMethods());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [$request->user() ? 'nullable' : 'required', 'nullable', 'email', 'max:190'],
            'phone' => ['required', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'shipping_zone' => ['required', Rule::in(array_keys(Order::SHIPPING_ZONES))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in($methods)],
            'transaction_id' => ['required_if:payment_method,bkash', 'nullable', 'alpha_num', 'between:8,20'],
        ], [
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'transaction_id.required_if' => 'Enter the bKash Transaction ID you received after sending payment.',
        ]);

        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your bag is empty.');
        }

        $order = DB::transaction(function () use ($data, $request) {
            $items = $this->cart->items();
            $lines = [];
            $subtotal = 0;

            // Lock and re-check every line so two shoppers cannot buy the last unit.
            foreach ($items as $item) {
                /** @var CartItem $item */
                $product = Product::lockForUpdate()->find($item->product_id);
                $variant = $item->product_variant_id ? ProductVariant::lockForUpdate()->find($item->product_variant_id) : null;
                $available = $variant ? $variant->stock : $product?->stock;

                if (! $product || ! $product->is_active || $available < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Sorry, “{$item->product->name}” only has {$available} left. Please update your bag.",
                    ]);
                }

                $price = $variant ? $variant->unitPrice($product) : $product->effective_price;
                $lineTotal = round($price * $item->quantity, 2);
                $subtotal += $lineTotal;
                $lines[] = compact('product', 'variant', 'price', 'lineTotal') + ['quantity' => $item->quantity];
            }

            $coupon = $this->cart->coupon();
            $discount = $coupon ? $coupon->discountFor($subtotal) : 0;
            $shipping = $this->cart->shippingCost($data['shipping_zone'], $subtotal - $discount);

            $order = Order::create([
                'order_number' => Order::generateNumber(),
                'user_id' => $request->user()?->id,
                'name' => $data['name'],
                'email' => $data['email'] ?? $request->user()?->email,
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'],
                'postcode' => $data['postcode'] ?? null,
                'shipping_zone' => $data['shipping_zone'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_cost' => $shipping,
                'total' => round($subtotal - $discount + $shipping, 2),
                'coupon_code' => $coupon?->code,
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_method'] === 'bkash' ? 'pending_verification' : 'unpaid',
                'transaction_id' => $data['payment_method'] === 'bkash' ? strtoupper($data['transaction_id']) : null,
                'status' => 'pending',
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_variant_id' => $line['variant']?->id,
                    'product_name' => $line['product']->name,
                    'sku' => $line['variant']?->sku ?: $line['product']->sku,
                    'size' => $line['variant']?->size,
                    'color' => $line['variant']?->color,
                    'price' => $line['price'],
                    'quantity' => $line['quantity'],
                    'total' => $line['lineTotal'],
                ]);

                if ($line['variant']) {
                    $line['variant']->decrement('stock', $line['quantity']);
                }
                $line['product']->forceFill([
                    'stock' => max(0, $line['product']->stock - $line['quantity']),
                    'sold_count' => $line['product']->sold_count + $line['quantity'],
                ])->saveQuietly();
            }

            if ($coupon) {
                Coupon::whereKey($coupon->id)->increment('used_count');
            }

            $order->histories()->create(['status' => 'pending', 'note' => 'Order placed by customer.']);

            // Remember useful details on the customer profile for next time.
            if ($user = $request->user()) {
                $user->fill(array_filter([
                    'phone' => $user->phone ?: $data['phone'],
                    'address' => $user->address ?: $data['address'],
                    'city' => $user->city ?: $data['city'],
                ]))->save();
            }

            $this->cart->clear();

            return $order;
        });

        if ($order->email) {
            try {
                Mail::to($order->email)->send(new OrderPlaced($order->load('items')));
            } catch (\Throwable $e) {
                Log::error('Order confirmation email failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);
            }
        }

        // Lets guests view their confirmation page without exposing other orders.
        session()->push('placed_orders', $order->order_number);

        return redirect()->route('checkout.success', $order);
    }

    public function success(Request $request, Order $order)
    {
        // Account orders are visible only to their owner; guest orders to the session that placed them.
        $ownsOrder = $order->user_id
            ? $request->user()?->id === $order->user_id
            : in_array($order->order_number, session('placed_orders', []), true);

        abort_unless($ownsOrder, 404);

        return view('checkout.success', ['order' => $order->load('items')]);
    }

    protected function enabledPaymentMethods(): array
    {
        return array_filter(Order::PAYMENT_METHODS, fn ($label, $key) => setting("{$key}_enabled") === '1', ARRAY_FILTER_USE_BOTH);
    }
}
