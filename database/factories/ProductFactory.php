<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = ucwords(fake()->unique()->words(3, true));

        return [
            'category_id' => fn () => Category::create(['name' => 'Cat '.Str::random(5), 'slug' => Str::random(10)])->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'sku' => 'T-'.Str::upper(Str::random(8)),
            'short_description' => fake()->sentence(),
            'price' => 1000,
            'sale_price' => null,
            'stock' => 10,
            'is_active' => true,
        ];
    }
}
