<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/category/{category}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/brand/{brand}', [ShopController::class, 'brand'])->name('shop.brand');
Route::get('/search/suggest', [ShopController::class, 'suggest'])->name('search.suggest')->middleware('throttle:60,1');
Route::get('/product/{product}', [ProductController::class, 'show'])->name('product.show');

Route::controller(CartController::class)->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store')->middleware('throttle:60,1');
    Route::patch('/{item}', 'update')->name('update')->whereNumber('item');
    Route::delete('/{item}', 'destroy')->name('destroy')->whereNumber('item');
    Route::post('/coupon', 'applyCoupon')->name('coupon.apply')->middleware('throttle:20,1');
    Route::delete('/coupon', 'removeCoupon')->name('coupon.remove');
});

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

Route::get('/track-order', [OrderTrackingController::class, 'index'])->name('track.index');
Route::post('/track-order', [OrderTrackingController::class, 'show'])->name('track.show')->middleware('throttle:20,1');

Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store')->middleware('throttle:10,1');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,1');
Route::get('/pages/{page}', [PageController::class, 'show'])->name('page');

/*
|--------------------------------------------------------------------------
| Authentication (laravel/ui) + customer account
|--------------------------------------------------------------------------
*/
Auth::routes();
Route::redirect('/home', '/account');

Route::middleware('auth')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/product/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('throttle:10,1');

    Route::controller(AccountController::class)->prefix('account')->name('account.')->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/orders', 'orders')->name('orders');
        Route::get('/orders/{order}', 'showOrder')->name('orders.show');
        Route::post('/orders/{order}/cancel', 'cancelOrder')->name('orders.cancel');
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile', 'updateProfile')->name('profile.update');
        Route::put('/password', 'updatePassword')->name('password.update');
    });
});

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('products/trash', [Admin\ProductController::class, 'trash'])->name('products.trash');
    Route::post('products/bulk', [Admin\ProductController::class, 'bulk'])->name('products.bulk');
    Route::patch('products/trash/{id}/restore', [Admin\ProductController::class, 'restore'])->name('products.restore')->whereNumber('id');
    Route::delete('products/trash/{id}', [Admin\ProductController::class, 'forceDelete'])->name('products.force-delete')->whereNumber('id');
    Route::resource('products', Admin\ProductController::class)->except('show');
    Route::patch('products/{product}/quick', [Admin\ProductController::class, 'quickUpdate'])->name('products.quick');
    Route::post('products/{product}/duplicate', [Admin\ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::delete('products/{product}/images/{image}', [Admin\ProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::patch('products/{product}/images/{image}/main', [Admin\ProductController::class, 'makeMainImage'])->name('products.images.main');
    Route::resource('pages', Admin\PageController::class)->except('show');
    Route::resource('categories', Admin\CategoryController::class)->except('show');
    Route::resource('brands', Admin\BrandController::class)->except('show');
    Route::resource('coupons', Admin\CouponController::class)->except('show');
    Route::resource('banners', Admin\BannerController::class)->except('show');

    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{order}/payment', [Admin\OrderController::class, 'updatePayment'])->name('orders.payment');
    Route::get('orders/{order}/invoice', [Admin\OrderController::class, 'invoice'])->name('orders.invoice');
    Route::delete('orders/{order}', [Admin\OrderController::class, 'destroy'])->name('orders.destroy');

    Route::get('customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/create', [Admin\CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [Admin\CustomerController::class, 'store'])->name('customers.store');
    Route::get('customers/{user}', [Admin\CustomerController::class, 'show'])->name('customers.show')->whereNumber('user');
    Route::get('customers/{user}/edit', [Admin\CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('customers/{user}', [Admin\CustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{user}', [Admin\CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::patch('customers/{user}/toggle', [Admin\CustomerController::class, 'toggle'])->name('customers.toggle');

    Route::get('reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('subscribers', [Admin\SubscriberController::class, 'index'])->name('subscribers.index');
    Route::get('subscribers/export', [Admin\SubscriberController::class, 'export'])->name('subscribers.export');
    Route::delete('subscribers/{subscriber}', [Admin\SubscriberController::class, 'destroy'])->name('subscribers.destroy');

    Route::get('messages', [Admin\MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{message}', [Admin\MessageController::class, 'show'])->name('messages.show');
    Route::delete('messages/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/branding', [Admin\SettingController::class, 'updateBranding'])->name('settings.branding');
    Route::delete('settings/branding/{slot}', [Admin\SettingController::class, 'destroyBranding'])
        ->whereIn('slot', ['logo', 'logo_light', 'favicon'])->name('settings.branding.destroy');
});
