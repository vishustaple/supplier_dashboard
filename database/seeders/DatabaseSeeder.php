<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\{
    Industry,
    Supplier,
    CatalogItem,
    ProductDetailsCategory,
    ProductDetailsRawValue,
    ProductDetailsCommonValue,
    ProductDetailsSubCategory,
    ProductDetailsCommonAttribute,
};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // $this->call([
        //     SupplierSeeder::class,
        //     // Add more seeders as needed
        // ]);

        // Seed the industries table
        Industry::factory()->count(10)->create();  // Adjust count as needed

        // Seed the suppliers table
        // Supplier::factory()->count(10)->create();

        // Seed the product_details_category table
        ProductDetailsCategory::factory()->count(10)->create();

        // Seed the product_details_sub_category table
        ProductDetailsSubCategory::factory()->count(10)->create();

        // Seed the product_details_common_attributes table
        ProductDetailsCommonAttribute::factory()->count(10)->create();

        // Seed the product_details_common_values table
        ProductDetailsCommonValue::factory()->count(10)->create();

        // Seed the product_details_raw_values table
        ProductDetailsRawValue::factory()->count(10)->create();

        // Seed the catalog_items table
        CatalogItem::factory()->count(10)->create();
    }
}
