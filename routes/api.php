<?php

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

Route::apiResource('users', UserController::class)
    ->whereNumber('user');

Route::apiResource('addresses', AddressController::class)
    ->whereNumber('address');

Route::apiResource('orders', OrderController::class)
    ->whereNumber('order');

Route::apiResource('payments', PaymentController::class)
    ->whereNumber('payment');

Route::apiResource('categories', CategoryController::class)
    ->whereNumber('category');

Route::apiResource('products', ProductController::class)
    ->whereNumber('product');

Route::apiResource('carts', CartController::class)
    ->whereNumber('cart');

Route::apiResource('cart-items', CartItemController::class)
    ->whereNumber('cart_item');

Route::apiResource('order-items', OrderItemController::class)
    ->whereNumber('order_item');
