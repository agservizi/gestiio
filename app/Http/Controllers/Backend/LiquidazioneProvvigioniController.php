<?php

namespace App\Http\Controllers\Backend;

use App\Enums\FatturaProformaStatus;
use App\Exports\LiquidazioneProvvigioniExport;
use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Http\Services\FatturaProformaService;
use App\Http\Services\LiquidazioneProvvigioniService;
use App\Models\FatturaProforma;
use App\Models\ProduzioneOperatore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LiquidazioneProvvigioniController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin') || Auth::user()?->hasRole('admin'), 403);

        $anno = (int) $request->input('anno', now()->year);
        $mese = (int) $request->input('mese', now()->month);
        if ($mese < 1 || $mese > 12) {
            $mese = (int) now()->month;
        }

        $service = new LiquidazioneProvvigioniService($anno, $mese);
        $righe = $service->righeMese();
        $riepilogo = $service->riepilogo();

        return view('Backend.LiquidazioneProvvigioni.index', [
            'titoloPagina' => 'Liquidazione provvigioni',
            'mainMenu' => 'liquidazione',
            'anno' => $anno,
            'mese' => $mese,
            'righe' => $righe,
            'riepilogo' => $riepilogo,
            'elencoAnni' => range((int) now()->year, (int) config('configurazione.primoAnno', 2022)),
        ]);
    }

    public function generaTutte(Request $request)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin') || Auth::user()?->hasRole('admin'), 403);

        $anno = (int) $request->input('anno', now()->year);
        $mese = (int) $request->input('mese', now()->month);

        $n = (new FatturaProformaService($anno, $mese))->creaFattureProformaTutti();

        $alert = new AlertMessage;
        $alert->messaggio("Generate {$n} proforma per {$mese}/{$anno}", 'success')->flash();

        return redirect()->action([self::class, 'index'], ['anno' => $anno, 'mese' => $mese]);
    }

    public function generaSingola(Request $request, $produzioneId)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin') || Auth::user()?->hasRole('admin'), 403);

        $produzione = ProduzioneOperatore::findOrFail($produzioneId);
        $service = new FatturaProformaService((int) $produzione->anno, (int) $produzione->mese);
        $result = $service->creaFatturaProformaAgente((int) $produzione->user_id, FatturaProformaStatus::EMESSA);

        $alert = new AlertMessage;
        if ($result === false) {
            $alert->messaggio($service->getErrore() ?: 'Creazione fallita', 'danger')->flash();
        } else {
            $alert->messaggio('Proforma #'.$result['numero'].' creata', 'success')->flash();
        }

        return redirect()->action([self::class, 'index'], [
            'anno' => $produzione->anno,
            'mese' => $produzione->mese,
        ]);
    }

    public function segnaPagateBatch(Request $request)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin') || Auth::user()?->hasRole('admin'), 403);

        $validated = $request->validate([
            'proforma_ids' => 'required|array|min:1',
            'proforma_ids.*' => 'integer',
            'data_pagamento' => 'required|date',
            'riferimento_pagamento' => 'required|string|max:255',
            'anno' => 'required|integer',
            'mese' => 'required|integer|min:1|max:12',
        ]);

        $svc = new FatturaProformaService((int) $validated['anno'], (int) $validated['mese']);
        $result = $svc->segnaPagateBatch(
            $validated['proforma_ids'],
            $validated['data_pagamento'],
            $validated['riferimento_pagamento']
        );

        $alert = new AlertMessage;
        $msg = "Pagate {$result['ok']} proforma";
        if ($result['fail'] > 0) {
            $msg .= ", {$result['fail']} errori";
            $alert->messaggio($msg.': '.implode('; ', array_slice($result['errors'], 0, 3)), 'warning')->flash();
        } else {
            $alert->messaggio($msg, 'success')->flash();
        }

        return redirect()->action([self::class, 'index'], [
            'anno' => $validated['anno'],
            'mese' => $validated['mese'],
        ]);
    }

    public function exportExcel(Request $request)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin') || Auth::user()?->hasRole('admin'), 403);

        $anno = (int) $request->input('anno', now()->year);
        $mese = (int) $request->input('mese', now()->month);
        $service = new LiquidazioneProvvigioniService($anno, $mese);

        $filename = sprintf('liquidazione-provvigioni-%04d-%02d.xlsx', $anno, $mese);

        return Excel::download(new LiquidazioneProvvigioniExport($service->righeMese(), $anno, $mese), $filename);
    }

    public function report(Request $request)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin') || Auth::user()?->hasRole('admin'), 403);

        $anno = (int) $request->input('anno', now()->year);
        $mese = (int) $request->input('mese', now()->month);
        $service = new LiquidazioneProvvigioniService($anno, $mese);

        return view('Backend.LiquidazioneProvvigioni.report', [
            'titoloPagina' => 'Report liquidazione',
            'mainMenu' => 'liquidazione',
            'anno' => $anno,
            'mese' => $mese,
            'righe' => $service->righeMese(),
            'riepilogo' => $service->riepilogo(),
            'elencoAnni' => range((int) now()->year, (int) config('configurazione.primoAnno', 2022)),
        ]);
    }
}
