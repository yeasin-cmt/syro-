<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function index(Request $request)
    {
        $zone = $request->query('zone') === 'outside_dhaka' ? 'outside_dhaka' : 'inside_dhaka';

        return view('cart.index', [
            'items' => $this->cart->items(),
            'totals' => $this->cart->totals($zone),
            'zone' => $zone,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $variant = isset($data['variant_id']) ? ProductVariant::findOrFail($data['variant_id']) : null;

        $this->cart->add($product, $variant, $data['quantity'] ?? 1);

        return $this->respond($request, "{$product->name} added to your bag.");
    }

    public function update(Request $request, int $item)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:20']]);
        $this->cart->update($this->cart->find($item), $data['quantity']);

        return $this->respond($request, 'Bag updated.');
    }

    public function destroy(Request $request, int $item)
    {
        $this->cart->remove($this->cart->find($item));

        return $this->respond($request, 'Item removed from your bag.');
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['coupon' => ['required', 'string', 'max:40']]);
        $coupon = $this->cart->applyCoupon($data['coupon']);

        return $this->respond($request, "Coupon {$coupon->code} applied — {$coupon->label}.");
    }

    public function removeCoupon(Request $request)
    {
        $this->cart->removeCoupon();

        return $this->respond($request, 'Coupon removed.');
    }

    protected function respond(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            $totals = $this->cart->totals();

            return response()->json([
                'message' => $message,
                'count' => $this->cart->count(),
                'subtotal' => money($totals['subtotal']),
            ]);
        }

        return back()->with('success', $message);
    }
}
