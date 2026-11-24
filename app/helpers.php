<?php

use App\Models\Setting;

if (! function_exists('money')) {
    /**
     * Format an amount as Bangladeshi Taka, e.g. ৳1,250.
     */
    function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;
        $decimals = floor($amount) == $amount ? 0 : 2;

        return config('app.currency_symbol', '৳').number_format($amount, $decimals);
    }
}

if (! function_exists('setting')) {
    /**
     * Read a store setting (cached). Falls back to $default when unset.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}
