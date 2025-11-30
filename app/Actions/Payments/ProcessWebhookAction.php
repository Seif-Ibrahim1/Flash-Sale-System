<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcessWebhookAction
{
    /**
     * @return array The response body to send back to the provider
     */
    public function handle(string $key, array $payload): array
    {
        // 1. Idempotency Check (The "Gatekeeper")
        // If we processed this key before, return the saved response immediately.
        $existingEvent = PaymentEvent::where('idempotency_key', $key)->first();

        if ($existingEvent) {
            Log::info("Idempotency hit for key: {$key}");
            return $existingEvent->response_summary ?? ['status' => 'already_processed'];
        }

        // 2. Process the Event (Atomic Transaction)
        return DB::transaction(function () use ($key, $payload) {

            // Assume payload looks like: ['order_id' => '...', 'status' => 'success']
            $orderId = $payload['order_id'] ?? null;
            $status = $payload['status'] ?? 'failed';

            $responseBody = ['received' => true];

            if ($orderId) {
                // Lock the order to prevent double-updates
                $order = Order::lockForUpdate()->find($orderId);

                if ($order && $order->status === OrderStatus::PENDING) {
                    if ($status === 'success') {
                        // HAPPY PATH: Mark paid
                        $order->update(['status' => OrderStatus::PAID]);
                        $responseBody['result'] = 'order_paid';
                    } else {
                        // FAILURE PATH: Release stock immediately
                        $order->update(['status' => OrderStatus::FAILED]);

                        // Release the hold so stock goes back to the pool
                        if ($order->hold) {
                            $order->hold->update(['converted_to_order_at' => null]);
                        }
                        $responseBody['result'] = 'order_failed_stock_released';
                    }
                }
            }

            // 3. Save the Event (So we never process it again)
            PaymentEvent::create([
                'idempotency_key' => $key,
                'payload' => $payload,
                'status' => 'processed',
                'response_summary' => $responseBody,
            ]);

            return $responseBody;
        });
    }
}
