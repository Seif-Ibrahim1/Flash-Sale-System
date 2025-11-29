<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Products
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2);

            // for concurrency:
            // 'total_stock' is physical inventory.
            // 'available_stock' is what can be held.
            $table->unsignedInteger('total_stock');
            $table->unsignedInteger('available_stock');

            $table->timestamps();

            // Index for fast lookup
            $table->index(['id', 'available_stock']);
        });

        // 2. Holds (Short-lived reservations)
        Schema::create('holds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('quantity');
            $table->timestamp('expires_at')->index(); // Indexed for expiry job
            $table->timestamp('converted_to_order_at')->nullable();

            $table->timestamps();
        });

        // 3. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Ensure one hold -> one order constraint
            $table->foreignUlid('hold_id')->unique()->constrained('holds');
            $table->foreignUlid('product_id')->constrained();

            $table->string('status')->default('pending'); // pending, paid, cancelled
            $table->decimal('total_amount', 10, 2);

            $table->timestamps();
        });

        // 4. Payment Events (Idempotency Store)
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique();
            $table->string('provider_transaction_id')->nullable()->index();
            $table->json('payload');
            $table->string('status'); // processing, success, failed
            $table->json('response_summary')->nullable(); // To return same response for duplicates
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('holds');
        Schema::dropIfExists('products');
    }
};
