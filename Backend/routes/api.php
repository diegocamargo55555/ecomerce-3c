<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1');

Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1');

Route::apiResource('products', ProductController::class)
    ->only(['index', 'show'])->whereNumber('product');
Route::apiResource('categories', CategoryController::class)->only(['index']);
Route::get('cart', [CartController::class, 'current']);
Route::post('cart/items', [CartItemController::class, 'add']);
Route::patch('cart/items/{id}', [CartItemController::class, 'updateCurrent'])->whereNumber('id');
Route::delete('cart/items/{id}', [CartItemController::class, 'removeCurrent'])->whereNumber('id');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('checkout', [OrderController::class, 'review']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
    Route::post('orders/{id}/pay', [PaymentController::class, 'pay'])->whereNumber('id');
    Route::get('my/addresses', [AddressController::class, 'mine']);
    Route::post('my/addresses', [AddressController::class, 'storeOwn']);

    Route::apiResource('categories', CategoryController::class)
        ->only(['show'])->whereNumber('category');

    Route::middleware('can:admin')->group(function () {
        Route::get('admin/products', [ProductController::class, 'adminIndex']);

        Route::apiResource('users', UserController::class)
            ->whereNumber('user');

        Route::apiResource('addresses', AddressController::class)
            ->whereNumber('address');

        Route::apiResource('orders', OrderController::class)
            ->only(['index'])
            ->whereNumber('order');

        Route::apiResource('payments', PaymentController::class)
            ->only(['index', 'show'])
            ->whereNumber('payment');

        Route::apiResource('categories', CategoryController::class)
            ->except(['index', 'show'])
            ->whereNumber('category');

        Route::apiResource('products', ProductController::class)
            ->except(['index', 'show'])
            ->whereNumber('product');

        Route::apiResource('carts', CartController::class)
            ->whereNumber('cart');

        Route::apiResource('cart-items', CartItemController::class)
            ->whereNumber('cart_item');

        Route::apiResource('order-items', OrderItemController::class)
            ->only(['index', 'show'])
            ->whereNumber('order_item');
    });
});
