<?php

namespace Tests\Feature;

use App\Mail\OrderPlaced;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShoppingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rahim Uddin',
            'email' => 'rahim@example.com',
            'phone' => '01712345678',
            'address' => 'House 5, Road 2, Banani',
            'city' => 'Dhaka',
            'shipping_zone' => 'inside_dhaka',
            'payment_method' => 'cod',
        ], $overrides);
    }

    public function test_public_pages_render(): void
    {
        $product = Product::factory()->create();

        foreach (['/', '/shop', '/cart', '/track-order', '/contact', '/pages/about', '/pages/faq', "/product/{$product->slug}"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/pages/does-not-exist')->assertNotFound();
    }

    public function test_inactive_product_is_hidden(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->get("/product/{$product->slug}")->assertNotFound();
        $this->get('/shop')->assertDontSee($product->name);
    }

    public function test_shop_filters_and_ajax_response(): void
    {
        $cheap = Product::factory()->create(['name' => 'Budget Tee', 'price' => 500]);
        $pricey = Product::factory()->create(['name' => 'Luxury Coat', 'price' => 9000]);

        $this->get('/shop?max_price=1000')->assertSee($cheap->name)->assertDontSee($pricey->name);
        $this->get('/shop?q=Luxury')->assertSee($pricey->name)->assertDontSee($cheap->name);

        $this->getJson('/shop?min_price=5000', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJson(['total' => 1])->assertJsonStructure(['html', 'total']);
    }

    public function test_guest_can_add_to_cart_and_checkout_with_cod(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['price' => 1500, 'stock' => 5]);

        $this->postJson('/cart', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()->assertJson(['count' => 2]);

        $response = $this->post('/checkout', $this->checkoutPayload());

        $order = Order::first();
        $response->assertRedirect(route('checkout.success', $order));
        $this->assertSame('3000.00', $order->subtotal);
        $this->assertSame('0.00', $order->shipping_cost, 'Free delivery at the ৳3,000 threshold');
        $this->assertSame('pending', $order->status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(2, $product->fresh()->sold_count);
        Mail::assertSent(OrderPlaced::class);

        // Guest can view their own confirmation; the cart is now empty.
        $this->get(route('checkout.success', $order))->assertOk()->assertSee($order->order_number);
        $this->getJson('/cart')->assertOk();
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $product = Product::factory()->create(['stock' => 1]);

        $this->postJson('/cart', ['product_id' => $product->id, 'quantity' => 2])
            ->assertStatus(422)->assertJsonValidationErrors('quantity');
    }

    public function test_variant_product_requires_variant_and_tracks_variant_stock(): void
    {
        $product = Product::factory()->create(['stock' => 0]);
        $variant = $product->variants()->create(['size' => 'M', 'color' => 'Black', 'stock' => 3]);
        $product->syncStockFromVariants();

        $this->postJson('/cart', ['product_id' => $product->id])->assertStatus(422)->assertJsonValidationErrors('variant');
        $this->postJson('/cart', ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2])->assertOk();

        $this->post('/checkout', $this->checkoutPayload(['shipping_zone' => 'outside_dhaka']))->assertRedirect();

        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame('130.00', Order::first()->shipping_cost);
    }

    public function test_checkout_rejects_when_stock_ran_out_after_adding(): void
    {
        $product = Product::factory()->create(['stock' => 2]);
        $this->postJson('/cart', ['product_id' => $product->id, 'quantity' => 2])->assertOk();

        $product->update(['stock' => 1]); // someone else bought one

        $this->post('/checkout', $this->checkoutPayload())->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_coupon_applies_discount_and_counts_usage(): void
    {
        Coupon::create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'min_order_amount' => 1000, 'is_active' => true]);
        $product = Product::factory()->create(['price' => 2000]);

        $this->postJson('/cart', ['product_id' => $product->id])->assertOk();
        $this->postJson('/cart/coupon', ['coupon' => 'save10'])->assertOk();
        $this->post('/checkout', $this->checkoutPayload());

        $order = Order::first();
        $this->assertSame('200.00', $order->discount);
        $this->assertSame('70.00', $order->shipping_cost);
        $this->assertSame('1870.00', $order->total);
        $this->assertSame(1, Coupon::first()->used_count);
    }

    public function test_invalid_and_ineligible_coupons_are_rejected(): void
    {
        Coupon::create(['code' => 'BIG', 'type' => 'fixed', 'value' => 500, 'min_order_amount' => 5000, 'is_active' => true]);
        $product = Product::factory()->create(['price' => 1000]);
        $this->postJson('/cart', ['product_id' => $product->id]);

        $this->postJson('/cart/coupon', ['coupon' => 'NOPE'])->assertStatus(422);
        $this->postJson('/cart/coupon', ['coupon' => 'BIG'])->assertStatus(422)->assertJsonValidationErrors('coupon');
    }

    public function test_bkash_requires_transaction_id(): void
    {
        $product = Product::factory()->create();
        $this->postJson('/cart', ['product_id' => $product->id]);

        $this->post('/checkout', $this->checkoutPayload(['payment_method' => 'bkash']))->assertSessionHasErrors('transaction_id');

        $this->post('/checkout', $this->checkoutPayload(['payment_method' => 'bkash', 'transaction_id' => '9BC4D7XK2L']))->assertRedirect();
        $this->assertSame('pending_verification', Order::first()->payment_status);
    }

    public function test_invalid_bangladeshi_phone_is_rejected(): void
    {
        $product = Product::factory()->create();
        $this->postJson('/cart', ['product_id' => $product->id]);

        $this->post('/checkout', $this->checkoutPayload(['phone' => '12345']))->assertSessionHasErrors('phone');
    }

    public function test_guest_cart_is_merged_on_login(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);
        $product = Product::factory()->create();

        $this->postJson('/cart', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);

        $this->assertDatabaseHas('cart_items', ['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_customer_can_cancel_pending_order_and_stock_is_restored(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 4]);

        $this->actingAs($user)->postJson('/cart', ['product_id' => $product->id, 'quantity' => 3]);
        $this->actingAs($user)->post('/checkout', $this->checkoutPayload());
        $order = Order::first();
        $this->assertSame(1, $product->fresh()->stock);

        $this->actingAs($user)->post(route('account.orders.cancel', $order))->assertRedirect();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_customers_cannot_see_each_others_orders(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($owner)->postJson('/cart', ['product_id' => $product->id]);
        $this->actingAs($owner)->post('/checkout', $this->checkoutPayload());
        $order = Order::first();

        $this->actingAs($other)->get(route('account.orders.show', $order))->assertNotFound();
        $this->actingAs($other)->post(route('account.orders.cancel', $order))->assertNotFound();
        $this->actingAs($other)->get(route('checkout.success', $order))->assertNotFound();
    }

    public function test_order_tracking_requires_matching_phone(): void
    {
        $product = Product::factory()->create();
        $this->postJson('/cart', ['product_id' => $product->id]);
        $this->post('/checkout', $this->checkoutPayload());
        $order = Order::first();

        $this->post('/track-order', ['order_number' => $order->order_number, 'phone' => '01999999999'])->assertSessionHasErrors('order_number');
        $this->post('/track-order', ['order_number' => strtolower($order->order_number), 'phone' => '+8801712345678'])->assertOk()->assertSee($order->order_number);
    }

    public function test_wishlist_requires_login_and_toggles(): void
    {
        $product = Product::factory()->create();
        $this->postJson("/wishlist/{$product->slug}")->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("/wishlist/{$product->slug}")->assertJson(['added' => true, 'count' => 1]);
        $this->actingAs($user)->postJson("/wishlist/{$product->slug}")->assertJson(['added' => false, 'count' => 0]);
    }

    public function test_only_verified_buyers_can_review_and_reviews_need_approval(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $review = ['rating' => 5, 'comment' => 'Wonderful quality and fit.'];

        $this->actingAs($user)->post(route('reviews.store', $product), $review)->assertSessionHas('error');
        $this->assertDatabaseCount('reviews', 0);

        $this->actingAs($user)->postJson('/cart', ['product_id' => $product->id]);
        $this->actingAs($user)->post('/checkout', $this->checkoutPayload());
        Order::first()->transitionTo('delivered');

        $this->actingAs($user)->post(route('reviews.store', $product), $review)->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', ['product_id' => $product->id, 'is_approved' => false]);

        $this->post('/logout');
        $this->get("/product/{$product->slug}")->assertDontSee('Wonderful quality and fit.');

        \App\Models\Review::first()->forceFill(['is_approved' => true])->save();
        $this->get("/product/{$product->slug}")->assertSee('Wonderful quality and fit.');
    }

    public function test_newsletter_and_contact(): void
    {
        $this->postJson('/newsletter', ['email' => 'Fan@Example.com'])->assertOk();
        $this->postJson('/newsletter', ['email' => 'fan@example.com'])->assertOk()->assertJson(['message' => 'You are already subscribed — thank you!']);
        $this->assertDatabaseCount('newsletter_subscribers', 1);

        $this->post('/contact', ['name' => 'A', 'email' => 'a@example.com', 'subject' => 'Hi', 'message' => 'Do you ship to Sylhet?'])->assertSessionHas('success');
        $this->post('/contact', ['name' => 'Bot', 'email' => 'b@example.com', 'subject' => 'Spam', 'message' => 'Buy cheap stuff now!', 'website' => 'spam.com'])->assertSessionHasErrors('website');
        $this->assertDatabaseCount('contact_messages', 1);
    }
}
