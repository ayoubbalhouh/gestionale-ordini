<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $products = Product::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('nome', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
            ->when($request->boolean('sotto_scorta'), fn ($query) => $query->sottoScorta())
            ->when($request->boolean('solo_attivi'), fn ($query) => $query->where('attivo', true))
            ->orderBy('nome');

        return ProductResource::collection($request->boolean('all') ? $products->get() : $products->paginate(15));
    }

    public function store(ProductRequest $request, OrderService $service)
    {
        $data = $request->validated();
        $giacenza = $data['giacenza'];
        $product = Product::create([...$data, 'giacenza' => 0]);
        if ($giacenza > 0) {
            $service->loadStock($product, $giacenza, $request->user(), 'Giacenza iniziale');
        }

        return (new ProductResource($product->refresh()))->response()->setStatusCode(201);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        Gate::authorize('admin');

        // Con ordini collegati non si cancella: si disattiva, così lo storico resta corretto
        if ($product->movements()->whereNotNull('order_id')->exists() || \App\Models\OrderItem::where('product_id', $product->id)->exists()) {
            $product->update(['attivo' => false]);

            return response()->json(['message' => 'Il prodotto è usato in alcuni ordini: è stato disattivato.']);
        }

        $product->delete();

        return response()->noContent();
    }

    public function load(Request $request, Product $product, OrderService $service)
    {
        Gate::authorize('admin');

        $data = $request->validate([
            'quantita' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000', function ($attr, $value, $fail) use ($product) {
                if ($product->giacenza + $value < 0) {
                    $fail("La giacenza non può andare sotto zero (ora: {$product->giacenza}).");
                }
            }],
            'nota' => ['nullable', 'string', 'max:200'],
        ]);

        return new ProductResource($service->loadStock($product, $data['quantita'], $request->user(), $data['nota'] ?? null));
    }
}
