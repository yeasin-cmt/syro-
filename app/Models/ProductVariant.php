<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'size', 'color', 'color_hex', 'sku', 'stock', 'price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'stock' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getLabelAttribute(): string
    {
        return collect([$this->size, $this->color])->filter()->implode(' / ');
    }

    /**
     * Unit price for this variant, falling back to the product's current price.
     */
    public function unitPrice(Product $product): float
    {
        return $this->price !== null ? (float) $this->price : $product->effective_price;
    }
}
