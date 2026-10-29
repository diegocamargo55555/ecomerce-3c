<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
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
        $genre = $this->genreService->update($id, $request->all());
        return response()->json($address);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->genreService->delete($id);
        return response()->noContent();
    }
}
