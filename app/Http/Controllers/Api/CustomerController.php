<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $customers = Customer::query()
            ->withCount('orders')
            ->withSum(['orders as fatturato_cents' => fn ($o) => $o->whereIn('stato', ['confermato', 'spedito', 'consegnato'])], 'imponibile_cents')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('ragione_sociale', 'like', "%{$q}%")->orWhere('piva', 'like', "%{$q}%")->orWhere('citta', 'like', "%{$q}%")))
            ->orderBy('ragione_sociale');

        return CustomerResource::collection($request->boolean('all') ? $customers->get() : $customers->paginate(15));
    }

    public function store(CustomerRequest $request)
    {
        return (new CustomerResource(Customer::create($request->validated())))->response()->setStatusCode(201);
    }

    public function show(Customer $customer)
    {
        return new CustomerResource($customer->loadCount('orders'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer)
    {
        if ($customer->orders()->exists()) {
            return response()->json(['message' => 'Non puoi eliminare un cliente che ha degli ordini.'], 422);
        }
        $customer->delete();

        return response()->noContent();
    }
}
