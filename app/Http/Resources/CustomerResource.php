<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ragione_sociale' => $this->ragione_sociale,
            'piva' => $this->piva,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'indirizzo' => $this->indirizzo,
            'citta' => $this->citta,
            'ordini_count' => $this->whenCounted('orders'),
            'fatturato_cents' => $this->when(isset($this->fatturato_cents), fn () => (int) $this->fatturato_cents),
        ];
    }
}
