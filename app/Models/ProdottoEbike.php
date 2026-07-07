<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdottoEbike extends Model
{
    use SoftDeletes;

    protected $table = 'prodotti_ebike';

    public const NOME_SINGOLARE = 'prodotto ebike';

    public const NOME_PLURALE = 'prodotti ebike';

    protected $fillable = [
        'nome',
        'sku',
        'descrizione',
        'prezzo',
        'giacenza',
        'immagine',
        'attivo',
    ];

    protected $casts = [
        'prezzo' => 'decimal:2',
        'giacenza' => 'integer',
        'attivo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function righe(): HasMany
    {
        return $this->hasMany(RigaOrdineEbike::class, 'prodotto_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeAttivi(Builder $query): Builder
    {
        return $query->where('attivo', true);
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */

    public function urlImmagine(): ?string
    {
        return $this->immagine ? '/storage/'.$this->immagine : null;
    }

    public function disponibile(int $quantita = 1): bool
    {
        return $this->attivo && $this->giacenza >= $quantita;
    }
}
