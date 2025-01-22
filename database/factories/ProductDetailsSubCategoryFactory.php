<?php

namespace Database\Factories;

use App\Models\{
    ProductDetailsCategory,
    ProductDetailsSubCategory
};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductDetailsSubCategory>
 */
class ProductDetailsSubCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductDetailsSubCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => ProductDetailsCategory::query()->exists() 
            ? ProductDetailsCategory::inRandomOrder()->first()->id 
            : ProductDetailsCategory::factory()->create()->id,
        
            'sub_category_name' => $this->faker->words(2, true),
        ];
    }
}
