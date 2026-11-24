<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $user = $request->user();

        $purchased = OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', 'delivered'))
            ->exists();

        if (! $purchased) {
            return back()->with('error', 'Only customers who have received this product can review it.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // One review per customer per product; editing sends it back for moderation.
        $review = Review::firstOrNew(['product_id' => $product->id, 'user_id' => $user->id]);
        $review->fill($data);
        $review->is_approved = false;
        $review->save();

        return back()->with('success', 'Thank you! Your review will appear once it has been approved.');
    }
}
