<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['images', 'category', 'brand'])
            ->withCount('variants')
            ->when($request->q, fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")))
            ->when($request->category, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->stock === 'low', fn ($q) => $q->whereBetween('stock', [1, 5]))
            ->when($request->stock === 'out', fn ($q) => $q->where('stock', 0))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', $this->formData(new Product(['is_active' => true])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $product = DB::transaction(function () use ($request, $data) {
            $product = Product::create($data + ['slug' => $this->uniqueSlug(Product::class, $data['name'])]);
            $this->syncVariants($product, $request->input('variants', []));
            $this->storeImages($product, $request);

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'variants']);

        return view('admin.products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);

        DB::transaction(function () use ($request, $product, $data) {
            if ($data['name'] !== $product->name) {
                $data['slug'] = $this->uniqueSlug(Product::class, $data['name'], $product->id);
            }
            $product->update($data);
            $this->syncVariants($product, $request->input('variants', []));
            $this->storeImages($product, $request);
        });

        return redirect()->route('admin.products.edit', $product->fresh())->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        // Soft delete keeps order history intact.
        $product->update(['is_active' => false]);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', "“{$product->name}” deleted.");
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);

        if (! str_starts_with($image->path, 'http')) {
            Storage::disk('public')->delete($image->path);
        }
        $image->delete();

        return back()->with('success', 'Image removed.');
    }

    /* ---------------------------------------------------------------- */

    protected function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::with('parent')->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ];
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')->ignore($product?->id)],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:190'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.size' => ['nullable', 'string', 'max:30'],
            'variants.*.color' => ['nullable', 'string', 'max:50'],
            'variants.*.color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'variants.*.sku' => ['nullable', 'string', 'max:80'],
            'variants.*.stock' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
        ], ['sale_price.lt' => 'The sale price must be lower than the regular price.']);

        // Reject duplicate size/colour combinations up-front with a friendly message.
        $combos = collect($request->input('variants', []))
            ->filter(fn ($v) => filled($v['size'] ?? null) || filled($v['color'] ?? null))
            ->map(fn ($v) => strtolower(trim($v['size'] ?? '')).'|'.strtolower(trim($v['color'] ?? '')));
        if ($combos->count() !== $combos->unique()->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['variants' => 'Each size / colour combination may only appear once.']);
        }

        foreach (['is_featured', 'is_new_arrival', 'is_trending', 'is_active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        $data['stock'] = (int) ($data['stock'] ?? 0);

        unset($data['images'], $data['variants']);

        return $data;
    }

    protected function syncVariants(Product $product, array $rows): void
    {
        $rows = collect($rows)->filter(fn ($v) => filled($v['size'] ?? null) || filled($v['color'] ?? null));
        $keepIds = [];

        foreach ($rows as $row) {
            $attributes = [
                'size' => filled($row['size'] ?? null) ? trim($row['size']) : null,
                'color' => filled($row['color'] ?? null) ? trim($row['color']) : null,
                'color_hex' => $row['color_hex'] ?? null,
                'sku' => $row['sku'] ?? null,
                'stock' => (int) ($row['stock'] ?? 0),
                'price' => filled($row['price'] ?? null) ? $row['price'] : null,
            ];

            $variant = ! empty($row['id']) ? $product->variants()->whereKey($row['id'])->first() : null;
            if ($variant) {
                $variant->update($attributes);
            } else {
                $variant = $product->variants()->create($attributes);
            }
            $keepIds[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keepIds)->delete();
        $product->syncStockFromVariants();
    }

    protected function storeImages(Product $product, Request $request): void
    {
        $next = (int) $product->images()->max('sort_order') + 1;

        foreach ($request->file('images', []) as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'alt' => $product->name,
                'sort_order' => $next++,
            ]);
        }
    }
}
