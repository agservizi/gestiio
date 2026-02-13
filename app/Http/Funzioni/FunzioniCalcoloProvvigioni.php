<?php

namespace App\Http\Funzioni;

use App\Models\Agente;
use App\Models\FasciaListinoTipoContratto;
use App\Models\User;

trait FunzioniCalcoloProvvigioni
{
    static $logCalcolo = [];

    protected static function determinaSogliaContrattiTelefonia($conteggioContratti, $listinoId, $tipoContrattoid)
    {
        if ($conteggioContratti == 0) {
            return null;
        }
        if (!$listinoId) {
            return null;
        }


        $f = FasciaListinoTipoContratto::query()
            ->where('listino_id', $listinoId)
            ->where('tipo_contratto_id', $tipoContrattoid)
            ->where('da_contratti', '<=', $conteggioContratti)
            ->where(
                function ($q) use ($conteggioContratti) {
                    return $q->where('a_contratti', '>=', $conteggioContratti)->orWhereNull('a_contratti');
                }
            )
            ->orderByDesc('da_contratti')
            ->first();

        if ($f) {
            self::aggiornaLog(__FUNCTION__, $conteggioContratti . " contratti, trovata fascia da " . $f->da_contratti . ' a ' . $f->a_contratti . ' bonus ' . $f->importo_bonus);
        } else {
            self::aggiornaLog(__FUNCTION__, $conteggioContratti . ' contratti, fascia non trovata');
        }

        return $f;
    }

    public static function aggiornaLog($funzione, $testo)
    {
        self::$logCalcolo[] = $funzione . ': ' . $testo . '|';

    }


}
