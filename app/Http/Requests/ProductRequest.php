<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:30', Rule::unique('products')->ignore($this->route('product'))],
            'nome' => ['required', 'string', 'max:120'],
            'descrizione' => ['nullable', 'string', 'max:255'],
            'prezzo_cents' => ['required', 'integer', 'min:0'],
            'iva' => ['required', Rule::in([0, 4, 10, 22])],
            'scorta_minima' => ['required', 'integer', 'min:0'],
            'giacenza' => [$this->isMethod('post') ? 'required' : 'prohibited', 'integer', 'min:0'],
            'attivo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['giacenza.prohibited' => 'La giacenza si modifica con un carico di magazzino.', 'sku.unique' => 'Questo codice SKU esiste già.'];
    }
}
