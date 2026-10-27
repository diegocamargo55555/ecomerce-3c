<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource('users', UserController::class)
    ->whereNumber('user');

Route::apiResource('addresses', AddressController::class)
    ->whereNumber('address');

Route::apiResource('orders', OrderController::class)
    ->whereNumber('order');

Route::apiResource('payments', PaymentController::class)
    ->whereNumber('payment');
