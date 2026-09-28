<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'sku' => $this->faker->unique()->ean13(),
            'barcode' => $this->faker->ean13(),
            'description' => $this->faker->sentence(),
            'cost' => $this->faker->randomFloat(2, 1, 50),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'tax_rate' => $this->faker->randomElement([0, 18, 16]),
            'is_active' => true,
        ];
    }
}
