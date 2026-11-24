<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_guests_and_customers_are_blocked(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin/products')->assertForbidden();
    }

    public function test_every_admin_page_renders(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();

        foreach ([
            '/admin', '/admin/products', '/admin/products/create', "/admin/products/{$product->slug}/edit",
            '/admin/categories', '/admin/categories/create', '/admin/brands', '/admin/brands/create',
            '/admin/orders', '/admin/customers', '/admin/coupons', '/admin/coupons/create', '/admin/reviews',
            '/admin/banners', '/admin/banners/create', '/admin/subscribers', '/admin/subscribers/export',
            '/admin/messages', '/admin/settings',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_create_product_with_variants_and_images(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts']);

        $this->actingAs($this->admin())->post('/admin/products', [
            'name' => 'Linen Shirt',
            'sku' => 'LIN-001',
            'category_id' => $category->id,
            'price' => 2500,
            'sale_price' => 1990,
            'is_active' => 1,
            'images' => [UploadedFile::fake()->image('front.jpg', 800, 1000)],
            'variants' => [
                ['size' => 'M', 'color' => 'White', 'color_hex' => '#FFFFFF', 'stock' => 4],
                ['size' => 'L', 'color' => 'White', 'color_hex' => '#FFFFFF', 'stock' => 6],
            ],
        ])->assertRedirect();

        $product = Product::where('sku', 'LIN-001')->firstOrFail();
        $this->assertSame('linen-shirt', $product->slug);
        $this->assertSame(10, $product->stock);
        $this->assertCount(2, $product->variants);
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertExists($product->images->first()->path);
    }

    public function test_sale_price_must_be_below_price_and_duplicate_variants_rejected(): void
    {
        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts']);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/products', [
            'name' => 'X', 'sku' => 'X1', 'category_id' => $category->id, 'price' => 1000, 'sale_price' => 1200,
        ])->assertSessionHasErrors('sale_price');

        $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Y', 'sku' => 'Y1', 'category_id' => $category->id, 'price' => 1000,
            'variants' => [['size' => 'M', 'color' => 'Red', 'stock' => 1], ['size' => 'm', 'color' => 'red', 'stock' => 2]],
        ])->assertSessionHasErrors('variants');
    }

    public function test_cancelling_order_restores_stock_and_cod_delivered_marks_paid(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson('/cart', ['product_id' => $product->id, 'quantity' => 2]);
        $this->post('/checkout', [
            'name' => 'Guest', 'email' => 'g@example.com', 'phone' => '01712345678', 'address' => 'Road 1',
            'city' => 'Dhaka', 'shipping_zone' => 'inside_dhaka', 'payment_method' => 'cod',
        ]);
        $order = Order::first();
        $this->assertSame(3, $product->fresh()->stock);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(5, $product->fresh()->stock);

        // Re-opening the order takes the stock out again.
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'delivered']);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(3, $order->histories()->count());

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee($order->order_number);
        $this->actingAs($admin)->get(route('admin.orders.invoice', $order))->assertOk();
    }

    public function test_settings_update_changes_shipping_rates(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', [
            'store_name' => 'SYRO', 'store_email' => 'hi@syro.test', 'store_phone' => '017', 'shipping_inside_dhaka' => 60,
            'shipping_outside_dhaka' => 150, 'free_shipping_threshold' => 0, 'cod_enabled' => 1,
        ])->assertSessionHas('success');

        $this->assertSame('150', setting('shipping_outside_dhaka'));
        $this->assertSame('0', setting('bkash_enabled'));
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.categories.destroy', $product->category))->assertSessionHas('error');
        $this->assertModelExists($product->category);
    }

    public function test_admin_can_deactivate_customer_but_not_self(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.customers.toggle', $customer));
        $this->assertFalse($customer->fresh()->is_active);

        $this->actingAs($admin)->patch(route('admin.customers.toggle', $admin))->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->is_active);
    }
}
