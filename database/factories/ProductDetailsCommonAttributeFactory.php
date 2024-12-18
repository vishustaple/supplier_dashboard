<?php

namespace Database\Factories;

use App\Models\{
    ProductDetailsSubCategory,
    ProductDetailsCommonAttribute
};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductDetailsCommonAttribute>
 */
class ProductDetailsCommonAttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductDetailsCommonAttribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subCategory = ProductDetailsSubCategory::inRandomOrder()->first();
        return [
            'sub_category_id' => $subCategory ? $subCategory->id : ProductDetailsSubCategory::factory()->create()->id,
            'attribute_name' => $this->faker->word,
            'type' => $this->faker->randomElement(['string', 'number', 'boolean']),
        ];
    }
}
