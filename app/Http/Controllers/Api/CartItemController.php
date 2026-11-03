<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartItemService;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function __construct(
        private CartItemService $cartItemService
    ) {}

    public function index()
    {
        return response()->json($this->cartItemService->listAll());
    }

    public function store(Request $request)
    {
        $cartItem = $this->cartItemService->create($request->all());

        return response()->json($cartItem, 201);
    }

    public function show(string $id)
    {
        return response()->json($this->cartItemService->findById($id));
    }

    public function update(Request $request, string $id)
    {
        $cartItem = $this->cartItemService->update($id, $request->all());

        return response()->json($cartItem);
    }

    public function destroy(string $id)
    {
        $this->cartItemService->delete($id);

        return response()->noContent();
    }
}
