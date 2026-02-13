<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdottoEnergiaEgea extends Model
{

    protected $table = "prodotto_energia_egea";

    protected $primaryKey = 'contratto_energia_id';

    protected $casts = [
        'data_rilascio' => 'date',
        'data_scadenza' => 'date'
    ];

    public const SPEDIZIONE_FATTURA = ['posta_ordinaria' => 'Posta ordinaria', 'email' => 'Email'];
    public const TIPI_ATTIVAZIONE = ['switch' => 'Switch', 'subentro' => 'Subentro', 'Voltura (allegare modulo)' => 'Voltura (allegare modulo) ', 'Voltura titolo IV' => 'Voltura titolo IV', 'Attivazione' => 'Attivazione', 'Richiesta nuovo' => 'Richiesta nuovo'];
    public const CAT_USO_ARERA = ['c1' => 'C1', 'c2' => 'C2', 'c3' => 'C3', 'c4' => 'C4', 'c5' => 'C5'];
    public const TIPOLOGIA_PDR = ['domestico_residente' => 'Cliente domestico RESIDENTE', 'domestico_non_residente' => 'Cliente domestico NON RESIDENTE', '1cond' => '1 cond. con uso domestico', '2usi' => '2 usi diversi'];
    public const MERCATO_ATTUALE = ['libero' => 'Libero', 'tutela' => 'Tutela', 'fui' => 'FUI'];
    public const MERCATO_ATTUALE_LUCE = ['libero' => 'Libero', 'tutela' => 'Maggior tutela'];
    public const MERCATO_PROVENIENZA = ['tutela' => 'Maggior tutela'];
    public const TIPOLOGIA_USO = ['domestico_residente' => 'Domestico RESIDENTE', 'domestico_non_residente' => 'Domestico NON RESIDENTE', 'altri_usi' => 'Altri usi', 'pertinenze' => 'Pertinenze'];


    public function comune()
    {
        return $this->hasOne(Comune::class, 'id', 'citta');
    }
}
