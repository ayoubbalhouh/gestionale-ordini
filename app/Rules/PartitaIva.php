<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Partita IVA italiana: 11 cifre e cifra di controllo corretta.
 */
class PartitaIva implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\d{11}$/', (string) $value)) {
            $fail('La partita IVA deve avere 11 cifre.');

            return;
        }

        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $n = (int) $value[$i];
            if ($i % 2 === 1) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
        }

        if ($sum % 10 !== 0) {
            $fail('La partita IVA non è valida.');
        }
    }
}
