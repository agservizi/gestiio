<?php

namespace App\Http\Controllers\Backend\SottoClassiEnergia;

use App\Models\GestoreContrattoEnergia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProdottoEnergiaAbstract
{
    protected static $tipoProdotto;

    /**
     * @param  $tipoDocumento
     * @return ProdottoEnergiaAbstract
     */
    public static function constructor($tipoProdotto)
    {
        self::$tipoProdotto = $tipoProdotto;

        switch ($tipoProdotto) {
            case 'ProdottoEnergiaEgea':
                return new Egea;

            case 'ProdottoEnergiaEnelBusiness':
                return new EnelBusiness;

            case 'ProdottoEnergiaEnelConsumer':
                return new EnelConsumer;

            case 'ProdottoEnergiaIllumia':
                return new Illumia;

            case 'ProdottoEnergiaGenerico':
                return new Generico;

        }
    }

    public function salvaDatiProdotto($contrattoEnergia, $request)
    {
        return Model::class;
    }

    public function rulesProdotto($id = null)
    {
        return [];
    }

    public function determinaProvvigione(Request $request)
    {
        return 0;
    }

    protected function calcolaProvvigioneDaGestore(Request $request, ?int $fallbackGestoreId = null, bool $consideraBollettino = false): float
    {
        $gestoreId = (int) $request->input('gestore_id');
        if ($gestoreId <= 0) {
            $gestoreId = (int) $fallbackGestoreId;
        }

        $gestore = $gestoreId > 0 ? GestoreContrattoEnergia::find($gestoreId) : null;
        if (! $gestore) {
            return 0;
        }

        $isBusiness = $gestore->categoria_pratica === 'business'
            || str_contains(strtolower((string) $gestore->model_prodotto), 'business');

        if ($consideraBollettino && $request->input('modalita_pagamento') === 'bollettino') {
            if ($isBusiness && $gestore->importo_pagamento_bollettino_business !== null) {
                return (float) $gestore->importo_pagamento_bollettino_business;
            }
            if ($gestore->importo_pagamento_bollettino !== null) {
                return (float) $gestore->importo_pagamento_bollettino;
            }
        }

        if ($isBusiness && $gestore->importo_contratto_business !== null) {
            return (float) $gestore->importo_contratto_business;
        }

        return (float) ($gestore->importo_contratto ?? 0);
    }

    public function completaNotifica($email, $contratto) {}
}
