<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentWebhookController;

// 1. Get Product Details (High-speed read)
Route::get('/products/{id}', [ProductController::class, 'show']);

// 2. Create Hold (The Concurrency Endpoint)
Route::post('/holds', [ProductController::class, 'reserve']);

Route::post('/payments/webhook', [PaymentWebhookController::class, 'handle']);
