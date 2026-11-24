<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $base = fn () => Product::active()->with(['images', 'variants', 'brand'])->withAvg('approvedReviews', 'rating');

        return view('home', [
            'heroBanners' => Banner::active()->where('position', 'hero')->get(),
            'promoBanners' => Banner::active()->where('position', 'promo')->take(2)->get(),
            'featuredCategories' => Category::active()->where('is_featured', true)
                ->withCount(['products' => fn ($q) => $q->active()])->orderBy('sort_order')->take(4)->get(),
            'newArrivals' => $base()->where('is_new_arrival', true)->latest()->take(8)->get(),
            'bestSellers' => $base()->orderByDesc('sold_count')->take(8)->get(),
            'trending' => $base()->where('is_trending', true)->latest()->take(4)->get(),
            'featured' => $base()->where('is_featured', true)->latest()->take(8)->get(),
            'brands' => Brand::active()->orderBy('name')->get(),
        ]);
    }
}
