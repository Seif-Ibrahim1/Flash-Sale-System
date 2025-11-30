<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Payments\ProcessWebhookAction;
use App\Enums\OrderStatus;
use App\Models\Hold;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    // Use RefreshDatabase to reset DB for every test method
    use RefreshDatabase;

    public function test_it_processes_payment_success_idempotently(): void
    {
        // 1. Setup Data
        $product = Product::create([
            'name' => 'Test Item',
            'price' => 100,
            'total_stock' => 10,
            'available_stock' => 9
        ]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'expires_at' => now()->addMinutes(10)
        ]);

        $order = Order::create([
            'hold_id' => $hold->id,
            'product_id' => $product->id,
            'status' => OrderStatus::PENDING,
            'total_amount' => 100,
        ]);

        $action = new ProcessWebhookAction();
        $payload = ['order_id' => $order->id, 'status' => 'success'];
        $key = 'idemp_key_001';

        // 2. FIRST CALL: Should process normally
        $response1 = $action->handle($key, $payload);

        $this->assertEquals('order_paid', $response1['result']);
        $this->assertEquals(OrderStatus::PAID, $order->refresh()->status);
        $this->assertDatabaseHas('payment_events', ['idempotency_key' => $key]);

        // 3. SECOND CALL: Should be ignored (Idempotent)
        // We manually change the order status to something else to prove the logic DOES NOT run again.
        // If the logic ran again, it would try to update it.
        // But the action should return the SAVED response immediately.

        $response2 = $action->handle($key, $payload);

        // Response should be identical to the first one
        $this->assertEquals($response1, $response2);

        // Ensure we didn't create a second event log
        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_it_releases_stock_on_payment_failure(): void
    {
        // 1. Setup: Order with an Active Hold
        $product = Product::create([
            'name' => 'Test Item',
            'price' => 100,
            'total_stock' => 10,
            'available_stock' => 9
        ]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'expires_at' => now()->addMinutes(10)
        ]);

         $order = Order::create([
            'hold_id' => $hold->id,
            'product_id' => $product->id,
            'status' => OrderStatus::PENDING,
            'total_amount' => 100,
        ]);

        // 2. Act: Send FAILURE webhook
        $action = new ProcessWebhookAction();
        $action->handle('key_fail_1', ['order_id' => $order->id, 'status' => 'failed']);

        // 3. Assert
        $this->assertEquals(OrderStatus::FAILED, $order->refresh()->status);

        // The hold should be released (converted_to_order_at set to null or deleted,
        $this->assertNull($hold->refresh()->converted_to_order_at);
    }
}

