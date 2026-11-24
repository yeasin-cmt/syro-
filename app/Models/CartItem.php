<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['user_id', 'cart_token', 'product_id', 'product_variant_id', 'quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getUnitPriceAttribute(): float
    {
        return $this->variant ? $this->variant->unitPrice($this->product) : $this->product->effective_price;
    }

    public function getLineTotalAttribute(): float
    {
        return round($this->unit_price * $this->quantity, 2);
    }

    public function getAvailableStockAttribute(): int
    {
        return $this->variant ? (int) $this->variant->stock : (int) $this->product->stock;
    }
}
