<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public const SORTS = [
        'newest' => 'Newest',
        'popular' => 'Best selling',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'name' => 'Name A–Z',
    ];

    public function index(Request $request)
    {
        return $this->listing($request);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);

        return $this->listing($request, $category);
    }

    public function brand(Request $request, Brand $brand)
    {
        abort_unless($brand->is_active, 404);

        return $this->listing($request, null, $brand);
    }

    protected function listing(Request $request, ?Category $category = null, ?Brand $brand = null)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'array'],
            'category.*' => ['string', 'max:120'],
            'brand' => ['nullable', 'array'],
            'brand.*' => ['string', 'max:120'],
            'size' => ['nullable', 'array'],
            'size.*' => ['string', 'max:30'],
            'color' => ['nullable', 'array'],
            'color.*' => ['string', 'max:50'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'availability' => ['nullable', 'in:in_stock,out_of_stock'],
            'on_sale' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:'.implode(',', array_keys(self::SORTS))],
        ]);

        // Resolve category filter: route category (incl. children) or checked category slugs.
        $categoryIds = null;
        if ($category) {
            $categoryIds = $category->selfAndDescendantIds();
        } elseif (! empty($data['category'])) {
            $categoryIds = Category::active()->whereIn('slug', $data['category'])->get()
                ->flatMap(fn (Category $c) => $c->selfAndDescendantIds())->unique()->values()->all();
        }

        $filters = [
            'q' => $data['q'] ?? null,
            'category_ids' => $categoryIds,
            'brands' => $brand ? [$brand->slug] : ($data['brand'] ?? null),
            'sizes' => $data['size'] ?? null,
            'colors' => $data['color'] ?? null,
            'min_price' => $data['min_price'] ?? null,
            'max_price' => $data['max_price'] ?? null,
            'availability' => $data['availability'] ?? null,
            'on_sale' => (bool) ($data['on_sale'] ?? false),
            'sort' => $data['sort'] ?? 'newest',
        ];

        $products = Product::active()
            ->with(['images', 'variants', 'brand'])
            ->withAvg('approvedReviews', 'rating')
            ->filter($filters)
            ->paginate(12)
            ->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('shop._grid', compact('products'))->render(),
                'total' => $products->total(),
            ]);
        }

        $activeVariantProducts = ProductVariant::whereHas('product', fn ($q) => $q->active());

        return view('shop.index', [
            'products' => $products,
            'category' => $category,
            'brand' => $brand,
            'filters' => $filters,
            'selected' => $data,
            'sorts' => self::SORTS,
            'categories' => Category::active()->roots()->with(['children' => fn ($q) => $q->active()])->orderBy('sort_order')->get(),
            'brands' => Brand::active()->orderBy('name')->get(),
            'sizes' => $this->sortSizes((clone $activeVariantProducts)->whereNotNull('size')->distinct()->pluck('size')),
            'colors' => (clone $activeVariantProducts)->whereNotNull('color')->select('color', 'color_hex')->distinct()->get()->unique('color')->values(),
            'priceCeiling' => (int) ceil((float) Product::active()->max('price')),
        ]);
    }

    /**
     * JSON search suggestions for the header search box.
     */
    public function suggest(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $products = Product::active()->with('images')->filter(['q' => mb_substr($term, 0, 100)])->take(6)->get();

        return response()->json($products->map(fn (Product $p) => [
            'name' => $p->name,
            'price' => money($p->effective_price),
            'image' => $p->image_url,
            'url' => route('product.show', $p),
        ]));
    }

    protected function sortSizes($sizes)
    {
        $order = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', 'Free Size'];

        return $sizes->sortBy(function ($size) use ($order) {
            $i = array_search($size, $order, true);

            return $i === false ? (is_numeric($size) ? 100 + (int) $size : 1000) : $i;
        })->values();
    }
}
