<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;

class SupplierSeeder extends Seeder
{   
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();
        DB::table('category_suppliers')->insert([
        [
            'supplier_name' => 'Enterprise',
            'created_by' => '1',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'supplier_name' => 'Grainger',
            'created_by' => '1',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'supplier_name' => 'Office Depot',
            'created_by' => '1',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'supplier_name' => 'Staples',
            'created_by' => '1',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'supplier_name' => 'WB Mason',
            'created_by' => '1',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'supplier_name' => 'Unknown',
            'created_by' => '1',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    
    
    ]);
    }
}
