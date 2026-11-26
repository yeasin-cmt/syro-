<?php

use App\Models\Setting;

if (! function_exists('money')) {
    /**
     * Format an amount as Bangladeshi Taka, e.g. BDT 1,250.
     */
    function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;
        $decimals = floor($amount) == $amount ? 0 : 2;

        return config('app.currency', 'BDT').' '.number_format($amount, $decimals);
    }
}

if (! function_exists('media_url')) {
    /**
     * Public URL for a file stored on the "public" disk (product images, banners, logos).
     *
     * On Cloudinary the delivery URL is built directly (no API request per image) and
     * served with automatic format/quality optimisation. Locally it is /storage/<path>.
     */
    function media_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = config('filesystems.disks.public');
        if (($disk['driver'] ?? null) === 'cloudinary') {
            $cloud = parse_url((string) ($disk['url'] ?? ''), PHP_URL_HOST);
            $publicId = preg_replace('/\.[A-Za-z0-9]+$/', '', ltrim(str_replace('\\', '/', $path), '/'));

            return "https://res.cloudinary.com/{$cloud}/image/upload/f_auto,q_auto/{$publicId}";
        }
        if (($disk['driver'] ?? null) === 'vercel_blob') {
            $base = rtrim($disk['url'] ?: App\Filesystem\VercelBlobAdapter::publicBaseUrl((string) ($disk['token'] ?? '')), '/');

            return $base.'/'.implode('/', array_map('rawurlencode', explode('/', ltrim(str_replace('\\', '/', $path), '/'))));
        }

        return Illuminate\Support\Facades\Storage::disk('public')->url($path);
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
