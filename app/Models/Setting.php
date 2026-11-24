<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected const CACHE_KEY = 'syro.settings';

    /** Defaults used until an admin saves their own values. */
    public const DEFAULTS = [
        'store_name' => 'SYRO',
        'store_tagline' => 'Modern Shopping. Premium Experience.',
        'store_email' => 'support@syro.com.bd',
        'store_phone' => '+880 1700-000000',
        'store_address' => 'House 12, Road 7, Gulshan 1, Dhaka 1212, Bangladesh',
        'announcement_text' => 'Free delivery on orders over ৳3,000 · Easy 7-day returns',
        'shipping_inside_dhaka' => '70',
        'shipping_outside_dhaka' => '130',
        'free_shipping_threshold' => '3000',
        'cod_enabled' => '1',
        'bkash_enabled' => '1',
        'bkash_number' => '01700-000000',
        'social_facebook' => 'https://facebook.com/',
        'social_instagram' => 'https://instagram.com/',
        'social_youtube' => 'https://youtube.com/',
        'social_tiktok' => 'https://tiktok.com/',
    ];

    public static function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return static::query()->pluck('value', 'key')->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $values = static::cached();

        // Saved value first, then the store default, then the caller's fallback.
        return $values[$key] ?? self::DEFAULTS[$key] ?? $default;
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }
}
