<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'user_id', 'stato', 'data_consegna', 'note', 'anno', 'progressivo'];

    protected function casts(): array
    {
        return [
            'stato' => OrderStatus::class,
            'data_consegna' => 'date',
            'confermato_at' => 'datetime',
            'spedito_at' => 'datetime',
            'consegnato_at' => 'datetime',
            'annullato_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getNumeroAttribute(): string
    {
        return sprintf('ORD-%d-%04d', $this->anno, $this->progressivo);
    }

    public function getTotaleCentsAttribute(): int
    {
        return $this->imponibile_cents + $this->iva_cents;
    }

    public function recalculateTotals(): void
    {
        $items = $this->items()->get();
        $this->imponibile_cents = $items->sum(fn ($i) => $i->quantita * $i->prezzo_cents);
        $this->iva_cents = (int) round($items->sum(fn ($i) => $i->quantita * $i->prezzo_cents * $i->iva / 100));
    }

    public static function nextProgressivo(int $anno): int
    {
        return (int) static::where('anno', $anno)->max('progressivo') + 1;
    }
}
