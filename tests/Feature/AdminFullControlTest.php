<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFullControlTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_new_admin_pages_render(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();

        foreach (['/admin/products/trash', '/admin/pages', '/admin/pages/create', '/admin/pages/about/edit',
            '/admin/customers/create', "/admin/customers/{$customer->id}/edit", '/admin/customers?role=admin', '/admin/customers?role=all'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_quick_update_changes_price_sale_stock_and_visibility(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'stock' => 3, 'is_active' => true]);

        $this->actingAs($this->admin())->patch(route('admin.products.quick', $product), [
            'price' => 1500, 'sale_price' => 1200, 'stock' => 25, 'is_active' => 0,
        ])->assertSessionHas('success');

        $product->refresh();
        $this->assertSame('1500.00', $product->price);
        $this->assertSame('1200.00', $product->sale_price);
        $this->assertSame(25, $product->stock);
        $this->assertFalse($product->is_active);
    }

    public function test_quick_update_rejects_sale_above_price_and_keeps_variant_stock(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['price' => 1000]);
        $product->variants()->create(['size' => 'M', 'stock' => 4]);
        $product->syncStockFromVariants();

        $this->actingAs($admin)->patch(route('admin.products.quick', $product), ['price' => 1000, 'sale_price' => 1100])
            ->assertSessionHasErrors('sale_price');

        $this->actingAs($admin)->patch(route('admin.products.quick', $product), ['price' => 900, 'stock' => 99, 'is_active' => 1]);
        $this->assertSame(4, $product->fresh()->stock, 'Variant products keep stock = sum of variants');
        $this->assertSame('900.00', $product->fresh()->price);
    }

    public function test_bulk_price_changes(): void
    {
        $admin = $this->admin();
        $a = Product::factory()->create(['price' => 1000, 'sale_price' => 800]);
        $b = Product::factory()->create(['price' => 2000]);
        $variant = $b->variants()->create(['size' => 'L', 'stock' => 2, 'price' => 2200]);
        $ids = [$a->id, $b->id];

        $this->actingAs($admin)->post(route('admin.products.bulk'), ['ids' => $ids, 'action' => 'increase', 'percent' => 10])->assertSessionHas('success');
        $this->assertSame('1100.00', $a->fresh()->price);
        $this->assertSame('880.00', $a->fresh()->sale_price);
        $this->assertSame('2200.00', $b->fresh()->price);
        $this->assertSame('2420.00', $variant->fresh()->price);

        $this->actingAs($admin)->post(route('admin.products.bulk'), ['ids' => $ids, 'action' => 'sale', 'percent' => 25]);
        $this->assertSame('825.00', $a->fresh()->sale_price);
        $this->assertSame('1650.00', $b->fresh()->sale_price);

        $this->actingAs($admin)->post(route('admin.products.bulk'), ['ids' => $ids, 'action' => 'clear_sale']);
        $this->assertNull($a->fresh()->sale_price);

        $this->actingAs($admin)->post(route('admin.products.bulk'), ['ids' => $ids, 'action' => 'decrease'])->assertSessionHasErrors('percent');
        $this->actingAs($admin)->post(route('admin.products.bulk'), ['action' => 'activate'])->assertSessionHasErrors('ids');
    }

    public function test_bulk_visibility_and_delete_then_restore_and_force_delete(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $product = Product::factory()->create();
        $path = UploadedFile::fake()->image('p.jpg')->store('products', 'public');
        $product->images()->create(['path' => $path]);

        $this->actingAs($admin)->post(route('admin.products.bulk'), ['ids' => [$product->id], 'action' => 'deactivate']);
        $this->assertFalse($product->fresh()->is_active);

        $this->actingAs($admin)->post(route('admin.products.bulk'), ['ids' => [$product->id], 'action' => 'delete']);
        $this->assertSoftDeleted($product);
        $this->actingAs($admin)->get(route('admin.products.trash'))->assertSee($product->name);

        $this->actingAs($admin)->patch(route('admin.products.restore', $product->id));
        $this->assertNotSoftDeleted($product);

        $product->delete();
        $this->actingAs($admin)->delete(route('admin.products.force-delete', $product->id));
        $this->assertModelMissing($product);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_duplicate_and_make_main_image(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create(['sku' => 'ABC-1']);
        $product->variants()->create(['size' => 'S', 'stock' => 3]);
        $first = $product->images()->create(['path' => 'https://example.com/1.jpg', 'sort_order' => 0]);
        $second = $product->images()->create(['path' => 'https://example.com/2.jpg', 'sort_order' => 1]);

        $this->actingAs($admin)->patch(route('admin.products.images.main', [$product, $second]));
        $this->assertSame($second->id, $product->images()->first()->id);

        $this->actingAs($admin)->post(route('admin.products.duplicate', $product))->assertRedirect();
        $copy = Product::where('sku', 'ABC-1-COPY')->firstOrFail();
        $this->assertFalse($copy->is_active);
        $this->assertCount(1, $copy->variants);
        $this->assertCount(2, $copy->images);
    }

    public function test_admin_manages_users_and_roles(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.customers.store'), [
            'name' => 'Store Manager', 'email' => 'manager@example.com', 'role' => 'admin', 'is_active' => 1,
            'password' => 'manager-pass', 'password_confirmation' => 'manager-pass',
        ])->assertRedirect();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $this->assertTrue($manager->isAdmin());
        $this->post('/logout');
        $this->post('/login', ['email' => 'manager@example.com', 'password' => 'manager-pass'])->assertRedirect('/admin');

        // Reset password + demote to customer.
        $this->actingAs($admin)->put(route('admin.customers.update', $manager), [
            'name' => 'Store Manager', 'email' => 'manager@example.com', 'role' => 'customer', 'is_active' => 1,
            'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ]);
        $this->assertFalse($manager->fresh()->isAdmin());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brand-new-pass', $manager->fresh()->password));

        $this->actingAs($admin)->delete(route('admin.customers.destroy', $manager));
        $this->assertModelMissing($manager);
    }

    public function test_admin_cannot_lock_themselves_out(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.customers.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'customer', 'is_active' => 1,
        ])->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->isAdmin());

        $this->actingAs($admin)->delete(route('admin.customers.destroy', $admin))->assertSessionHas('error');
        $this->assertModelExists($admin);
    }

    public function test_deleting_order_restores_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $this->postJson('/cart', ['product_id' => $product->id, 'quantity' => 2]);
        $this->post('/checkout', [
            'name' => 'Test', 'email' => 't@example.com', 'phone' => '01712345678', 'address' => 'Road 1',
            'city' => 'Dhaka', 'shipping_zone' => 'inside_dhaka', 'payment_method' => 'cod',
        ]);
        $order = Order::first();
        $this->assertSame(3, $product->fresh()->stock);

        $this->actingAs($this->admin())->delete(route('admin.orders.destroy', $order))->assertRedirect(route('admin.orders.index'));
        $this->assertModelMissing($order);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_admin_edits_pages_and_storefront_shows_them(): void
    {
        $admin = $this->admin();
        $about = Page::where('slug', 'about')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.pages.update', $about), [
            'title' => 'Our Story', 'slug' => 'about', 'layout' => 'standard', 'intro' => 'Hello Dhaka',
            'body' => "## Founded 2026\nWe started small.\n\n- Quality\n- Honesty", 'is_active' => 1,
        ])->assertRedirect();

        $this->post('/logout');
        $this->get('/pages/about')->assertOk()->assertSee('Our Story')->assertSee('Founded 2026')->assertSee('<li class="mb-1">Honesty</li>', false);

        $this->actingAs($admin)->post(route('admin.pages.store'), [
            'title' => 'Size Guide', 'layout' => 'standard', 'body' => 'Measure carefully.',
        ])->assertRedirect();
        $this->post('/logout');
        $this->get('/pages/size-guide')->assertNotFound(); // created hidden (is_active unchecked)

        $page = Page::where('slug', 'size-guide')->firstOrFail();
        $page->update(['is_active' => true]);
        $this->get('/pages/size-guide')->assertOk()->assertSee('Measure carefully.');
    }
}
