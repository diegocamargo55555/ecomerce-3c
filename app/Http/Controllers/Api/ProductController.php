<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index()
    {
        return response()->json($this->productService->listAll());
    }

    public function store(Request $request)
    {
        $product = $this->productService->create($request->all());

        return response()->json($product, 201);
    }

    public function show(string $id)
    {
        return response()->json($this->productService->findById($id));
    }

    public function update(Request $request, string $id)
    {
        $product = $this->productService->update($id, $request->all());

        return response()->json($product);
    }

    public function destroy(string $id)
    {
        $this->productService->delete($id);

        return response()->noContent();
    }
}
