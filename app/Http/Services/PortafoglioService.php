<?php

namespace App\Http\Services;

use App\Models\MovimentoPortafoglio;

class PortafoglioService
{
    public function __construct()
    {


    }

    public function annullaMovimento($prodottoId, $prodottoType)
    {


        $movimento = MovimentoPortafoglio::withoutGlobalScope('filtroOperatore')->where('prodotto_id', $prodottoId)->where('prodotto_type', $prodottoType)->first();
        abort_if(!$movimento, 404, 'Questo movimento non esiste ' . $prodottoId . ' ' . $prodottoType);
        if ($movimento && $movimento->portafoglio) {
            $record = new MovimentoPortafoglio();
            $record->agente_id = $movimento->agente_id;
            $record->portafoglio = $movimento->portafoglio;
            $record->importo = $movimento->importo * -1;
            $record->prodotto_id = $movimento->prodotto_id;
            $record->prodotto_type = $movimento->prodotto_type;
            $record->descrizione = 'Annullamento ' . $movimento->descrizione;
            $record->save();
        }

    }
}
