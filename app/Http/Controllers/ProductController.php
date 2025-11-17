<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Listar produtos (paginado)
     */
    public function index(): JsonResponse
    {
        return response()->json(Product::paginate(20));
    }

    /**
     * Criar produto
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create([
            'name'   => $request->name,
            'amount' => $request->amount,
        ]);

        return response()->json($product, 201);
    }

    /**
     * Mostrar produto
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    /**
     * Atualizar produto
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update([
            'name'   => $request->name ?? $product->name,
            'amount' => $request->amount ?? $product->amount,
        ]);

        return response()->json($product);
    }

    /**
     * Deletar produto
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Produto removido']);
    }
}
