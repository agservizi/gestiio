<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RigaOrdineEbike extends Model
{
    protected $table = 'righe_ordini_ebike';

    protected $fillable = [
        'ordine_id',
        'prodotto_id',
        'nome_prodotto',
        'quantita',
        'prezzo_unitario',
    ];

    protected $casts = [
        'quantita' => 'integer',
        'prezzo_unitario' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function ordine(): BelongsTo
    {
        return $this->belongsTo(OrdineEbike::class, 'ordine_id');
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(ProdottoEbike::class, 'prodotto_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */

    public function subtotale(): float
    {
        return (float) $this->quantita * (float) $this->prezzo_unitario;
    }
}
