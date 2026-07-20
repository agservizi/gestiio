<?php

namespace App\Http\Controllers\Backend;

use App\Enums\FatturaProformaStatus;
use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Http\Services\FatturaProformaService;
use App\Models\ProduzioneOperatore;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ProduzioneOperatoreController extends Controller
{
    protected $conFiltro = false;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ProduzioneOperatore::class);

        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);

        $ordinamenti = [
            'recente' => ['testo' => 'Più recente', 'filtro' => function ($q) {
                return $q->orderByDesc('anno')->orderByDesc('mese');
            }],
            'nominativo' => ['testo' => 'Nominativo', 'filtro' => function ($q) {
                return $q->join('users', 'users.id', '=', 'produzioni_operatori.user_id')
                    ->orderBy('users.cognome')
                    ->orderBy('users.nome')
                    ->select('produzioni_operatori.*');
            }],
            'totale' => ['testo' => 'Totale', 'filtro' => function ($q) {
                return $q->orderByDesc('importo_totale');
            }],
        ];

        $orderByUser = Auth::user()->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } elseif ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if (! isset($ordinamenti[$orderBy])) {
            $orderBy = 'recente';
        }

        if ($orderByUser != $orderByString) {
            Auth::user()->setExtra([$nomeClasse => $orderBy]);
        }

        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.ProduzioneOperatore.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])),
            ];
        }

        $agenti = User::whereHas('permissions', fn ($q) => $q->where('name', 'agente'))
            ->orderBy('cognome')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cognome']);

        return view('Backend.ProduzioneOperatore.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco produzioni',
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $request->input('filtro', 'tutti'),
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => null,
            'testoCerca' => 'Cerca agente',
            'agenti' => $agenti,
            'filtri' => [
                'anno' => $request->input('anno'),
                'mese' => $request->input('mese'),
                'agente_id' => $request->input('agente_id'),
                'senza_proforma' => $request->boolean('senza_proforma'),
                'importo_positivo' => $request->boolean('importo_positivo'),
            ],
        ]);
    }

    public function show($id)
    {
        $record = ProduzioneOperatore::with(['agente', 'fatturaProforma'])->find($id);
        abort_if(! $record, 404, 'Questa produzione non esiste');
        $this->authorize('view', $record);

        return view('Backend.ProduzioneOperatore.show', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => 'Produzione '.$record->mese.'/'.$record->anno,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco produzioni'],
        ]);
    }

    public function previewProforma($id)
    {
        $produzione = ProduzioneOperatore::with('agente')->find($id);
        abort_if(! $produzione, 404, 'Questa produzione non esiste');
        $this->authorize('createProforma', $produzione);

        $service = new FatturaProformaService($produzione->anno, $produzione->mese);
        $preview = $service->previewAgente($produzione->user_id);

        if (! ($preview['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'error' => $preview['error'] ?? 'Impossibile generare l\'anteprima',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'produzione_id' => $produzione->id,
            'agente' => optional($produzione->agente)->nominativo(),
            'periodo' => $preview['periodo'],
            'totale' => $preview['totale'],
            'totale_formattato' => importo($preview['totale'], true),
            'linee' => collect($preview['linee'])->map(fn ($l) => [
                'descrizione' => $l['descrizione'],
                'quantita' => $l['quantita'],
                'imponibile' => $l['imponibile'],
                'imponibile_formattato' => importo($l['imponibile'], true),
            ])->values(),
            'intestazione_incompleta' => $preview['intestazione_incompleta'],
            'crea_url' => action([self::class, 'creaProforma'], $produzione->id),
        ]);
    }

    public function creaProforma(Request $request, $id)
    {
        $produzione = ProduzioneOperatore::find($id);
        abort_if(! $produzione, 404, 'Questa produzione non esiste');
        $this->authorize('createProforma', $produzione);

        $meseCorrente = now()->startOfMonth();
        $periodoProd = Carbon::createFromDate($produzione->anno, $produzione->mese, 1);
        if (! $periodoProd->lessThan($meseCorrente)) {
            $alert = new AlertMessage;
            $alert->messaggio('Il mese corrente non è ancora chiudibile.', 'warning')->flash();

            return redirect()->action([self::class, 'index']);
        }

        $service = new FatturaProformaService($produzione->anno, $produzione->mese);
        $res = $service->creaFatturaProformaAgente($produzione->user_id, FatturaProformaStatus::BOZZA);

        $alert = new AlertMessage;
        if ($res === false) {
            $alert->messaggio($service->getErrore() ?: 'Creazione proforma non riuscita', 'danger')->flash();

            return redirect()->action([self::class, 'index']);
        }

        $alert->messaggio('Proforma creata n. '.$res['numero'], 'success')->flash();

        return redirect()->action([FatturaProformaController::class, 'show'], $res['id']);
    }

    public function ricalcola(Request $request, $id)
    {
        $produzione = ProduzioneOperatore::find($id);
        abort_if(! $produzione, 404, 'Questa produzione non esiste');
        $this->authorize('recalculate', $produzione);

        if ($produzione->fattura_proforma_id) {
            $alert = new AlertMessage;
            $alert->messaggio('Produzione già collegata a una proforma: usa Rigenera sulla proforma.', 'warning')->flash();

            return redirect()->action([FatturaProformaController::class, 'show'], $produzione->fattura_proforma_id);
        }

        $produzione->ricalcolaProduzione();
        $produzione->refresh();

        $alert = new AlertMessage;
        $alert->messaggio('Produzione ricalcolata. Totale: '.importo($produzione->importo_totale, true), 'success')->flash();

        if ($request->wantsJson() || $request->ajax()) {
            return ['success' => true, 'redirect' => action([self::class, 'show'], $produzione->id)];
        }

        return redirect()->action([self::class, 'show'], $produzione->id);
    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    protected function applicaFiltri($request)
    {
        $queryBuilder = ProduzioneOperatore::query()
            ->where('produzioni_operatori.user_id', '>', 2)
            ->with('agente:id,nome,cognome')
            ->with('fatturaProforma:id,numero,status');

        if (Auth::user()->hasPermissionTo('agente')
            && ! Auth::user()->hasPermissionTo('admin')
            && ! Auth::user()->hasRole('admin')) {
            $queryBuilder->where('produzioni_operatori.user_id', Auth::id());
        }

        $term = $request->input('cerca');
        if ($term) {
            $this->conFiltro = true;
            $queryBuilder->whereHas('agente', function ($q) use ($term) {
                $arrTerm = explode(' ', $term);
                foreach ($arrTerm as $t) {
                    $t = trim($t);
                    if ($t === '') {
                        continue;
                    }
                    $q->where(function ($qq) use ($t) {
                        $qq->where('nome', 'like', "%{$t}%")
                            ->orWhere('cognome', 'like', "%{$t}%");
                    });
                }
            });
        }

        if ($request->filled('anno')) {
            $this->conFiltro = true;
            $queryBuilder->where('anno', (int) $request->input('anno'));
        }

        if ($request->filled('mese')) {
            $this->conFiltro = true;
            $queryBuilder->where('mese', (int) $request->input('mese'));
        }

        if ($request->filled('agente_id') && Auth::user()->hasPermissionTo('admin')) {
            $this->conFiltro = true;
            $queryBuilder->where('produzioni_operatori.user_id', (int) $request->input('agente_id'));
        }

        if ($request->boolean('senza_proforma')) {
            $this->conFiltro = true;
            $queryBuilder->whereNull('fattura_proforma_id');
        }

        if ($request->boolean('importo_positivo')) {
            $this->conFiltro = true;
            $queryBuilder->where('importo_totale', '>', 0);
        }

        return $queryBuilder;
    }
}
