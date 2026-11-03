<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AddressService;
use Illuminate\Http\Request;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;


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
        return addressResource::collection($this->addressService->listAll());

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
php artisan make:resource AddressResource
php artisan make:resource CartResource
php artisan make:resource CategoryResource
php artisan make:resource OrderResource
php artisan make:resource OrderItemResource
php artisan make:resource PaymentResource
php artisan make:resource ProductResource
php artisan make:resource UserResource

*/