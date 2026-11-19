<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prodotto' => $this->whenLoaded('product', fn () => ['id' => $this->product->id, 'sku' => $this->product->sku, 'nome' => $this->product->nome]),
            'ordine' => $this->whenLoaded('order', fn () => $this->order ? ['id' => $this->order->id, 'numero' => $this->order->numero] : null),
            'utente' => $this->whenLoaded('user', fn () => $this->user?->name),
            'quantita' => $this->quantita,
            'tipo' => $this->tipo,
            'nota' => $this->nota,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
