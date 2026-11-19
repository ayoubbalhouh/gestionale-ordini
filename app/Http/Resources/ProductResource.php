<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'nome' => $this->nome,
            'descrizione' => $this->descrizione,
            'prezzo_cents' => $this->prezzo_cents,
            'iva' => $this->iva,
            'giacenza' => $this->giacenza,
            'scorta_minima' => $this->scorta_minima,
            'sotto_scorta' => $this->isSottoScorta(),
            'attivo' => $this->attivo,
        ];
    }
}
