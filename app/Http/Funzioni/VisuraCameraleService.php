<?php

namespace App\Http\Funzioni;

use App\Http\MieClassi\AlertMessage;
use App\Models\Provincia;
use App\Models\VisuraCamerale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisuraCameraleService
{
    private const ENDPOINT_SANDBOX = 'https://test.visurecamerali.openapi.it/';
    private const ENDPOINT_PRODUCTION = 'https://visurecamerali.openapi.it/';

    public $error = false;
    public $message;
    public $response;

    public function impresa(Request $request)
    {
        $query = [
            'denominazione' => $request->input('ragione_sociale'),
        ];

        if ($request->input('provincia')) {
            $query['provincia'] = $this->normalizeProvincia($request->input('provincia'));
            if (!$query['provincia']) {
                unset($query['provincia']);
            }
        }


        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearer()
            ])->get($this->endpoint() . 'impresa', $query);

        } catch (\Exception $exception) {

            $this->messaggioErrore($exception->getCode(), $exception->getMessage());
            return null;
        }


        return $this->response($response);
    }

    public function richiediVisura($naturaGiuridica, $partitaIva)
    {

        Log::info('Richiesta visura ' . $naturaGiuridica . ' per ' . $partitaIva);
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
        ])->get($this->endpoint() . $tipo . '/' . $id);


        return $this->response($response);

    }

    public function richiediAllegato($id, $tipo)
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer()
        ])->get($this->endpoint() . $tipo . '/' . $id . '/allegati');


        return $this->response($response);

    }


    public function ordinariaIndividuale($partitaIva)
    {
        $query = [
            'cf_piva_id' => $partitaIva,
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer()
        ])->post($this->endpoint() . 'ordinaria-impresa-individuale', $query);


        return $this->response($response);

    }

    public function ordinariaSocietaPersone($partitaIva)
    {
        $query = [
            'cf_piva_id' => $partitaIva,
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer()
        ])->post($this->endpoint() . 'ordinaria-societa-persone', $query);


        return $this->response($response);

    }

    public function ordinariaSocietaCapitale($partitaIva)
    {
        $query = [
            'cf_piva_id' => $partitaIva,
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bearer()
        ])->post($this->endpoint() . 'ordinaria-societa-capitale', $query);


        return $this->response($response);

    }

    protected function messaggioErrore($code, $error)
    {
        $this->error = true;
        $this->message = $error;

        $alert = new AlertMessage();
        $alert->titolo('Errore ' . $code, 'danger')->messaggio($error, 'danger')->flash();
    }


    protected function endpoint(): string
    {
        if (config('services.openapi.sandbox')) {
            return rtrim((string) (config('services.openapi.visure_camerali_base_url_sandbox') ?: self::ENDPOINT_SANDBOX), '/') . '/';
        } else {
            return rtrim((string) (config('services.openapi.visure_camerali_base_url_production') ?: self::ENDPOINT_PRODUCTION), '/') . '/';
        }
    }

    protected function bearer()
    {
        return (string) (
            config('services.openapi.bearer_visure_camerali')
            ?: env('OPENAPI_BEARER_VISURE_CAMERALI')
            ?: config('services.openapi.bearer_visure')
            ?: env('OPENAPI_BEARER_VISURE')
            ?: env('OPENAPI_BEARER')
        );
    }

    protected function normalizeProvincia($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            $provincia = Provincia::find((int) $raw);
            if ($provincia && $provincia->sigla_automobilistica) {
                return strtoupper((string) $provincia->sigla_automobilistica);
            }

            return null;
        }

        $candidate = strtoupper(preg_replace('/[^A-Za-z]/', '', $raw) ?: '');
        if (strlen($candidate) === 2) {
            return $candidate;
        }

        $provincia = Provincia::where('nome', 'like', $raw)->orWhere('nome', 'like', '%' . $raw . '%')->first();
        if ($provincia && $provincia->sigla_automobilistica) {
            return strtoupper((string) $provincia->sigla_automobilistica);
        }

        return null;
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
            $errorCode = $json->error ?? (string) $response->status();
            $errorMessage = $json->message ?? 'Errore servizio visure camerali';
            $this->messaggioErrore($errorCode, $errorMessage);
            return null;
        }

    }
}
