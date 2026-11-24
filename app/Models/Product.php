<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'short_description', 'description',
        'price', 'sale_price', 'stock', 'is_featured', 'is_new_arrival', 'is_trending', 'is_active',
        'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_new_arrival' => 'boolean',
            'is_trending' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ---------------- Relations ---------------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /* ---------------- Scopes ---------------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Shop filters. Accepts the validated query-string array from ShopController.
     */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        $query->when($f['q'] ?? null, function (Builder $q, string $term) {
            $q->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });
        });

        $query->when($f['category_ids'] ?? null, fn (Builder $q, array $ids) => $q->whereIn('category_id', $ids));
        $query->when($f['brands'] ?? null, fn (Builder $q, array $slugs) => $q->whereHas('brand', fn ($b) => $b->whereIn('slug', $slugs)));
        $query->when($f['sizes'] ?? null, fn (Builder $q, array $sizes) => $q->whereHas('variants', fn ($v) => $v->whereIn('size', $sizes)->where('stock', '>', 0)));
        $query->when($f['colors'] ?? null, fn (Builder $q, array $colors) => $q->whereHas('variants', fn ($v) => $v->whereIn('color', $colors)->where('stock', '>', 0)));

        $priceExpr = 'COALESCE(sale_price, price)';
        // "(? + 0)" forces a numeric comparison on every driver (PDO binds floats as strings).
        $query->when(isset($f['min_price']) && $f['min_price'] !== null, fn (Builder $q) => $q->whereRaw("{$priceExpr} >= (? + 0)", [(float) $f['min_price']]));
        $query->when(isset($f['max_price']) && $f['max_price'] !== null, fn (Builder $q) => $q->whereRaw("{$priceExpr} <= (? + 0)", [(float) $f['max_price']]));

        $query->when(($f['availability'] ?? null) === 'in_stock', fn (Builder $q) => $q->where('stock', '>', 0));
        $query->when(($f['availability'] ?? null) === 'out_of_stock', fn (Builder $q) => $q->where('stock', 0));
        $query->when($f['on_sale'] ?? false, fn (Builder $q) => $q->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price'));

        return match ($f['sort'] ?? 'newest') {
            'price_asc' => $query->orderByRaw("{$priceExpr} asc"),
            'price_desc' => $query->orderByRaw("{$priceExpr} desc"),
            'popular' => $query->orderByDesc('sold_count'),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };
    }

    /* ---------------- Accessors / helpers ---------------- */

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->is_on_sale ? $this->sale_price : $this->price);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && (float) $this->sale_price < (float) $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        return $this->is_on_sale ? (int) round(100 - ((float) $this->sale_price / (float) $this->price * 100)) : 0;
    }

    public function getImageUrlAttribute(): string
    {
        return $this->images->first()?->url ?? asset('images/placeholder.svg');
    }

    public function getHoverImageUrlAttribute(): ?string
    {
        return $this->images->get(1)?->url;
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    public function getAverageRatingAttribute(): float
    {
        return round((float) ($this->approved_reviews_avg_rating ?? $this->approvedReviews()->avg('rating') ?? 0), 1);
    }

    public function hasVariants(): bool
    {
        return $this->variants->isNotEmpty();
    }

    public function availableSizes(): Collection
    {
        return $this->variants->pluck('size')->filter()->unique()->values();
    }

    public function availableColors(): Collection
    {
        return $this->variants->filter(fn ($v) => $v->color)->unique('color')
            ->map(fn ($v) => ['name' => $v->color, 'hex' => $v->color_hex ?: '#cccccc'])->values();
    }

    /**
     * Keep the product's aggregate stock equal to the sum of its variants.
     */
    public function syncStockFromVariants(): void
    {
        if ($this->variants()->exists()) {
            $this->forceFill(['stock' => (int) $this->variants()->sum('stock')])->saveQuietly();
        }
    }
}
