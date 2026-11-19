<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'data_consegna' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantita' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Scegli un cliente.',
            'items.required' => 'Aggiungi almeno un prodotto.',
            'items.*.product_id.distinct' => 'Lo stesso prodotto è presente due volte.',
            'items.*.quantita.min' => 'La quantità deve essere almeno 1.',
            'data_consegna.after_or_equal' => 'La data di consegna non può essere nel passato.',
        ];
    }
}
