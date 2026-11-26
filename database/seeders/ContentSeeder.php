<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use Database\Seeders\Support\DemoImageGenerator;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $gen = new DemoImageGenerator();

        Banner::query()->delete();
        $banners = [
            ['hero', 'The New Season Edit', 'Considered silhouettes in linen, silk and soft cotton — designed for Dhaka days and evenings.', 'Autumn / Winter 2026', '/shop?sort=newest', 'Shop new in', [17, 17, 17], [58, 48, 38]],
            ['hero', 'Festive, Refined', 'Handloom sarees and panjabis reimagined with a modern sensibility.', 'The Heritage Collection', '/category/panjabi', 'Explore heritage', [30, 26, 22], [92, 70, 48]],
            ['hero', 'Up to 25% Off', 'Selected pieces, for a limited time. Free delivery over BDT 3,000.', 'Season sale', '/shop?on_sale=1', 'Shop the sale', [20, 22, 26], [46, 58, 70]],
            ['promo', 'Accessories, Elevated', 'Leather bags, watches and eyewear.', 'Finishing touches', '/category/accessories', 'Discover', [40, 32, 26], [110, 86, 62]],
            ['promo', 'Sneaker Season', 'Comfort that keeps up with the city.', 'Footwear', '/category/footwear', 'Shop footwear', [26, 30, 32], [70, 80, 84]],
        ];

        foreach ($banners as $i => [$position, $title, $subtitle, $eyebrow, $link, $button, $from, $to]) {
            $image = $position === 'hero'
                ? $gen->banner($from, $to, "demo/banners/hero-{$i}.jpg")
                : $gen->banner($from, $to, "demo/banners/promo-{$i}.jpg", 1200, 900);

            Banner::create(compact('position', 'title', 'subtitle', 'eyebrow', 'link') + [
                'button_text' => $button, 'image' => $image, 'sort_order' => $i, 'is_active' => true,
            ]);
        }

        Coupon::updateOrCreate(['code' => 'WELCOME10'], [
            'type' => 'percent', 'value' => 10, 'min_order_amount' => 1000, 'max_discount' => 500, 'is_active' => true,
            'expires_at' => now()->addMonths(6),
        ]);
        Coupon::updateOrCreate(['code' => 'SYRO500'], [
            'type' => 'fixed', 'value' => 500, 'min_order_amount' => 5000, 'usage_limit' => 200, 'is_active' => true,
        ]);

        foreach (['hello@example.com', 'style@example.com', 'fashion.bd@example.com'] as $email) {
            NewsletterSubscriber::firstOrCreate(['email' => $email]);
        }
    }
}
