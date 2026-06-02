<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTypeSeeder extends Seeder {
    public function run(): void {
        // 🔥 Tumhaari Excel sheet se scan kiye gaye ekdum unique 16 Product Types
        $products = [
            'Two Owner Search',
            'Full Search',
            'Current Owner Search',
            'Deed Search',
            'Commercial Update',
            'Document Retrieval Search',
            '42 Years Search',
            'Tax Search',
            'Additional Search Info',
            'Update Search',
            'Commercial',
            'Commercial_Current Owner Search',
            '40 Years Search',
            'Commercial_run',
            '30 Years Search',
            '60 Years Search'
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['name' => $product],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}