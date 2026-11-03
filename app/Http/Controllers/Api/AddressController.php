<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        private AddressService $addressService
    ) {}

        /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json($this->addressService->listAll());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $address = $this->addressService->create($request->all());
        return response()->json($address, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json($this->addressService->findById($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $address = $this->addressService->update($id, $request->all());
        return response()->json($address);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->addressService->delete($id);
        return response()->noContent();
    }
}




/*
php artisan make:request StoreAddressRequest
php artisan make:request UpdateAddressRequest

php artisan make:request StoreCartRequest
php artisan make:request UpdateCartRequest

php artisan make:request StoreCartItemRequest
php artisan make:request UpdateCartItemRequest

php artisan make:request StoreCategoryRequest
php artisan make:request UpdateCategoryRequest

php artisan make:request StoreOrderRequest
php artisan make:request UpdateOrderRequest

php artisan make:request StoreOrderItemRequest
php artisan make:request UpdateOrderItemRequest

php artisan make:request StorePaymentRequest
php artisan make:request UpdatePaymentRequest


php artisan make:request StoreProductRequest
php artisan make:request UpdateProductRequest


php artisan make:request StoreUserRequest
php artisan make:request UpdateUserRequest

*/