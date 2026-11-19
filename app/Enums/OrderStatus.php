<?php

namespace App\Enums;

/**
 * Stati di un ordine e passaggi consentiti.
 */
enum OrderStatus: string
{
    case Bozza = 'bozza';
    case Confermato = 'confermato';
    case Spedito = 'spedito';
    case Consegnato = 'consegnato';
    case Annullato = 'annullato';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** @return OrderStatus[] */
    public function next(): array
    {
        return match ($this) {
            self::Bozza => [self::Confermato, self::Annullato],
            self::Confermato => [self::Spedito, self::Annullato],
            self::Spedito => [self::Consegnato],
            self::Consegnato, self::Annullato => [],
        };
    }

    public function canMoveTo(self $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    /** Lo stock è già stato scaricato in questi stati */
    public function holdsStock(): bool
    {
        return in_array($this, [self::Confermato, self::Spedito, self::Consegnato], true);
    }
}
