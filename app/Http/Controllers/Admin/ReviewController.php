<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['product', 'user'])
            ->when($request->status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->status === 'approved', fn ($q) => $q->where('is_approved', true))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function update(Request $request, Review $review)
    {
        $review->forceFill(['is_approved' => $request->boolean('is_approved')])->save();

        return back()->with('success', $review->is_approved ? 'Review approved and published.' : 'Review hidden.');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
