<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelefoniaOfferta extends Model
{
    protected $table = 'telefonia_offerte';

    public const NOME_SINGOLARE = 'offerta telefonia';

    public const NOME_PLURALE = 'offerte telefonia';

    public const COMMODITIES = [
        'fibra' => 'Fibra',
        'fwa' => 'FWA',
        'altro' => 'Altro',
    ];

    public const SOURCES = [
        'drive_pdf_sync' => 'Sync PDF Drive',
        'manual' => 'Manuale',
    ];

    protected $guarded = [];

    protected $casts = [
        'attivo' => 'boolean',
        'prezzo_mese' => 'float',
        'valido_dal' => 'date',
        'valido_al' => 'date',
        'synced_at' => 'datetime',
    ];

    public function gestore(): BelongsTo
    {
        return $this->belongsTo(Gestore::class, 'gestore_id');
    }

    public function tipoContratto(): BelongsTo
    {
        return $this->belongsTo(TipoContratto::class, 'tipo_contratto_id');
    }

    public function scopeAttive($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeValideOggi($query)
    {
        $oggi = now()->toDateString();

        return $query
            ->where(function ($q) use ($oggi) {
                $q->whereNull('valido_dal')->orWhere('valido_dal', '<=', $oggi);
            })
            ->where(function ($q) use ($oggi) {
                $q->whereNull('valido_al')->orWhere('valido_al', '>=', $oggi);
            });
    }

    public static function selected($id): string
    {
        if (! $id) {
            return '';
        }
        $record = self::find($id);
        if (! $record) {
            return '';
        }

        return "<option value='{$record->id}' selected>{$record->nome}</option>";
    }
}
