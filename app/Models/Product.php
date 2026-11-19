<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['sku', 'nome', 'descrizione', 'prezzo_cents', 'iva', 'giacenza', 'scorta_minima', 'attivo'];

    protected function casts(): array
    {
        return ['attivo' => 'boolean'];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeSottoScorta(Builder $query): Builder
    {
        return $query->whereColumn('giacenza', '<=', 'scorta_minima');
    }

    public function isSottoScorta(): bool
    {
        return $this->giacenza <= $this->scorta_minima;
    }
}
