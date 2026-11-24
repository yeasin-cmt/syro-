<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Database-backed cart. Guests are identified by a random token kept in the session
 * (which survives Laravel's session-ID regeneration on login); signed-in users by user_id.
 */
class CartService
{
    public const COUPON_SESSION_KEY = 'cart.coupon';

    protected ?Collection $itemsCache = null;

    public function token(): string
    {
        if (! session()->has('cart_token')) {
            session()->put('cart_token', Str::random(40));
        }

        return session('cart_token');
    }

    protected function query(): Builder
    {
        return auth()->check()
            ? CartItem::query()->where('user_id', auth()->id())
            : CartItem::query()->whereNull('user_id')->where('cart_token', $this->token());
    }

    /**
     * Cart lines with their product data. Lines whose product was removed or disabled are dropped.
     */
    public function items(): Collection
    {
        if ($this->itemsCache !== null) {
            return $this->itemsCache;
        }

        $items = $this->query()->with(['product.images', 'variant'])->oldest()->get();

        $invalid = $items->filter(fn (CartItem $i) => ! $i->product || ! $i->product->is_active
            || ($i->product_variant_id && ! $i->variant));
        if ($invalid->isNotEmpty()) {
            CartItem::whereIn('id', $invalid->pluck('id'))->delete();
            $items = $items->diff($invalid)->values();
        }

        return $this->itemsCache = $items;
    }

    public function count(): int
    {
        return (int) $this->query()->sum('quantity');
    }

    public function subtotal(): float
    {
        return round($this->items()->sum(fn (CartItem $i) => $i->line_total), 2);
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    public function add(Product $product, ?ProductVariant $variant, int $quantity): CartItem
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages(['product' => 'This product is no longer available.']);
        }
        if ($product->variants()->exists() && ! $variant) {
            throw ValidationException::withMessages(['variant' => 'Please select a size / colour.']);
        }
        if ($variant && $variant->product_id !== $product->id) {
            throw ValidationException::withMessages(['variant' => 'Invalid option selected.']);
        }

        $line = $this->query()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->first();

        $available = $variant ? $variant->stock : $product->stock;
        $newQty = ($line?->quantity ?? 0) + $quantity;

        if ($available < 1) {
            throw ValidationException::withMessages(['quantity' => 'Sorry, this item is out of stock.']);
        }
        if ($newQty > $available) {
            throw ValidationException::withMessages(['quantity' => "Only {$available} left in stock."]);
        }

        $this->itemsCache = null;

        if ($line) {
            $line->update(['quantity' => $newQty]);

            return $line;
        }

        return CartItem::create([
            'user_id' => auth()->id(),
            'cart_token' => auth()->check() ? null : $this->token(),
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
        ]);
    }

    public function find(int $id): CartItem
    {
        return $this->query()->with(['product', 'variant'])->findOrFail($id);
    }

    public function update(CartItem $item, int $quantity): void
    {
        if ($quantity > $item->available_stock) {
            throw ValidationException::withMessages(['quantity' => "Only {$item->available_stock} left in stock."]);
        }
        $item->update(['quantity' => $quantity]);
        $this->itemsCache = null;
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
        $this->itemsCache = null;
    }

    public function clear(): void
    {
        $this->query()->delete();
        session()->forget(self::COUPON_SESSION_KEY);
        $this->itemsCache = null;
    }

    /**
     * Move a guest cart into the user's cart after login/registration.
     */
    public function mergeGuestCartInto(User $user, ?string $token): void
    {
        if (! $token) {
            return;
        }

        $guestItems = CartItem::whereNull('user_id')->where('cart_token', $token)->get();

        foreach ($guestItems as $guest) {
            $existing = CartItem::where('user_id', $user->id)
                ->where('product_id', $guest->product_id)
                ->where('product_variant_id', $guest->product_variant_id)
                ->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $guest->quantity]);
                $guest->delete();
            } else {
                $guest->update(['user_id' => $user->id, 'cart_token' => null]);
            }
        }
        $this->itemsCache = null;
    }

    /* ---------------- Coupons ---------------- */

    public function applyCoupon(string $code): Coupon
    {
        $coupon = Coupon::where('code', strtoupper(trim($code)))->first();

        if (! $coupon) {
            throw ValidationException::withMessages(['coupon' => 'That coupon code is not valid.']);
        }
        if ($error = $coupon->validationError($this->subtotal())) {
            throw ValidationException::withMessages(['coupon' => $error]);
        }

        session()->put(self::COUPON_SESSION_KEY, $coupon->code);

        return $coupon;
    }

    public function removeCoupon(): void
    {
        session()->forget(self::COUPON_SESSION_KEY);
    }

    /**
     * The applied coupon, if it is still valid for the current cart.
     */
    public function coupon(): ?Coupon
    {
        $code = session(self::COUPON_SESSION_KEY);
        if (! $code) {
            return null;
        }

        $coupon = Coupon::where('code', $code)->first();
        if (! $coupon || $coupon->validationError($this->subtotal())) {
            $this->removeCoupon();

            return null;
        }

        return $coupon;
    }

    /* ---------------- Totals ---------------- */

    public function shippingCost(string $zone, float $subtotalAfterDiscount): float
    {
        $threshold = (float) setting('free_shipping_threshold', 0);
        if ($threshold > 0 && $subtotalAfterDiscount >= $threshold) {
            return 0;
        }

        return (float) ($zone === 'outside_dhaka' ? setting('shipping_outside_dhaka') : setting('shipping_inside_dhaka'));
    }

    /**
     * @return array{subtotal: float, discount: float, shipping: float, total: float, coupon: ?Coupon}
     */
    public function totals(string $zone = 'inside_dhaka'): array
    {
        $subtotal = $this->subtotal();
        $coupon = $this->coupon();
        $discount = $coupon ? $coupon->discountFor($subtotal) : 0.0;
        $shipping = $subtotal > 0 ? $this->shippingCost($zone, $subtotal - $discount) : 0.0;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => round($subtotal - $discount + $shipping, 2),
            'coupon' => $coupon,
        ];
    }
}
