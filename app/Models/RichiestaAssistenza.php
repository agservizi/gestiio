<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RichiestaAssistenza extends Model
{
    use HasFactory;

    protected $table = 'richieste_assistenza';

    public const NOME_SINGOLARE = 'richiesta assistenza';

    public const NOME_PLURALE = 'richieste assistenze';

    public const TIPO_NUOVA_ATTIVAZIONE_SPID = 'nuova_attivazione_spid';

    public const TIPO_ASSISTENZA_SPID = 'assistenza_spid';

    public const TIPO_ASSISTENZA_GENERICA = 'assistenza_generica';

    protected $casts = [
        'importo_economico' => 'decimal:2',
        'economico_contabilizzato' => 'boolean',
    ];

    public static function tipiOperazione(): array
    {
        return [
            self::TIPO_NUOVA_ATTIVAZIONE_SPID => 'Nuova attivazione SPID',
            self::TIPO_ASSISTENZA_SPID => 'Recupero / assistenza SPID già attivo',
            self::TIPO_ASSISTENZA_GENERICA => 'Assistenza generica',
        ];
    }

    public static function importoPerTipoOperazione(?string $tipo): float
    {
        return $tipo === self::TIPO_NUOVA_ATTIVAZIONE_SPID ? 22.00 : 5.00;
    }

    public function tipoOperazioneLabel(): string
    {
        return self::tipiOperazione()[$this->tipo_operazione] ?? 'Da classificare';
    }

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteAssistenza::class, 'cliente_id');
    }

    public function prodotto(): BelongsTo
    {
        return $this->belongsTo(ProdottoAssistenza::class, 'prodotto_assistenza_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | PER BLADE
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ALTRO
    |--------------------------------------------------------------------------
    */
}
