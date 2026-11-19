<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Logica degli ordini: creazione, modifica e cambi di stato con gestione del magazzino.
 * Ogni operazione che tocca lo stock avviene in una transazione.
 */
class OrderService
{
    /**
     * @param  array{customer_id:int, data_consegna?:string|null, note?:string|null, items: array<int, array{product_id:int, quantita:int}>}  $data
     */
    public function save(array $data, User $user, ?Order $order = null): Order
    {
        if ($order && $order->stato !== OrderStatus::Bozza) {
            throw ValidationException::withMessages(['stato' => 'Si possono modificare solo gli ordini in bozza.']);
        }

        return DB::transaction(function () use ($data, $user, $order) {
            if (! $order) {
                $anno = (int) now()->format('Y');
                $order = new Order([
                    'anno' => $anno,
                    'progressivo' => Order::nextProgressivo($anno),
                    'user_id' => $user->id,
                    'stato' => OrderStatus::Bozza,
                ]);
            }

            $order->fill([
                'customer_id' => $data['customer_id'],
                'data_consegna' => $data['data_consegna'] ?? null,
                'note' => $data['note'] ?? null,
            ])->save();

            // Le righe salvano prezzo e IVA del momento: se il listino cambia, l'ordine resta uguale
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');
            $order->items()->delete();
            foreach ($data['items'] as $row) {
                $p = $products[$row['product_id']];
                $order->items()->create([
                    'product_id' => $p->id,
                    'descrizione' => $p->nome,
                    'quantita' => $row['quantita'],
                    'prezzo_cents' => $p->prezzo_cents,
                    'iva' => $p->iva,
                ]);
            }

            $order->recalculateTotals();
            $order->save();

            return $order->load('items', 'customer');
        });
    }

    public function changeStatus(Order $order, OrderStatus $to, User $user): Order
    {
        if (! $order->stato->canMoveTo($to)) {
            throw ValidationException::withMessages([
                'stato' => "Un ordine {$order->stato->value} non può passare a {$to->value}.",
            ]);
        }

        return DB::transaction(function () use ($order, $to, $user) {
            $order->load('items');

            if ($to === OrderStatus::Confermato) {
                $this->takeStock($order, $user);
            }
            if ($to === OrderStatus::Annullato && $order->stato->holdsStock()) {
                $this->returnStock($order, $user);
            }

            $order->stato = $to;
            $order->{$to->value.'_at'} = now();
            $order->save();

            return $order;
        });
    }

    private function takeStock(Order $order, User $user): void
    {
        // Blocca le righe dei prodotti fino alla fine della transazione
        $products = Product::whereIn('id', $order->items->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');

        $missing = [];
        foreach ($order->items->groupBy('product_id') as $productId => $rows) {
            $needed = $rows->sum('quantita');
            $p = $products[$productId];
            if ($p->giacenza < $needed) {
                $missing[] = "{$p->nome} (disponibili {$p->giacenza}, richiesti {$needed})";
            }
        }
        if ($missing) {
            throw ValidationException::withMessages(['stock' => 'Giacenza insufficiente: '.implode('; ', $missing).'.']);
        }

        foreach ($order->items as $item) {
            $products[$item->product_id]->decrement('giacenza', $item->quantita);
            StockMovement::create([
                'product_id' => $item->product_id, 'order_id' => $order->id, 'user_id' => $user->id,
                'quantita' => -$item->quantita, 'tipo' => 'ordine', 'nota' => $order->numero,
            ]);
        }
    }

    private function returnStock(Order $order, User $user): void
    {
        foreach ($order->items as $item) {
            Product::whereKey($item->product_id)->increment('giacenza', $item->quantita);
            StockMovement::create([
                'product_id' => $item->product_id, 'order_id' => $order->id, 'user_id' => $user->id,
                'quantita' => $item->quantita, 'tipo' => 'annullo', 'nota' => "Annullato {$order->numero}",
            ]);
        }
    }

    public function loadStock(Product $product, int $quantita, User $user, ?string $nota = null): Product
    {
        return DB::transaction(function () use ($product, $quantita, $user, $nota) {
            $product->increment('giacenza', $quantita);
            StockMovement::create([
                'product_id' => $product->id, 'user_id' => $user->id,
                'quantita' => $quantita, 'tipo' => $quantita >= 0 ? 'carico' : 'rettifica', 'nota' => $nota,
            ]);

            return $product->refresh();
        });
    }
}
