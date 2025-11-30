<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_purchase_flow(): void
    {
        // 1. Setup
        $product = Product::create([
            'name' => 'Flow Item',
            'price' => 50,
            'total_stock' => 10,
            'available_stock' => 10
        ]);

        // 2. Create Hold via API
        $holdResponse = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $holdResponse->assertCreated();
        $holdId = $holdResponse->json('data.hold_id');

        // Check stock reduced
        $this->assertEquals(9, $product->refresh()->available_stock);

        // 3. Create Order via API
        $orderResponse = $this->postJson('/api/orders', [
            'hold_id' => $holdId,
        ]);

        $orderResponse->assertCreated();
        $orderId = $orderResponse->json('data.order_id');

        // 4. Webhook Payment Success
        $webhookResponse = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => 'test_key_abc',
            'order_id' => $orderId,
            'status' => 'success',
        ]);

        $webhookResponse->assertOk();
        $this->assertEquals('paid', Order::find($orderId)->status->value);
    }

    public function test_expired_hold_restores_stock(): void
    {
        // 1. Setup
        $product = Product::create([
            'name' => 'Expire Item',
            'price' => 50,
            'total_stock' => 10,
            'available_stock' => 9 // 1 is held
        ]);

        // Create a hold that expired 1 minute ago
        Hold::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'expires_at' => now()->subMinute(),
        ]);

        // 2. Run the Release Command
        $this->artisan('holds:release')
             ->expectsOutputToContain('Released 1 expired holds');

        // 3. Verify Stock Restored
        $this->assertEquals(10, $product->refresh()->available_stock);
        $this->assertDatabaseCount('holds', 0);
    }

    public function test_cannot_create_order_from_expired_hold(): void
    {
        $product = Product::create(['name' => 'Item', 'price' => 10, 'total_stock' => 10, 'available_stock' => 10]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'expires_at' => now()->subMinute(), // Expired
        ]);

        $response = $this->postJson('/api/orders', [
            'hold_id' => $hold->id,
        ]);

        // Should be 400 Bad Request
        $response->assertStatus(400);
        $response->assertJsonFragment(['error' => 'The hold has expired.']);
    }
}
