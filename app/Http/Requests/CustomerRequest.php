<?php

namespace App\Http\Requests;

use App\Rules\PartitaIva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ragione_sociale' => ['required', 'string', 'max:150'],
            'piva' => ['nullable', 'string', new PartitaIva, Rule::unique('customers')->ignore($this->route('customer'))],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'indirizzo' => ['nullable', 'string', 'max:200'],
            'citta' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return ['piva.unique' => 'Esiste già un cliente con questa partita IVA.'];
    }
}
