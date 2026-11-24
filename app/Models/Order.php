<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Unpaid',
        'pending_verification' => 'Pending verification',
        'paid' => 'Paid',
        'refunded' => 'Refunded',
        'failed' => 'Failed',
    ];

    public const PAYMENT_METHODS = [
        'cod' => 'Cash on Delivery',
        'bkash' => 'bKash',
    ];

    public const SHIPPING_ZONES = [
        'inside_dhaka' => 'Inside Dhaka',
        'outside_dhaka' => 'Outside Dhaka',
    ];

    /** Statuses in which stock has been given back to inventory. */
    public const RESTOCKED_STATUSES = ['cancelled', 'returned'];

    protected $fillable = [
        'order_number', 'user_id', 'name', 'email', 'phone', 'address', 'city', 'postcode',
        'shipping_zone', 'notes', 'subtotal', 'discount', 'shipping_cost', 'total', 'coupon_code',
        'payment_method', 'payment_status', 'transaction_id', 'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'SYR-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? strtoupper($this->payment_method);
    }

    public function getShippingZoneLabelAttribute(): string
    {
        return self::SHIPPING_ZONES[$this->shipping_zone] ?? $this->shipping_zone;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'confirmed', 'processing' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'cancelled', 'returned' => 'danger',
            default => 'secondary',
        };
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }

    /**
     * Move the order to a new status, recording history and adjusting stock.
     */
    public function transitionTo(string $status, ?string $note = null, ?int $byUserId = null): void
    {
        if ($status === $this->status) {
            return;
        }

        DB::transaction(function () use ($status, $note, $byUserId) {
            $wasRestocked = in_array($this->status, self::RESTOCKED_STATUSES, true);
            $willRestock = in_array($status, self::RESTOCKED_STATUSES, true);

            if (! $wasRestocked && $willRestock) {
                $this->adjustStock(+1);
            } elseif ($wasRestocked && ! $willRestock) {
                $this->adjustStock(-1);
            }

            $this->update(['status' => $status]);
            $this->histories()->create(['status' => $status, 'note' => $note, 'user_id' => $byUserId]);
        });
    }

    /**
     * $direction = +1 puts items back into stock, -1 takes them out again.
     */
    protected function adjustStock(int $direction): void
    {
        foreach ($this->items()->get() as $item) {
            $qty = $item->quantity * $direction;

            if ($item->product_variant_id && $variant = ProductVariant::lockForUpdate()->find($item->product_variant_id)) {
                $variant->forceFill(['stock' => max(0, $variant->stock + $qty)])->save();
            }
            if ($item->product_id && $product = Product::withTrashed()->lockForUpdate()->find($item->product_id)) {
                $product->forceFill([
                    'stock' => max(0, $product->stock + $qty),
                    'sold_count' => max(0, $product->sold_count - $qty),
                ])->saveQuietly();
            }
        }
    }
}
