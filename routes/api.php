<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// 1. Get Product Details (High-speed read)
Route::get('/products/{id}', [ProductController::class, 'show']);

// 2. Create Hold (The Concurrency Endpoint)
Route::post('/holds', [ProductController::class, 'reserve']);
