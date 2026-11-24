<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENT = 'percent';

    protected $fillable = [
        'code', 'type', 'value', 'min_order_amount', 'max_discount', 'usage_limit',
        'starts_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * Returns a human-readable reason the coupon cannot be used, or null when valid.
     */
    public function validationError(float $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'This coupon is not active.';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'This coupon is not active yet.';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'This coupon has expired.';
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'This coupon has reached its usage limit.';
        }
        if ($subtotal < (float) $this->min_order_amount) {
            return 'Spend at least '.money($this->min_order_amount).' to use this coupon.';
        }

        return null;
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === self::TYPE_PERCENT
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal));
    }

    public function getLabelAttribute(): string
    {
        return $this->type === self::TYPE_PERCENT ? rtrim(rtrim((string) $this->value, '0'), '.').'% off' : money($this->value).' off';
    }
}
