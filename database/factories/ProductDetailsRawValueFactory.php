<?php

namespace Database\Factories;

use App\Models\{
    CatalogItem,
    ProductDetailsRawValue
};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductDetailsRawValue>
 */
class ProductDetailsRawValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductDetailsRawValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'catalog_item_id' => CatalogItem::query()->exists() 
            ? CatalogItem::inRandomOrder()->first()->id 
            : CatalogItem::factory()->create()->id,  // Create a new catalog item if none exists
        
            'raw_values' => json_encode($this->faker->words($this->faker->numberBetween(3, 7))),  // Random JSON array with 3 to 7 words
        ];
    }
}
