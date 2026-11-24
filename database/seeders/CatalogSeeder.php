<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\Support\DemoImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    protected const CATEGORIES = [
        'Women' => ['desc' => 'Effortless dresses, refined tops and modern ethnic wear.', 'shape' => 'dress', 'hex' => '#8C6F5A',
            'children' => ['Dresses', 'Tops', 'Sarees & Ethnic']],
        'Men' => ['desc' => 'Sharp shirts, everyday tees and festive panjabis.', 'shape' => 'shirt', 'hex' => '#2F3A45',
            'children' => ['Shirts', 'T-Shirts', 'Panjabi', 'Trousers']],
        'Footwear' => ['desc' => 'Comfort-first sneakers crafted for city days.', 'shape' => 'sneaker', 'hex' => '#3A3A3A',
            'children' => ['Sneakers']],
        'Accessories' => ['desc' => 'Bags, watches and eyewear to finish the look.', 'shape' => 'bag', 'hex' => '#6B4E3D',
            'children' => ['Bags', 'Watches', 'Sunglasses']],
    ];

    protected const BRANDS = ['SYRO Studio', 'Aurelle', 'Nordic Thread', 'Maison Dhaka', 'Urban Loom'];

    protected const APPAREL_SIZES = ['S', 'M', 'L', 'XL'];
    protected const SHOE_SIZES = ['39', '40', '41', '42', '43'];

    protected const C = [
        'Black' => '#1C1C1C', 'Ivory' => '#EFE8DA', 'Sand' => '#CDB894', 'Olive' => '#5E6448', 'Navy' => '#253246',
        'Terracotta' => '#B0603F', 'Sage' => '#9AA58C', 'Burgundy' => '#6E2A35', 'Charcoal' => '#3B3D40', 'Camel' => '#B08A5B',
        'White' => '#F6F4EF', 'Sky' => '#9DB4C8', 'Blush' => '#D8B2A6', 'Forest' => '#2E4A3B', 'Tan' => '#A87A4F', 'Gold' => '#B69A70',
    ];

    /** [name, category, brand, shape, price, sale, colours, sizes, flags] */
    protected function products(): array
    {
        $A = self::APPAREL_SIZES;
        $S = self::SHOE_SIZES;

        return [
            ['Linen Wrap Midi Dress', 'Dresses', 'Aurelle', 'dress', 4290, 3490, ['Sand', 'Sage', 'Black'], $A, ['featured', 'new', 'trending']],
            ['Pleated Satin Slip Dress', 'Dresses', 'Aurelle', 'dress', 5450, null, ['Burgundy', 'Black'], $A, ['featured']],
            ['Cotton Tiered Maxi Dress', 'Dresses', 'SYRO Studio', 'dress', 3890, null, ['Ivory', 'Terracotta'], $A, ['new']],
            ['Belted Shirt Dress', 'Dresses', 'Nordic Thread', 'dress', 3650, 2990, ['Olive', 'Navy'], $A, []],
            ['Ribbed Knit Top', 'Tops', 'Nordic Thread', 'top', 1690, null, ['Ivory', 'Black', 'Blush'], $A, ['new']],
            ['Silk-Touch Blouse', 'Tops', 'Aurelle', 'top', 2490, 1990, ['Ivory', 'Sky'], $A, ['trending']],
            ['Relaxed Linen Top', 'Tops', 'SYRO Studio', 'top', 1890, null, ['Sand', 'White'], $A, []],
            ['Handloom Jamdani Saree', 'Sarees & Ethnic', 'Maison Dhaka', 'saree', 12500, null, ['Ivory', 'Gold'], [], ['featured', 'trending']],
            ['Soft Silk Saree', 'Sarees & Ethnic', 'Maison Dhaka', 'saree', 8900, 7490, ['Burgundy', 'Forest'], [], ['featured']],
            ['Embroidered Kurti', 'Sarees & Ethnic', 'Maison Dhaka', 'panjabi', 2790, null, ['Blush', 'Sage', 'Ivory'], $A, ['new']],

            ['Oxford Button-Down Shirt', 'Shirts', 'Nordic Thread', 'shirt', 2650, null, ['White', 'Sky', 'Navy'], $A, ['featured', 'new']],
            ['Linen Camp-Collar Shirt', 'Shirts', 'SYRO Studio', 'shirt', 2890, 2390, ['Sand', 'Olive'], $A, ['trending']],
            ['Tailored Poplin Shirt', 'Shirts', 'Aurelle', 'shirt', 3190, null, ['White', 'Black'], $A, []],
            ['Heavyweight Cotton Tee', 'T-Shirts', 'Urban Loom', 'tee', 1190, null, ['Black', 'White', 'Olive', 'Navy'], $A, ['featured', 'new']],
            ['Essential Crew Tee', 'T-Shirts', 'SYRO Studio', 'tee', 890, 690, ['White', 'Charcoal', 'Sand'], $A, []],
            ['Pima Pocket Tee', 'T-Shirts', 'Nordic Thread', 'tee', 1350, null, ['Navy', 'Forest'], $A, ['trending']],
            ['Festive Cotton Panjabi', 'Panjabi', 'Maison Dhaka', 'panjabi', 3990, null, ['Ivory', 'Navy', 'Burgundy'], $A, ['featured', 'trending']],
            ['Silk Blend Panjabi', 'Panjabi', 'Maison Dhaka', 'panjabi', 5490, 4590, ['Gold', 'Charcoal'], $A, ['new']],
            ['Minimal Linen Panjabi', 'Panjabi', 'SYRO Studio', 'panjabi', 3290, null, ['White', 'Sand'], $A, []],
            ['Pleated Wool-Feel Trousers', 'Trousers', 'Aurelle', 'pants', 3450, null, ['Charcoal', 'Camel', 'Black'], ['30', '32', '34', '36'], ['featured']],
            ['Relaxed Chino', 'Trousers', 'Urban Loom', 'pants', 2490, 1990, ['Sand', 'Olive', 'Navy'], ['30', '32', '34', '36'], ['new']],

            ['Court Leather Sneaker', 'Sneakers', 'Urban Loom', 'sneaker', 5990, null, ['White', 'Black'], $S, ['featured', 'trending']],
            ['Suede Runner', 'Sneakers', 'Nordic Thread', 'sneaker', 6490, 5290, ['Sand', 'Navy'], $S, ['new']],
            ['Canvas Low-Top', 'Sneakers', 'SYRO Studio', 'sneaker', 2990, null, ['Ivory', 'Olive', 'Black'], $S, []],

            ['Structured Leather Tote', 'Bags', 'Aurelle', 'bag', 6890, null, ['Camel', 'Black'], [], ['featured', 'trending']],
            ['Mini Crossbody Bag', 'Bags', 'SYRO Studio', 'bag', 3490, 2790, ['Burgundy', 'Tan', 'Black'], [], ['new']],
            ['Everyday Canvas Tote', 'Bags', 'Urban Loom', 'bag', 1590, null, ['Ivory', 'Olive'], [], []],
            ['Classic Leather Watch', 'Watches', 'Nordic Thread', 'watch', 8990, null, ['Black', 'Navy'], [], ['featured']],
            ['Minimal Mesh Watch', 'Watches', 'SYRO Studio', 'watch', 6490, 5490, ['Ivory', 'Charcoal'], [], ['trending']],
            ['Round Acetate Sunglasses', 'Sunglasses', 'Aurelle', 'sunglasses', 2990, null, ['Black', 'Tan'], [], ['new']],
            ['Aviator Sunglasses', 'Sunglasses', 'Urban Loom', 'sunglasses', 2490, 1990, ['Charcoal', 'Gold'], [], []],
            ['Wool Baseball Cap', 'Accessories', 'Urban Loom', 'cap', 1290, null, ['Black', 'Navy', 'Sand'], [], []],
        ];
    }

    public function run(): void
    {
        $gen = new DemoImageGenerator();
        Storage::disk('public')->deleteDirectory('demo');

        // Brands
        $brands = collect(self::BRANDS)->mapWithKeys(fn ($name) => [
            $name => Brand::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => true]),
        ]);

        // Categories (roots get a generated tile image and are featured on the homepage)
        $categories = [];
        $sort = 0;
        foreach (self::CATEGORIES as $name => $meta) {
            $image = $gen->product($meta['shape'], $meta['hex'], 'demo/categories/'.Str::slug($name).'.jpg');
            $root = Category::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'description' => $meta['desc'], 'image' => $image,
                'is_featured' => true, 'is_active' => true, 'sort_order' => $sort++,
            ]);
            $categories[$name] = $root;
            foreach ($meta['children'] as $i => $child) {
                $categories[$child] = Category::updateOrCreate(['slug' => Str::slug($child)], [
                    'name' => $child, 'parent_id' => $root->id, 'is_active' => true, 'sort_order' => $i,
                ]);
            }
        }

        mt_srand(2026); // deterministic stock levels

        foreach ($this->products() as $i => [$name, $cat, $brand, $shape, $price, $sale, $colors, $sizes, $flags]) {
            $slug = Str::slug($name);
            $product = Product::withTrashed()->updateOrCreate(['slug' => $slug], [
                'category_id' => $categories[$cat]->id,
                'brand_id' => $brands[$brand]->id,
                'name' => $name,
                'sku' => 'SYR-'.str_pad((string) ($i + 1001), 5, '0', STR_PAD_LEFT),
                'short_description' => $this->shortDescription($cat, $name),
                'description' => $this->description($cat, $name),
                'price' => $price,
                'sale_price' => $sale,
                'stock' => 0,
                'is_featured' => in_array('featured', $flags),
                'is_new_arrival' => in_array('new', $flags),
                'is_trending' => in_array('trending', $flags),
                'is_active' => true,
            ]);
            $product->restore();
            $product->forceFill([
                'sold_count' => mt_rand(3, 140),
                'views' => mt_rand(80, 2400),
                'created_at' => now()->subDays(in_array('new', $flags) ? mt_rand(0, 10) : mt_rand(15, 120)),
            ])->saveQuietly();

            // Images: two per product in the first two colours.
            $product->images()->delete();
            foreach (array_slice($colors, 0, 2) as $n => $colorName) {
                $path = $gen->product($shape, self::C[$colorName], "demo/products/{$slug}-{$n}.jpg", $n);
                $product->images()->create(['path' => $path, 'alt' => "{$name} in {$colorName}", 'sort_order' => $n]);
            }
            if (count($colors) === 1) {
                $path = $gen->product($shape, self::C[$colors[0]], "demo/products/{$slug}-1.jpg", 1);
                $product->images()->create(['path' => $path, 'alt' => $name, 'sort_order' => 1]);
            }

            // Variants: every colour × size (or colour only for one-size items).
            $product->variants()->delete();
            foreach ($colors as $colorName) {
                foreach ($sizes ?: [null] as $size) {
                    $product->variants()->create([
                        'size' => $size,
                        'color' => $colorName,
                        'color_hex' => self::C[$colorName],
                        'sku' => $product->sku.'-'.strtoupper(substr($colorName, 0, 3)).($size ? '-'.$size : ''),
                        'stock' => mt_rand(0, 10) === 0 ? 0 : mt_rand(2, 18),
                    ]);
                }
            }
            $product->syncStockFromVariants();
        }
    }

    protected function shortDescription(string $cat, string $name): string
    {
        return match (true) {
            in_array($cat, ['Dresses', 'Tops']) => 'A softly structured piece cut from breathable fabric — easy to dress up or down.',
            in_array($cat, ['Sarees & Ethnic', 'Panjabi']) => 'Contemporary craftsmanship rooted in Bangladeshi textile heritage.',
            in_array($cat, ['Shirts', 'T-Shirts', 'Trousers']) => 'An elevated everyday essential with a clean, modern fit.',
            $cat === 'Sneakers' => 'Cushioned, lightweight and made to keep up with the city.',
            default => 'A refined finishing touch designed to be used every day.',
        };
    }

    protected function description(string $cat, string $name): string
    {
        $care = in_array($cat, ['Bags', 'Watches', 'Sunglasses', 'Accessories', 'Sneakers'])
            ? 'Wipe clean with a soft, dry cloth. Store in the dust bag provided.'
            : 'Machine wash cold on a gentle cycle. Dry in shade. Warm iron on reverse.';

        return "The {$name} is designed in Dhaka by the SYRO team with a focus on proportion, comfort and longevity.\n\n"
            ."• Premium, carefully sourced materials\n• Considered details and clean finishing\n• Quality-checked by hand before dispatch\n\n"
            ."Care: {$care}";
    }
}
