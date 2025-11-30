<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Check if exists to prevent duplicates on re-runs
        if (Product::where('name', 'Flash Sale Item')->exists()) {
            return;
        }

        Product::create([
            'name' => 'Flash Sale Item',
            'price' => 199.99,
            'total_stock' => 100,
            'available_stock' => 100,
        ]);

        $this->command->info('✅ Flash Sale Product Seeded!');
    }
}
