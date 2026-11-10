<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'sort' => ['sometimes', 'string', 'in:newest,price-asc,price-desc,name'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        return ProductResource::collection($this->productService->listCatalog($filters));
    }

    public function adminIndex()
    {
        return ProductResource::collection($this->productService->listAll());
    }

    public function store(StoreProductRequest $request)
    {
        return new ProductResource($this->productService->create($request->validated())->refresh());
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

    public function uploadImage(Request $request, int $id)
    {
        // 2 MB: dentro do limite padrão de upload do PHP (upload_max_filesize).
        $data = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        return new ProductResource($this->productService->updateImage($id, $data['image']));
    }

    public function destroy(string $id)
    {
        $this->productService->delete($id);

        return response()->noContent();
    }
}
