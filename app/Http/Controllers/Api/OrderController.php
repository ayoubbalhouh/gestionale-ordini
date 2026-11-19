<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderService $service) {}

    public function index(Request $request)
    {
        $request->validate(['stato' => ['nullable', Rule::enum(OrderStatus::class)]]);
        $q = trim((string) $request->query('q'));

        $orders = Order::with('customer')->withCount('items')
            ->when($request->query('stato'), fn ($query, $stato) => $query->where('stato', $stato))
            ->when($request->query('customer_id'), fn ($query, $id) => $query->where('customer_id', $id))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->whereHas('customer', fn ($c) => $c->where('ragione_sociale', 'like', "%{$q}%"));
                    if (preg_match('/(\d+)$/', $q, $m)) {
                        $w->orWhere('progressivo', (int) $m[1]);
                    }
                });
            })
            ->latest('id')
            ->paginate(15);

        return OrderResource::collection($orders)->additional([
            'conteggi' => Order::selectRaw('stato, count(*) as n')->groupBy('stato')->pluck('n', 'stato'),
        ]);
    }

    public function store(OrderRequest $request)
    {
        $order = $this->service->save($request->validated(), $request->user());

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function show(Order $order)
    {
        return new OrderResource($order->load('items', 'customer', 'user'));
    }

    public function update(OrderRequest $request, Order $order)
    {
        return new OrderResource($this->service->save($request->validated(), $request->user(), $order));
    }

    public function destroy(Order $order)
    {
        if ($order->stato !== OrderStatus::Bozza) {
            return response()->json(['message' => 'Si possono eliminare solo le bozze. Gli altri ordini si annullano.'], 422);
        }
        $order->delete();

        return response()->noContent();
    }

    public function status(Request $request, Order $order)
    {
        $data = $request->validate(['stato' => ['required', Rule::enum(OrderStatus::class)]]);
        $order = $this->service->changeStatus($order, OrderStatus::from($data['stato']), $request->user());

        return new OrderResource($order->load('items', 'customer', 'user'));
    }

    public function pdf(Order $order)
    {
        $order->load('items', 'customer');

        return Pdf::loadView('pdf.order', ['order' => $order])
            ->setPaper('a4')
            ->stream("{$order->numero}.pdf");
    }
}
