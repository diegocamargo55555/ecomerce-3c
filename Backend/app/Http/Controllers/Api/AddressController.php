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

    public function mine(Request $request)
    {
        return AddressResource::collection($this->addressService->listForUser($request->user()->id));
    }

    public function storeOwn(StoreAddressRequest $request)
    {
        $address = $this->addressService->create([
            ...$request->validated(), 'users_id' => $request->user()->id,
        ]);

        return new AddressResource($address);
    }

    public function updateOwn(UpdateAddressRequest $request, int $id)
    {
        $address = $this->addressService->update($id, $request->validated(), $request->user()->id);

        return (new AddressResource($address))->response()->setStatusCode(200);
    }

    public function destroyOwn(Request $request, int $id)
    {
        $this->addressService->delete($id, $request->user()->id);

        return response()->noContent();
    }

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
    public function store(StoreAddressRequest $request)
    {
        $address = $this->addressService->create($request->validated());
        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new addressResource($this->addressService->findById($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressRequest $request, int $id)
    {
        $address = $this->addressService->update($id, $request->validated());
        return (new AddressResource($address))->response()->setStatusCode(200);
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
