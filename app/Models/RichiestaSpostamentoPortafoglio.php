<?php

namespace App\Models;

use App\Enums\TipiPortafoglioEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RichiestaSpostamentoPortafoglio extends Model
{
    protected $table = 'richieste_spostamento_portafoglio';

    protected $casts = [
        'importo' => 'float',
        'applicata_il' => 'datetime',
    ];

    public function agente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agente_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function portafoglioDaTesto(): string
    {
        return TipiPortafoglioEnum::tryFrom($this->portafoglio_da)?->testo() ?? 'Portafoglio';
    }

    public function portafoglioATesto(): string
    {
        return TipiPortafoglioEnum::tryFrom($this->portafoglio_a)?->testo() ?? 'Portafoglio';
    }
}
