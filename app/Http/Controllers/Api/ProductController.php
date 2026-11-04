<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index()
    {
        return ProductResource::collection($this->productService->listAll());
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request->all());

        return response()->json($product, 201);
    }

    public function show(string $id)
    {
        return new ProductResource($this->productService->findById($id));
    }

    public function update(UpdateProductRequest $request, int $id)
    {
        $product = $this->productService->update($id, $request->validated());

        return new ProductResource($product);
    }

    public function destroy(string $id)
    {
        $this->productService->delete($id);

        return response()->noContent();
    }
}
