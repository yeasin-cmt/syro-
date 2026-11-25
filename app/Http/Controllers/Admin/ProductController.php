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
            'trashCount' => Product::onlyTrashed()->count(),
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

        $image->delete();
        $this->deleteFileIfUnused($image->path);

        return back()->with('success', 'Image removed.');
    }

    /**
     * Make an image the main (first) image of the product.
     */
    public function makeMainImage(Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $image) {
            $order = 1;
            foreach ($product->images()->get() as $img) {
                $img->update(['sort_order' => $img->is($image) ? 0 : $order++]);
            }
        });

        return back()->with('success', 'Main image updated.');
    }

    /**
     * Inline edit from the product list: price, sale price, stock and visibility.
     */
    public function quickUpdate(Request $request, Product $product)
    {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], ['sale_price.lt' => 'Sale price must be lower than the price.']);

        $product->price = $data['price'];
        $product->sale_price = $data['sale_price'] ?? null;
        $product->is_active = $request->boolean('is_active');
        // Stock of products with variants is the sum of their variants, so it is not edited here.
        if (! $product->variants()->exists() && isset($data['stock'])) {
            $product->stock = $data['stock'];
        }
        $product->save();

        return back()->with('success', "“{$product->name}” updated.");
    }

    /**
     * Bulk actions on selected products.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['activate', 'deactivate', 'feature', 'unfeature', 'delete', 'increase', 'decrease', 'sale', 'clear_sale'])],
            'percent' => ['required_if:action,increase,decrease,sale', 'nullable', 'numeric', 'min:1', 'max:90'],
        ], [
            'ids.required' => 'Select at least one product first.',
            'percent.required_if' => 'Enter a percentage for this action.',
        ]);

        $products = Product::with('variants')->whereIn('id', $data['ids'])->get();
        $pct = (float) ($data['percent'] ?? 0) / 100;

        DB::transaction(function () use ($products, $data, $pct) {
            foreach ($products as $p) {
                switch ($data['action']) {
                    case 'activate': $p->update(['is_active' => true]); break;
                    case 'deactivate': $p->update(['is_active' => false]); break;
                    case 'feature': $p->update(['is_featured' => true]); break;
                    case 'unfeature': $p->update(['is_featured' => false]); break;
                    case 'delete':
                        $p->update(['is_active' => false]);
                        $p->delete();
                        break;
                    case 'increase':
                    case 'decrease':
                        $factor = $data['action'] === 'increase' ? 1 + $pct : 1 - $pct;
                        $newPrice = max(1, round((float) $p->price * $factor));
                        $newSale = $p->sale_price !== null ? round((float) $p->sale_price * $factor) : null;
                        $p->update(['price' => $newPrice, 'sale_price' => $newSale !== null && $newSale < $newPrice ? $newSale : null]);
                        foreach ($p->variants as $v) {
                            if ($v->price !== null) {
                                $v->update(['price' => max(1, round((float) $v->price * $factor))]);
                            }
                        }
                        break;
                    case 'sale':
                        $p->update(['sale_price' => max(1, round((float) $p->price * (1 - $pct)))]);
                        break;
                    case 'clear_sale':
                        $p->update(['sale_price' => null]);
                        break;
                }
            }
        });

        $labels = [
            'activate' => 'shown in store', 'deactivate' => 'hidden', 'feature' => 'featured', 'unfeature' => 'unfeatured',
            'delete' => 'moved to trash', 'increase' => 'price increased', 'decrease' => 'price decreased',
            'sale' => 'put on sale', 'clear_sale' => 'removed from sale',
        ];

        return back()->with('success', $products->count().' product(s) '.$labels[$data['action']].'.');
    }

    public function duplicate(Product $product)
    {
        $copy = DB::transaction(function () use ($product) {
            $copy = $product->replicate(['slug', 'sku', 'sold_count', 'views']);
            $copy->name = $product->name.' (Copy)';
            $copy->slug = $this->uniqueSlug(Product::class, $copy->name);
            $sku = $product->sku.'-COPY';
            $n = 2;
            while (Product::withTrashed()->where('sku', $sku)->exists()) {
                $sku = $product->sku.'-COPY'.$n++;
            }
            $copy->sku = $sku;
            $copy->is_active = false; // review before publishing
            $copy->sold_count = 0;
            $copy->views = 0;
            $copy->save();

            foreach ($product->variants as $v) {
                $copy->variants()->create($v->only(['size', 'color', 'color_hex', 'stock', 'price']) + ['sku' => $v->sku ? $v->sku.'-C' : null]);
            }
            // Photos are shared with the original; a file is only deleted once no product uses it.
            foreach ($product->images as $img) {
                $copy->images()->create(['path' => $img->path, 'alt' => $img->alt, 'sort_order' => $img->sort_order]);
            }

            return $copy;
        });

        return redirect()->route('admin.products.edit', $copy)->with('success', 'Product duplicated. It is hidden until you make it visible.');
    }

    /* ---------------- Trash ---------------- */

    public function trash()
    {
        return view('admin.products.trash', [
            'products' => Product::onlyTrashed()->with(['images', 'category'])->latest('deleted_at')->paginate(20),
        ]);
    }

    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return back()->with('success', "“{$product->name}” restored. It is hidden — make it visible when ready.");
    }

    public function forceDelete(int $id)
    {
        $product = Product::onlyTrashed()->with('images')->findOrFail($id);
        $paths = $product->images->pluck('path');

        $product->forceDelete(); // order history keeps the product name and price; image rows cascade
        $paths->each(fn ($path) => $this->deleteFileIfUnused($path));

        return back()->with('success', "“{$product->name}” permanently deleted.");
    }

    /* ---------------------------------------------------------------- */

    /**
     * Remove a stored image file once no product image refers to it any more.
     * Storage errors (e.g. the cloud is unreachable) never block the database change.
     */
    protected function deleteFileIfUnused(string $path): void
    {
        if (str_starts_with($path, 'http') || ProductImage::where('path', $path)->exists()) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }

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
