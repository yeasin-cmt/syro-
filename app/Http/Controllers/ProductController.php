<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        $this->ensureVisible($product);

        $product->load(['images', 'variants', 'category.parent', 'brand']);
        $product->increment('views');

        $reviews = $product->approvedReviews()->with('user:id,name')->paginate(5, ['*'], 'reviews');
        $ratingStats = $product->approvedReviews()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');

        $related = Product::active()->with(['images', 'variants', 'brand'])->withAvg('approvedReviews', 'rating')
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->inRandomOrder()->take(4)->get();

        $user = auth()->user();

        return view('product.show', [
            'product' => $product,
            'reviews' => $reviews,
            'ratingStats' => $ratingStats,
            'reviewCount' => $ratingStats->sum(),
            'related' => $related,
            'inWishlist' => $user ? $user->hasInWishlist($product) : false,
            'userReview' => $user ? $product->reviews()->where('user_id', $user->id)->first() : null,
            'canReview' => $user ? $this->hasPurchased($user->id, $product->id) : false,
            'variantsJson' => $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'size' => $v->size,
                'color' => $v->color,
                'stock' => $v->stock,
                'price' => $v->unitPrice($product),
                'price_formatted' => money($v->unitPrice($product)),
            ])->values(),
        ]);
    }

    protected function ensureVisible(Product $product): void
    {
        abort_unless($product->is_active || auth()->user()?->isAdmin(), 404);
    }

    /**
     * Only customers who received the product may review it.
     */
    protected function hasPurchased(int $userId, int $productId): bool
    {
        return \App\Models\OrderItem::where('product_id', $productId)
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId)->where('status', 'delivered'))
            ->exists();
    }
}
