<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UploadProductImageRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(ListProductRequest $request)
    {
        return ProductResource::collection($this->productService->listCatalog($request->validated()));
    }

    public function adminIndex()
    {
        return ProductResource::collection($this->productService->listAll());
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request->validated());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(string $id)
    {
        return new ProductResource($this->productService->findPublicById($id));
    }

    public function update(UpdateProductRequest $request, int $id)
    {
        $product = $this->productService->update($id, $request->validated());

        return new ProductResource($product);
    }

    public function uploadImage(UploadProductImageRequest $request, int $id)
    {
        return new ProductResource($this->productService->updateImage($id, $request->validated('image')));
    }

    public function destroy(string $id)
    {
        $this->productService->delete($id);

        return response()->noContent();
    }
}
