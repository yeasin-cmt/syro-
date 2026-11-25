<?php

namespace App\Providers;

use App\Filesystem\VercelBlobAdapter;
use App\Models\Category;
use App\Services\CartService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CartService::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // "vercel_blob" disk driver — image storage on Vercel, where local files are not kept.
        Storage::extend('vercel_blob', function ($app, array $config) {
            $adapter = new VercelBlobAdapter((string) $config['token'], $config['url'] ?? null);

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        // Wishlist state for product cards — queried once per request.
        View::composer('partials.product-card', function ($view) {
            $attrs = request()->attributes;
            if (! $attrs->has('wishlist_ids')) {
                $attrs->set('wishlist_ids', auth()->check() ? auth()->user()->wishlist()->pluck('products.id')->all() : []);
            }
            $view->with('wishlistIds', $attrs->get('wishlist_ids'));
        });

        // Header / footer data for every storefront page.
        View::composer('layouts.app', function ($view) {
            $navCategories = Cache::remember('syro.nav_categories', 600, fn () => Category::active()->roots()
                ->with(['children' => fn ($q) => $q->active()])
                ->orderBy('sort_order')->get());

            $view->with([
                'navCategories' => $navCategories,
                'cartCount' => app(CartService::class)->count(),
                'wishlistCount' => auth()->check() ? auth()->user()->wishlist()->count() : 0,
            ]);
        });
    }
}
