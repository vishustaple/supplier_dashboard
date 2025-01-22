<?php

namespace Database\Factories;

use App\Models\{
    Supplier,
    Industry,
    CatalogItem,
    Manufacturer,
    ProductDetailsCategory,
    ProductDetailsSubCategory,
};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CatalogItem>
 */
class CatalogItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CatalogItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::inRandomOrder()->first()?->id ?? Supplier::factory()->create()->id,
            'industry_id' => Industry::inRandomOrder()->first()?->id ?? Industry::factory()->create()->id,
            'category_id' => ProductDetailsCategory::inRandomOrder()->first()?->id ?? ProductDetailsCategory::factory()->create()->id,
            'sub_category_id' => ProductDetailsSubCategory::inRandomOrder()->first()?->id ?? ProductDetailsSubCategory::factory()->create()->id,
            'manufacterer_id' => Manufacturer::inRandomOrder()->first()?->id ?? Manufacturer::factory()->create()->id,  // Optional manufacturer_id
            'sku' => $this->faker->unique()->numerify('SKU-#####'),
            'unspsc' => $this->faker->numerify('UNSPSC-####'),
            'manufacterer_number' => $this->faker->optional()->word,
            'catalog_item_name' => $this->faker->company,  // Use company name as a substitute for product name
            'supplier_shorthand_name' => $this->faker->companySuffix,
            'quantity_per_unit' => $this->faker->randomNumber(2),
            'unit_of_measure' => $this->faker->randomElement(['kg', 'pcs', 'liter', 'm']),
            'catalog_item_url' => $this->faker->url,
        ];
    }
}