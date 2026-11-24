<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->wishlist()->active()->with(['images', 'variants'])->latest('wishlists.created_at')->paginate(12);

        return view('wishlist.index', compact('products'));
    }

    public function toggle(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $result = $request->user()->wishlist()->toggle($product->id);
        $added = ! empty($result['attached']);
        $message = $added ? 'Saved to your wishlist.' : 'Removed from your wishlist.';

        if ($request->expectsJson()) {
            return response()->json([
                'added' => $added,
                'message' => $message,
                'count' => $request->user()->wishlist()->count(),
            ]);
        }

        return back()->with('success', $message);
    }
}
