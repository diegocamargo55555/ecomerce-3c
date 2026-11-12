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
        $address = $this->addressService->create([...$request->validated(), 'users_id' => $request->user()->id,]);

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

}
