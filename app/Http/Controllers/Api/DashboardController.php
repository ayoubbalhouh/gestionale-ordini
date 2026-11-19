<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Carbon\CarbonImmutable;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $venduti = [OrderStatus::Confermato->value, OrderStatus::Spedito->value, OrderStatus::Consegnato->value];
        $inizioMese = CarbonImmutable::now()->startOfMonth();

        // Fatturato (imponibile) degli ultimi 6 mesi, per mese di conferma
        $mesi = collect(range(5, 0))->map(function ($i) use ($venduti) {
            $from = CarbonImmutable::now()->startOfMonth()->subMonths($i);
            $totale = Order::whereIn('stato', $venduti)
                ->whereBetween('confermato_at', [$from, $from->endOfMonth()])
                ->sum('imponibile_cents');

            return ['mese' => $from->translatedFormat('M'), 'anno' => $from->year, 'imponibile_cents' => (int) $totale];
        });

        return [
            'kpi' => [
                'fatturato_mese_cents' => (int) Order::whereIn('stato', $venduti)->where('confermato_at', '>=', $inizioMese)->sum('imponibile_cents'),
                'ordini_mese' => Order::whereIn('stato', $venduti)->where('confermato_at', '>=', $inizioMese)->count(),
                'da_spedire' => Order::where('stato', OrderStatus::Confermato)->count(),
                'bozze' => Order::where('stato', OrderStatus::Bozza)->count(),
                'sotto_scorta' => Product::where('attivo', true)->sottoScorta()->count(),
                'clienti' => Customer::count(),
            ],
            'mesi' => $mesi,
            'ultimi_ordini' => OrderResource::collection(Order::with('customer')->latest('id')->limit(6)->get()),
            'sotto_scorta' => ProductResource::collection(Product::where('attivo', true)->sottoScorta()->orderBy('giacenza')->limit(6)->get()),
        ];
    }
}
