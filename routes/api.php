<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::post('/login', [Api\AuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [Api\AuthController::class, 'me']);
    Route::post('/logout', [Api\AuthController::class, 'logout']);

    Route::get('/dashboard', Api\DashboardController::class);

    Route::apiResource('products', Api\ProductController::class);
    Route::post('/products/{product}/carico', [Api\ProductController::class, 'load']);

    Route::apiResource('customers', Api\CustomerController::class);

    Route::apiResource('orders', Api\OrderController::class);
    Route::post('/orders/{order}/stato', [Api\OrderController::class, 'status']);
    Route::get('/orders/{order}/pdf', [Api\OrderController::class, 'pdf']);

    Route::get('/movimenti', [Api\StockMovementController::class, 'index']);

    Route::apiResource('users', Api\UserController::class)->only(['index', 'store', 'destroy']);
});
