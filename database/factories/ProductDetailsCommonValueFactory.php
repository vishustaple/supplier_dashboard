<?php

namespace Database\Factories;

use App\Models\{
    CatalogItem,
    ProductDetailsCommonValue,
    ProductDetailsCommonAttribute
};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductDetailsCommonValue>
 */
class ProductDetailsCommonValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductDetailsCommonValue::class;

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
        
            'common_attribute_id' => ProductDetailsCommonAttribute::query()->exists() 
            ? ProductDetailsCommonAttribute::inRandomOrder()->first()->id 
            : ProductDetailsCommonAttribute::factory()->create()->id,  // Create a new attribute if none exists

            'value' => $this->faker->word,
        ];
    }
}
