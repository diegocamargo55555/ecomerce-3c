<?php

use App\Http\Controllers\Api\{AddressController, AuthController, CartController, CartItemController, CategoryController, CheckoutController, OrderController, ProductController, UserController};
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::apiResource('products', ProductController::class)->only(['index', 'show'])->whereNumber('product');
Route::apiResource('categories', CategoryController::class)->only(['index', 'show'])->whereNumber('category');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('cart', [CartController::class, 'current']);
    Route::apiResource('carts', CartController::class)->only(['index', 'store', 'show'])->whereNumber('cart');
    Route::apiResource('cart-items', CartItemController::class)->whereNumber('cart_item');
    Route::apiResource('addresses', AddressController::class)->whereNumber('address');
    Route::apiResource('orders', OrderController::class)->only(['index', 'show'])->whereNumber('order');
    Route::post('checkout', CheckoutController::class)->middleware('throttle:30,1');

    Route::prefix('admin')->name('admin.')->middleware(EnsureAdmin::class)->group(function () {
        Route::apiResource('products', ProductController::class)->whereNumber('product');
        Route::apiResource('categories', CategoryController::class)->whereNumber('category');
        Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'update'])->whereNumber('order');
    });

    // Legacy user management is restricted; orders and payments cannot be forged via CRUD.
    Route::apiResource('users', UserController::class)->middleware(EnsureAdmin::class)->whereNumber('user');
});
