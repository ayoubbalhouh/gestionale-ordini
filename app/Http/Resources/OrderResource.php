<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'stato' => $this->stato->value,
            'prossimi_stati' => array_map(fn ($s) => $s->value, $this->stato->next()),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'creato_da' => $this->whenLoaded('user', fn () => $this->user?->name),
            'data_consegna' => $this->data_consegna?->toDateString(),
            'note' => $this->note,
            'imponibile_cents' => (int) $this->imponibile_cents,
            'iva_cents' => (int) $this->iva_cents,
            'totale_cents' => $this->totale_cents,
            'items_count' => $this->whenCounted('items'),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'descrizione' => $i->descrizione,
                'quantita' => $i->quantita,
                'prezzo_cents' => $i->prezzo_cents,
                'iva' => $i->iva,
                'totale_cents' => $i->quantita * $i->prezzo_cents,
            ])),
            'storico' => array_values(array_filter([
                ['stato' => 'bozza', 'at' => $this->created_at?->toIso8601String()],
                $this->confermato_at ? ['stato' => 'confermato', 'at' => $this->confermato_at->toIso8601String()] : null,
                $this->spedito_at ? ['stato' => 'spedito', 'at' => $this->spedito_at->toIso8601String()] : null,
                $this->consegnato_at ? ['stato' => 'consegnato', 'at' => $this->consegnato_at->toIso8601String()] : null,
                $this->annullato_at ? ['stato' => 'annullato', 'at' => $this->annullato_at->toIso8601String()] : null,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
