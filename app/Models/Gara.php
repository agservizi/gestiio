<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gara extends Model
{
    protected $table = 'gare';

    public const NOME_SINGOLARE = 'gara';

    public const NOME_PLURALE = 'gare';

    public const CANALI = [
        'telefonia' => 'Telefonia',
        'energia' => 'Energia',
    ];

    public const AMBITI = [
        'tutti' => 'Tutti gli affiliati',
        'listino' => 'Listino telefonia',
        'agenti' => 'Affiliati selezionati',
    ];

    protected $guarded = [];

    protected $casts = [
        'attivo' => 'boolean',
        'is_business' => 'boolean',
        'anno' => 'integer',
        'mese' => 'integer',
    ];

    public function soglie(): HasMany
    {
        return $this->hasMany(GaraSoglia::class, 'gara_id')->orderBy('da_valore');
    }

    public function risultati(): HasMany
    {
        return $this->hasMany(GaraRisultato::class, 'gara_id');
    }

    public function agenti(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'gara_agente', 'gara_id', 'user_id');
    }

    public function listino(): BelongsTo
    {
        return $this->belongsTo(Listino::class, 'listino_id');
    }

    public function telefoniaOfferta(): BelongsTo
    {
        return $this->belongsTo(TelefoniaOfferta::class, 'telefonia_offerta_id');
    }

    public function tipoContratto(): BelongsTo
    {
        return $this->belongsTo(TipoContratto::class, 'tipo_contratto_id');
    }

    public function scopeAttive($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeDelMese($query, int $anno, int $mese)
    {
        return $query->where('anno', $anno)->where('mese', $mese);
    }

    public function labelCanale(): string
    {
        return self::CANALI[$this->canale] ?? (string) $this->canale;
    }

    public function labelPeriodo(): string
    {
        return sprintf('%02d/%04d', (int) $this->mese, (int) $this->anno);
    }
}
