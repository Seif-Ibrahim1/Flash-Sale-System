<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class SetupDemo extends Command
{
    protected $signature = 'demo:setup';
    protected $description = 'Reset DB and print testing instructions';

    public function handle(): void
    {
        // 1. Reset Database
        $this->call('migrate:fresh', ['--seed' => true]);

        // 2. Fetch the seeded product
        $product = Product::first();

        $this->newLine();
        $this->info("✅ Database Reset & Seeded.");
        $this->info("📦 Demo Product ID: {$product->id}");
        $this->newLine();

        $this->comment("👉 Step 1: Check Stock");
        $this->line("curl http://127.0.0.1:8000/api/products/{$product->id}");

        $this->newLine();
        $this->comment("👉 Step 2: Create Hold (Copy the 'hold_id' from response)");
        $this->line("curl -X POST http://127.0.0.1:8000/api/holds -H \"Content-Type: application/json\" -d '{\"product_id\": \"{$product->id}\", \"qty\": 1}'");

        $this->newLine();
        $this->comment("👉 Step 3: Create Order (Replace HOLD_ID)");
        $this->line("curl -X POST http://127.0.0.1:8000/api/orders -H \"Content-Type: application/json\" -d '{\"hold_id\": \"REPLACE_WITH_HOLD_ID\"}'");

        $this->newLine();
    }
}
