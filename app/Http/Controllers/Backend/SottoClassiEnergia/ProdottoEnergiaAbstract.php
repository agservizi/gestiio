<?php

namespace App\Http\Controllers\Backend\SottoClassiEnergia;

use App\Models\Comune;
use App\Models\ContrattoEnergia;
use App\Models\ProdottoEnergiaEnelBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use function App\siNo;


class ProdottoEnergiaAbstract
{
    protected static $tipoProdotto;

    /**
     * @param $tipoDocumento
     * @return ProdottoEnergiaAbstract
     */
    public static function constructor($tipoProdotto)
    {
        self::$tipoProdotto = $tipoProdotto;

        switch ($tipoProdotto) {
            case 'ProdottoEnergiaEgea':
                return new Egea();

            case 'ProdottoEnergiaEnelBusiness':
                return new EnelBusiness();

            case 'ProdottoEnergiaEnelConsumer':
                return new EnelConsumer();

            case 'ProdottoEnergiaIllumia':
                return new Illumia();

            case 'ProdottoEnergiaGenerico':
                return new Generico();

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


    public function completaNotifica($email, $contratto)
    {

    }
}
