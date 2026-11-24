<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Historic orders so the dashboard, account pages and reviews have realistic data.
 * These orders do not change stock (they represent sales before the seed snapshot).
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        Order::query()->delete();
        Review::query()->delete();
        mt_srand(77);

        $customers = User::where('role', User::ROLE_CUSTOMER)->get();
        $products = Product::with('variants')->get();
        $statuses = ['delivered', 'delivered', 'delivered', 'shipped', 'processing', 'confirmed', 'pending', 'cancelled'];

        for ($n = 0; $n < 36; $n++) {
            $customer = $customers->random();
            $status = $statuses[array_rand($statuses)];
            $placed = now()->subDays(mt_rand(0, 29))->setTime(mt_rand(9, 22), mt_rand(0, 59));
            $zone = $customer->city === 'Dhaka' ? 'inside_dhaka' : 'outside_dhaka';
            $method = mt_rand(0, 3) === 0 ? 'bkash' : 'cod';

            $lines = $products->random(mt_rand(1, 3));
            $subtotal = 0;
            $items = [];
            foreach ($lines as $p) {
                $v = $p->variants->random();
                $qty = mt_rand(1, 2);
                $price = $p->effective_price;
                $subtotal += $price * $qty;
                $items[] = [
                    'product_id' => $p->id, 'product_variant_id' => $v->id, 'product_name' => $p->name,
                    'sku' => $v->sku, 'size' => $v->size, 'color' => $v->color, 'price' => $price,
                    'quantity' => $qty, 'total' => $price * $qty,
                ];
            }
            $shipping = $subtotal >= 3000 ? 0 : ($zone === 'inside_dhaka' ? 70 : 130);

            $order = Order::create([
                'order_number' => Order::generateNumber(),
                'user_id' => $customer->id,
                'name' => $customer->name, 'email' => $customer->email, 'phone' => $customer->phone,
                'address' => $customer->address ?: 'Demo address', 'city' => $customer->city ?: 'Dhaka',
                'shipping_zone' => $zone,
                'subtotal' => $subtotal, 'discount' => 0, 'shipping_cost' => $shipping, 'total' => $subtotal + $shipping,
                'payment_method' => $method,
                'payment_status' => match (true) {
                    $status === 'delivered' => 'paid',
                    $method === 'bkash' && $status !== 'cancelled' => $status === 'pending' ? 'pending_verification' : 'paid',
                    default => 'unpaid',
                },
                'transaction_id' => $method === 'bkash' ? strtoupper(substr(md5((string) $n), 0, 10)) : null,
                'status' => $status,
            ]);
            $order->items()->createMany($items);
            $order->histories()->create(['status' => 'pending', 'note' => 'Order placed by customer.']);
            if ($status !== 'pending') {
                $order->histories()->create(['status' => $status]);
            }
            $order->forceFill(['created_at' => $placed, 'updated_at' => $placed])->saveQuietly();
            $order->histories()->update(['created_at' => $placed]);
        }

        // Approved reviews from customers on products they received.
        $comments = [
            [5, 'Beautiful quality', 'The fabric feels premium and the fit is exactly as described. Delivery inside Dhaka took two days.'],
            [5, 'Exceeded expectations', 'Elegant packaging and the piece looks even better in person. Will definitely order again.'],
            [4, 'Lovely, runs slightly large', 'Great finish and colour. I would size down if you are between sizes.'],
            [4, 'Great everyday piece', 'Comfortable and well made. The colour is true to the photos.'],
            [5, 'Perfect gift', 'Bought this as a gift and it was a hit. Cash on delivery made it easy.'],
        ];

        Order::where('status', 'delivered')->with('items')->get()->each(function (Order $order) use ($comments) {
            foreach ($order->items as $item) {
                if (! $item->product_id || mt_rand(0, 1) === 0) {
                    continue;
                }
                [$rating, $title, $comment] = $comments[array_rand($comments)];
                $review = Review::firstOrNew(['product_id' => $item->product_id, 'user_id' => $order->user_id]);
                $review->fill(compact('rating', 'title', 'comment'));
                $review->is_approved = true;
                $review->save();
            }
        });
    }
}
