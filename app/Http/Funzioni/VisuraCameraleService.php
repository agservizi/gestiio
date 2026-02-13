<?php

namespace App\Http\Funzioni;

use App\Http\MieClassi\AlertMessage;
use App\Models\Provincia;
use App\Models\VisuraCamerale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VisuraCameraleService
{

    public $error = false;
    public $message;
    public $response;

    public function impresa(Request $request)
    {
        $query = [
            'denominazione' => $request->input('ragione_sociale'),
        ];

        if ($request->input('provincia')) {
            $query['provincia'] = Provincia::find($request->input('provincia'))->sigla_automobilistica;
        }


        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearer()
            ])->get($this->url('https://test.visurecamerali.openapi.it/impresa'), $query);

        } catch (\Exception $exception) {

            $this->messaggioErrore($exception->getCode(), $exception->getMessage());
            return null;
        }


        return $this->response($response);
    }

    public function richiediVisura($naturaGiuridica, $partitaIva)
    {

        \Log::info('Richiesta visura ' . $naturaGiuridica . ' per ' . $partitaIva);
        switch ($naturaGiuridica) {
            case 'impresa-individuale':
                $res = $this->ordinariaIndividuale($partitaIva);
                break;

            case 'societa-capitale':
                $res = $this->ordinariaSocietaCapitale($partitaIva);
                break;
            case 'societa-persone':
                $res = $this->ordinariaSocietaPersone($partitaIva);
                break;
        }

        return $res;

    }


    public function calcolaPrezzo($tipo)
    {
        return config('configurazione.prezzo_visura.' . $tipo);
    }


    public function aggiornaVisura($id, $tipo)
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer()
        ])->get($this->url("https://test.visurecamerali.openapi.it/$tipo/$id"));


        return $this->response($response);

    }

    public function richiediAllegato($id, $tipo)
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer()
        ])->get($this->url("https://test.visurecamerali.openapi.it/$tipo/$id/allegati"));


        return $this->response($response);

    }


    public function ordinariaIndividuale($partitaIva)
    {
        $query = [
            'cf_piva_id' => $partitaIva,
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer()
        ])->post($this->url('https://test.visurecamerali.openapi.it/ordinaria-impresa-individuale'), $query);


        return $this->response($response);

    }

    public function ordinariaSocietaPersone($partitaIva)
    {
        $query = [
            'cf_piva_id' => $partitaIva,
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer()
        ])->post($this->url('https://test.visurecamerali.openapi.it/ordinaria-societa-persone'), $query);


        return $this->response($response);

    }

    public function ordinariaSocietaCapitale($partitaIva)
    {
        $query = [
            'cf_piva_id' => $partitaIva,
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer()
        ])->post($this->url('https://test.visurecamerali.openapi.it/ordinaria-societa-capitale'), $query);


        return $this->response($response);

    }

    protected function messaggioErrore($code, $error)
    {
        $this->error = true;
        $this->message = $error;

        $alert = new AlertMessage();
        $alert->titolo('Errore ' . $code, 'danger')->messaggio($error, 'danger')->flash();
    }


    protected function url($url)
    {
        if (env('OPENAPI_SANDBOX')) {
            return $url;
        } else {
            return str_replace('test.', '', $url);
        }
    }

    protected function bearer()
    {
        return env('OPENAPI_BEARER');
    }

    protected function response($response)
    {

        if ($response->status() == 200) {
            $this->error = false;
            return json_decode($response->body());
        } elseif ($response->status() == 204) {
            $this->messaggioErrore('Nessun risultato', 'Nessun risultato trovato');

            return null;
        } else {
            $json = json_decode($response->body());
            $this->messaggioErrore($json->error, $json->message);
            return null;
        }

    }
}
