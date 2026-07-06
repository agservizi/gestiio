<?php

namespace App\Http\Controllers\Backend;

use App\Enums\TipiPortafoglioEnum;
use App\Http\Controllers\Controller;
use App\Http\Funzioni\VisuraCameraleService;
use App\Http\MieClassi\AlertMessage;
use App\Http\Services\OpenApiCatastoService;
use App\Http\Services\OpenApiVisureService;
use App\Models\AllegatoServizio;
use App\Models\AllegatoVisura;
use App\Models\EsitoVisura;
use App\Models\MovimentoPortafoglio;
use App\Models\Notifica;
use App\Models\TabMotivoKo;
use App\Models\TipoVisura;
use App\Models\User;
use App\Models\Visura;
use App\Notifications\NotificaVisuraCambioEsitoAdAgente;
use App\Rules\PartitaIvaRule;
use App\Support\VisuraAttachmentMailer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SoapClient;

use function App\getInputCheckbox;
use function App\getInputToUpper;
use function App\importo;

class VisuraController extends Controller
{
    protected $conFiltro = false;

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * Display a listing of the resource.
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);

        $ordinamenti = [
            'recente' => ['testo' => 'Più recente', 'filtro' => function ($q) {
                return $q->orderBy('id', 'desc');
            }],

            'nominativo' => ['testo' => 'Nominativo', 'filtro' => function ($q) {
                return $q->orderBy('cognome')->orderBy('nome');
            }],

        ];

        $orderByUser = $this->currentUser()->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } elseif ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($orderByUser != $orderByString) {
            $this->currentUser()->setExtra([$nomeClasse => $orderBy]);
        }

        // Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $totali = [
            'totale' => (clone $recordsQB)->count(),
            'in_lavorazione' => (clone $recordsQB)->whereNull('esito_finale')->count(),
            'ok' => (clone $recordsQB)->where('esito_finale', 'ok')->count(),
            'ko' => (clone $recordsQB)->where('esito_finale', 'ko')->count(),
            'sla_attention' => (clone $recordsQB)->whereNull('esito_finale')->where('created_at', '<=', now()->subDays(3))->count(),
        ];

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        $puoModificare = $this->puoModificare();
        $puoModificareEsito = $this->puoModificareEsito();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.Visura.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                    'puoModificare' => $puoModificare,
                    'puoModificareEsito' => $puoModificareEsito,
                ])->render()),
                'kpi' => base64_encode(view('Backend.Visura._kpi', [
                    'totali' => $totali,
                ])->render()),
            ];

        }

        $agentiFiltro = collect();
        if ($this->currentUser()->hasAnyPermission(['admin', 'supervisore'])) {
            $agentiFiltro = User::query()
                ->whereHas('permissions', function ($query) {
                    $query->where('name', 'agente');
                })
                ->orderBy('cognome')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cognome']);
        }

        return view('Backend.Visura.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco '.Visura::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova '.Visura::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in nominativo, P.IVA/CF, agente',
            'puoModificare' => $puoModificare,
            'puoModificareEsito' => $puoModificareEsito,
            'agentiFiltro' => $agentiFiltro,
            'totali' => $totali,

        ]);

    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = Visura::query()
            ->with('esito')
            ->with('agente')
            ->with('tipo:id,nome,openapi_hash_visura')
            ->withCount('allegati')
            ->withCount('allegatiPerCliente');
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(function ($query) use ($t) {
                    $query
                        ->where(DB::raw('concat_ws(\' \',nome,cognome,ragione_sociale,partita_iva,codice_fiscale)'), 'like', "%$t%")
                        ->orWhereHas('agente', function ($agenteQuery) use ($t) {
                            $agenteQuery->where(DB::raw('concat_ws(\' \',alias,nome,cognome,email)'), 'like', "%$t%");
                        });
                });
            }
            $this->conFiltro = true;
        }

        if ($request->filled('esito_id')) {
            $queryBuilder->where('esito_id', $request->input('esito_id'));
            $this->conFiltro = true;
        }

        if ($request->filled('tipo_visura_id')) {
            $queryBuilder->where('tipo_visura_id', $request->input('tipo_visura_id'));
            $this->conFiltro = true;
        }

        if ($request->filled('agente_id') && $this->currentUser()->hasAnyPermission(['admin', 'supervisore'])) {
            $queryBuilder->where('agente_id', $request->input('agente_id'));
            $this->conFiltro = true;
        }

        if ($request->filled('data_da')) {
            $queryBuilder->whereDate('data', '>=', $request->input('data_da'));
            $this->conFiltro = true;
        }

        if ($request->filled('data_a')) {
            $queryBuilder->whereDate('data', '<=', $request->input('data_a'));
            $this->conFiltro = true;
        }

        if ($request->input('filtro_sla') === 'attenzione') {
            $queryBuilder->whereNull('esito_finale')->where('created_at', '<=', now()->subDays(3));
            $this->conFiltro = true;
        }

        if ($request->input('con_allegati') === '1') {
            $queryBuilder->havingRaw('(allegati_count + allegati_per_cliente_count) > 0');
            $this->conFiltro = true;
        }

        if ($request->input('con_allegati') === '0') {
            $queryBuilder->havingRaw('(allegati_count + allegati_per_cliente_count) = 0');
            $this->conFiltro = true;
        }

        return $queryBuilder;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return mixed
     */
    public function create($servizio = null)
    {

        if (! $servizio) {
            return view('Backend.Visura.create', [
                'record' => new Visura,
                'titoloPagina' => 'Nuova visura',
                'controller' => get_class($this),
                'serviziVisura' => TipoVisura::query()->orderBy('nome')->get(),
            ]);
        }
        $record = new Visura;
        $record->data = today();
        $record->uid = Str::ulid();

        if ($this->currentUser()->hasPermissionTo('agente')) {
            $record->agente_id = Auth::id();
        }

        $tipoServizio = TipoVisura::find($servizio);
        $isCatastale = $this->isCatastaleType($tipoServizio);
        $catastoData = [];
        if ($isCatastale) {
            [$catastoData, $noteLibera] = $this->extractCatastoDataFromNote((string) $record->note);
            $record->note = $noteLibera;
        }

        return view('Backend.Visura.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuova '.$tipoServizio->nome,
            'controller' => get_class($this),
            'breadcrumbs' => [action([VisuraController::class, 'index']) => 'Torna a elenco '.Visura::NOME_PLURALE],
            'tipoServizio' => $tipoServizio,
            'isCatastale' => $isCatastale,
            'catastoData' => $catastoData,
        ]);
    }

    public function ricercaAzienda(Request $request)
    {
        if ($request->input('provincia_id') === '') {
            $request->merge(['provincia_id' => null]);
        }

        $request->validate([
            'denominazione' => ['required', 'string', 'max:255'],
            'provincia_id' => ['nullable', 'string', 'max:10'],
        ]);

        [$rawItems, $lastMessage] = $this->eseguiRicercaAziendaConFallback(
            (string) $request->input('denominazione'),
            $request->input('provincia_id')
        );

        if (empty($rawItems)) {
            return [
                'success' => false,
                'message' => $lastMessage ?: 'Nessun risultato trovato. Prova con una denominazione più breve.',
            ];
        }

        $checkVies = $request->boolean('check_vies');
        $viesCache = [];
        $items = collect($rawItems)
            ->map(function ($item) use (&$viesCache, $checkVies) {
                $arr = json_decode(json_encode($item), true) ?: [];
                $denominazione = $this->extractDenominazioneFromAziendaResult($arr);
                $comune = (string) ($arr['comune'] ?? $arr['sede_legale_comune'] ?? $arr['sede_comune'] ?? '');
                $indirizzo = (string) ($arr['indirizzo'] ?? $arr['sede_legale_indirizzo'] ?? $arr['sede_indirizzo'] ?? '');
                $natura = (string) ($arr['codice_natura_giuridica'] ?? $arr['natura_giuridica'] ?? $arr['forma_giuridica'] ?? '');
                $partitaIva = $this->extractPartitaIvaFromAziendaResult($arr);
                $viesValid = null;
                if ($checkVies && $partitaIva !== '') {
                    if (! array_key_exists($partitaIva, $viesCache)) {
                        $viesCache[$partitaIva] = $this->verifyPartitaIvaViaVies($partitaIva);
                    }
                    $viesValid = $viesCache[$partitaIva];
                }

                return [
                    'denominazione' => $denominazione,
                    'partita_iva' => $partitaIva,
                    'vies_valid' => $viesValid,
                    'indirizzo' => $indirizzo,
                    'comune' => $comune,
                    'natura_giuridica' => $natura,
                    'raw' => $arr,
                ];
            })
            ->filter(function ($row) {
                return $row['denominazione'] !== '' || $row['comune'] !== '' || $row['indirizzo'] !== '';
            })
            ->values();

        return [
            'success' => true,
            'count' => $items->count(),
            'items' => $items,
        ];
    }

    public function ricercaAziendaDettaglio(Request $request)
    {
        if ($request->input('provincia_id') === '') {
            $request->merge(['provincia_id' => null]);
        }

        $request->validate([
            'denominazione' => ['required', 'string', 'max:255'],
            'provincia_id' => ['nullable', 'string', 'max:10'],
            'raw' => ['nullable', 'string'],
        ]);

        $raw = [];
        if ($request->filled('raw')) {
            $decoded = json_decode((string) $request->input('raw'), true);
            if (is_array($decoded)) {
                $raw = $decoded;
            }
        }

        $pivaFromRaw = $this->extractPartitaIvaFromAziendaResult($raw);
        if ($pivaFromRaw !== '') {
            return [
                'success' => true,
                'partita_iva' => $pivaFromRaw,
            ];
        }

        [$rawItems, $lastMessage] = $this->eseguiRicercaAziendaConFallback(
            (string) $request->input('denominazione'),
            $request->input('provincia_id')
        );

        if (empty($rawItems)) {
            return [
                'success' => false,
                'message' => $lastMessage ?: 'Nessun dettaglio disponibile per la denominazione indicata.',
            ];
        }

        $needle = $this->normalizeAziendaStringForMatch((string) $request->input('denominazione'));
        $bestPiva = '';
        foreach ($rawItems as $item) {
            $arr = json_decode(json_encode($item), true) ?: [];
            $piva = $this->extractPartitaIvaFromAziendaResult($arr);
            if ($piva === '') {
                continue;
            }

            $den = $this->normalizeAziendaStringForMatch($this->extractDenominazioneFromAziendaResult($arr));
            if ($needle !== '' && $den === $needle) {
                return [
                    'success' => true,
                    'partita_iva' => $piva,
                ];
            }

            if ($bestPiva === '') {
                $bestPiva = $piva;
            }
        }

        if ($bestPiva !== '') {
            return [
                'success' => true,
                'partita_iva' => $bestPiva,
            ];
        }

        return [
            'success' => false,
            'message' => 'Partita IVA non disponibile per il risultato selezionato.',
        ];
    }

    protected function eseguiRicercaAziendaConFallback(string $denominazioneInput, $provinciaId): array
    {
        $service = new VisuraCameraleService;
        $results = [];
        $lastMessage = null;
        $denominazioni = $this->denominazioniRicercaAzienda($denominazioneInput);
        $province = [];
        if ($provinciaId) {
            $province[] = $provinciaId;
        }
        $province[] = null;

        foreach ($denominazioni as $denominazione) {
            foreach ($province as $provincia) {
                $legacyRequest = new Request([
                    'ragione_sociale' => $denominazione,
                    'provincia' => $provincia,
                ]);

                $response = $service->impresa($legacyRequest);
                if (! $response || ! isset($response->data)) {
                    $lastMessage = $service->message ?: $lastMessage;

                    continue;
                }

                $dataItems = $this->normalizeAziendaSearchDataItems($response->data);
                if (empty($dataItems)) {
                    $lastMessage = $service->message ?: $lastMessage;

                    continue;
                }

                foreach ($dataItems as $item) {
                    $row = json_decode(json_encode($item), true) ?: [];
                    $denominazione = $this->extractDenominazioneFromAziendaResult($row);
                    $comune = trim((string) ($row['comune'] ?? $row['sede_legale_comune'] ?? $row['sede_comune'] ?? ''));
                    $fallbackKey = md5(json_encode($row));
                    $key = md5(strtolower(
                        trim((string) $denominazione)
                        .'|'
                        .$comune
                    ));
                    if (trim((string) $denominazione) === '' && $comune === '') {
                        $key = $fallbackKey;
                    }
                    $results[$key] = $item;
                }

                if (! empty($results)) {
                    return [array_values($results), $lastMessage];
                }
            }
        }

        return [[], $lastMessage];
    }

    protected function normalizeAziendaSearchDataItems($data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if ($data instanceof \Traversable) {
            return iterator_to_array($data, false);
        }

        if (is_object($data)) {
            $arr = json_decode(json_encode($data), true);
            if (! is_array($arr)) {
                return [];
            }

            // Alcune risposte arrivano come oggetto stdClass con chiavi numeriche.
            // In questo caso è una lista risultati a tutti gli effetti.
            if (array_is_list($arr) || $this->isNumericKeyedArray($arr)) {
                return array_values($arr);
            }

            // Se è già un singolo record azienda, restituiscilo come lista a 1 elemento.
            $hasAziendaFields = isset($arr['denominazione']) || isset($arr['ragione_sociale']) || isset($arr['partita_iva']) || isset($arr['piva']);
            if ($hasAziendaFields) {
                return [$arr];
            }

            // Se è una struttura contenitore, prova i nodi più comuni.
            foreach (['items', 'records', 'risultati', 'result'] as $node) {
                if (isset($arr[$node]) && is_array($arr[$node])) {
                    return $arr[$node];
                }
            }
        }

        return [];
    }

    protected function isNumericKeyedArray(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        foreach (array_keys($arr) as $key) {
            if (! (is_int($key) || (is_string($key) && ctype_digit($key)))) {
                return false;
            }
        }

        return true;
    }

    protected function denominazioniRicercaAzienda(string $input): array
    {
        $original = trim($input);
        $original = preg_replace('/\s+/', ' ', $original) ?: $original;

        $clean = strtoupper($original);
        $alnum = preg_replace('/[^A-Z0-9\s]/', ' ', $clean) ?: $clean;
        $alnum = preg_replace('/\s+/', ' ', trim($alnum)) ?: $alnum;

        $variants = [$original, $clean, $alnum];
        $words = array_values(array_filter(explode(' ', $alnum)));
        if (count($words) > 3) {
            $variants[] = implode(' ', array_slice($words, 0, 3));
        }
        if (count($words) > 2) {
            $variants[] = implode(' ', array_slice($words, 0, 2));
        }

        return array_values(array_unique(array_filter($variants)));
    }

    protected function extractDenominazioneFromAziendaResult(array $arr): string
    {
        foreach ([
            'denominazione',
            'ragione_sociale',
            'nome',
            'descrizione',
            'company_name',
            'business_name',
            'anagrafica_denominazione',
            'impresa_denominazione',
        ] as $key) {
            $value = trim((string) ($arr[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function normalizeAziendaStringForMatch(string $value): string
    {
        $upper = strtoupper(trim($value));
        $upper = preg_replace('/[^A-Z0-9]+/', ' ', $upper) ?: '';

        return trim(preg_replace('/\s+/', ' ', $upper) ?: '');
    }

    protected function extractPartitaIvaFromAziendaResult(array $data): string
    {
        $flat = $this->flattenArrayForLookup($data);

        $candidateKeys = [
            'partita_iva',
            'partitaiva',
            'piva',
            'cf_piva_id',
            'cfpivaid',
            'cf_piva',
            'cfpiva',
            'codice_fiscale',
            'codicefiscale',
            'cf',
            'vat',
            'vat_number',
            'vatnumber',
        ];

        foreach ($candidateKeys as $key) {
            if (! array_key_exists($key, $flat)) {
                continue;
            }
            $normalized = preg_replace('/\D+/', '', (string) $flat[$key]);
            if (is_string($normalized) && strlen($normalized) === 11) {
                return $normalized;
            }
        }

        foreach ($flat as $key => $value) {
            if (
                ! str_contains($key, 'partita')
                && ! str_contains($key, 'piva')
                && ! str_contains($key, 'vat')
                && ! str_contains($key, 'cfpiva')
                && ! str_contains($key, 'codicefiscale')
                && $key !== 'cf'
            ) {
                continue;
            }
            $normalized = preg_replace('/\D+/', '', (string) $value);
            if (is_string($normalized) && strlen($normalized) === 11) {
                return $normalized;
            }
        }

        return '';
    }

    protected function flattenArrayForLookup(array $data, string $prefix = ''): array
    {
        $flat = [];
        foreach ($data as $key => $value) {
            $normalizedKey = preg_replace('/[^a-z0-9_]+/', '', strtolower((string) $key));
            $composed = trim($prefix.'_'.$normalizedKey, '_');

            if (is_array($value)) {
                $flat = array_merge($flat, $this->flattenArrayForLookup($value, $composed));

                continue;
            }

            if (is_scalar($value) || $value === null) {
                $flat[$composed] = (string) $value;
                $flat[$normalizedKey] = (string) $value;
            }
        }

        return $flat;
    }

    protected function verifyPartitaIvaViaVies(string $partitaIva): ?bool
    {
        if (! preg_match('/^\d{11}$/', $partitaIva)) {
            return null;
        }

        try {
            $client = new SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', [
                'connection_timeout' => 8,
                'cache_wsdl' => WSDL_CACHE_BOTH,
            ]);
            $res = $client->checkVat([
                'countryCode' => 'IT',
                'vatNumber' => $partitaIva,
            ]);

            return (bool) ($res->valid ?? false);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        $servizio = $request->input('tipo_visura_id');
        $fallbackBackoffice = $request->boolean('fallback_backoffice');

        $request->validate($this->rules(null, $request));

        DB::beginTransaction();

        try {
            $tipoServizio = TipoVisura::find($servizio);
            if (! $tipoServizio) {
                throw new \RuntimeException('Tipo visura non trovato');
            }

            $agente = User::query()->with('agente')->find((int) $request->input('agente_id'));
            if (! $agente || ! $agente->agente) {
                throw new \RuntimeException('Agente non trovato per questa visura');
            }
            $prezzo = (float) $tipoServizio->prezzo_agente;
            if ($fallbackBackoffice) {
                $saldoServizi = (float) ($agente->agente->portafoglio_servizi ?? 0);
                if ($saldoServizi < $prezzo) {
                    throw new \RuntimeException('Credito portafoglio servizi insufficiente per invio al backoffice');
                }
            } else {
                $saldoVisure = (float) ($agente->agente->portafoglio_visure ?? 0);
                if ($saldoVisure < $prezzo) {
                    throw new \RuntimeException('Credito portafoglio visure insufficiente');
                }
            }

            $record = new Visura;
            $record->esito_id = 'in-gestione';
            $record->prezzo_pratica = $prezzo;
            $record->tipo_visura_id = $tipoServizio->id;
            $record->caricato_da_user_id = Auth::id();
            if ($tipoServizio->tipo_visura == 'azienda') {
                $this->salvaDatiAzienda($record, $request);
            } else {
                $this->salvaDatiPrivato($record, $request);
            }

            if ($fallbackBackoffice) {
                $record->openapi_stato_richiesta = 'backoffice';
                $record->openapi_response = [
                    'status' => 'backoffice',
                    'message' => 'Pratica inviata al backoffice su richiesta agente',
                ];
                $record->openapi_last_sync_at = now();
                $record->save();
            } else {
                try {
                    $this->avviaRichiestaOpenApi($record, $tipoServizio);
                    $this->processaOpenApiAutomatico($record);
                } catch (\Throwable $openApiException) {
                    if ($this->isOpenApiCreditoTokenIssue($openApiException->getMessage())) {
                        throw new \RuntimeException('__OPENAPI_CREDITO_BLOCCO__'.$openApiException->getMessage());
                    }
                    throw $openApiException;
                }
            }

            $movimento = new MovimentoPortafoglio;
            $movimento->agente_id = (int) $agente->id;
            $movimento->importo = -$tipoServizio->prezzo_agente;
            $movimento->descrizione = ($fallbackBackoffice ? 'Visura backoffice ' : 'Visura ')
                .$tipoServizio->nome.' per '.($record->partita_iva ?? $record->codice_fiscale);
            $movimento->prodotto_id = $record->id;
            $movimento->prodotto_type = get_class($record);
            $movimento->portafoglio = $fallbackBackoffice
                ? TipiPortafoglioEnum::SERVIZI->value
                : TipiPortafoglioEnum::VISURE->value;
            $movimento->save();

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            $message = $exception->getMessage();
            $openApiCreditoBloccato = str_starts_with($message, '__OPENAPI_CREDITO_BLOCCO__');
            if ($openApiCreditoBloccato) {
                $message = 'Il servizio automatico visure al momento non è disponibile. Puoi ricaricare il portafoglio visure oppure inviare la pratica al backoffice.';
            } else {
                $message = str_replace('__OPENAPI_CREDITO_BLOCCO__', '', $message);
            }

            Log::error('Errore creazione visura', ['error' => $message]);

            $response = redirect()->back()->withInput()->withErrors([
                'openapi' => $message,
            ]);

            if ($openApiCreditoBloccato) {
                $response->with('openapi_credito_bloccato', true);
            }

            return $response;
        }

        $alertMessage = new AlertMessage;
        $walletText = $fallbackBackoffice ? 'portafoglio servizi' : 'portafoglio visure';
        $azioneText = $fallbackBackoffice ? 'inviata al backoffice' : 'creata';
        $alertMessage
            ->messaggio('Ti è stato scalato l\'importo di '.importo($tipoServizio->prezzo_agente).' dal '.$walletText.' per la visura '.$tipoServizio->nome.' '.$azioneText, 'primary')
            ->titolo('Portafoglio aggiornato', 'primary')
            ->flash();

        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return mixed
     */
    public function show($id)
    {
        $record = Visura::with(['tipo:id,nome', 'agente:id,nome,cognome,alias', 'esito', 'allegati', 'allegatiPerCliente'])->find($id);
        abort_if(! $record, 404, 'Questa visura non esiste');

        return view('Backend.Visura.show', [
            'record' => $record,
            'controller' => VisuraController::class,
            'titoloPagina' => Visura::NOME_SINGOLARE,
            'breadcrumbs' => [action([VisuraController::class, 'index']) => 'Torna a elenco '.Visura::NOME_PLURALE],

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return mixed
     */
    public function edit($id)
    {
        $record = Visura::with(['tipo', 'agente:id,nome,cognome,alias', 'esito', 'allegati', 'allegatiPerCliente'])->find($id);
        abort_if(! $record, 404, 'Questa visura non esiste');
        $eliminabile = true;

        $isCatastale = $this->isCatastaleType($record->tipo);
        $catastoData = [];
        if ($isCatastale) {
            [$catastoData, $noteLibera] = $this->extractCatastoDataFromNote((string) $record->note);
            $record->note = $noteLibera;
        }

        return view('Backend.Visura.edit', [
            'record' => $record,
            'controller' => VisuraController::class,
            'titoloPagina' => 'Modifica '.$record->tipo->nome,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([VisuraController::class, 'index']) => 'Torna a elenco '.Visura::NOME_PLURALE],
            'tipoServizio' => $record->tipo,
            'isCatastale' => $isCatastale,
            'catastoData' => $catastoData,

        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $record = Visura::find($id);
        abort_if(! $record, 404, 'Questa '.Visura::NOME_SINGOLARE.' non esiste');
        $request->validate($this->rules($id, $request));
        if ($record->tipo->tipo_visura == 'azienda') {
            $this->salvaDatiAzienda($record, $request);
        } else {
            $this->salvaDatiPrivato($record, $request);
        }

        return $this->backToIndex();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return mixed
     */
    public function destroy($id)
    {
        $record = Visura::find($id);
        abort_if(! $record, 404, 'Questa visura non esiste');

        $record->delete();

        return [
            'success' => true,
            'redirect' => action([VisuraController::class, 'index']),
        ];
    }

    public function uploadAllegato(Request $request)
    {
        $file = new AllegatoVisura;

        if ($request->file('file')) {
            $cartella = config('configurazione.allegati_visure.cartella');
            $stored = app(\App\Http\Services\SensitiveFileService::class)->store($request->file('file'), $cartella, [
                'area' => 'visure',
                'visura_id' => $request->input('visura_id'),
                'per_cliente' => $request->input('per_cliente', 0),
            ]);
            $file->path_filename = $stored['path'];
            $file->filename_originale = $stored['original_name'];
            $file->mime_type = $stored['mime_type'];
            $file->file_contenuto_base64 = $stored['base64'];
            $file->uid = $request->input('uid');
            $file->dimensione_file = $stored['size'];
            $file->visura_id = $request->input('visura_id');
            $file->per_cliente = $request->input('per_cliente', 0);
            $file->save();

            return response()->json(['success' => true, 'id' => $file->id, 'filename' => $stored['filename'], 'thumbnail' => $file->urlThumbnail()]);

        }
        abort(404, 'File non presente');

    }

    public function deleteAllegato(Request $request)
    {
        $record = AllegatoVisura::find($request->input('id'));
        abort_if(! $record, 404, 'File non trovato');
        Log::debug(__FUNCTION__, $record->toArray());

        Log::debug('elimino allegato cliente'.$record->path_filename);
        $record->delete();

        return $record->path_filename;
    }

    public function aggiornaStato(Request $request, $id)
    {
        $visura = Visura::withCount('allegati')->withCount('allegatiPerCliente')->find($id);
        abort_if(! $visura, 404, 'Questo servizio non esiste');

        $esitoPrima = $visura->esito_id;

        $esito = EsitoVisura::find($request->input('esito_id'));
        $motivoKoprima = $visura->motivo_ko;
        $visura->esito_id = $esito->id;
        $visura->esito_finale = $esito->esito_finale;
        $visura->pagato = getInputCheckbox($request->input('pagato'));
        $visura->motivo_ko = getInputToUpper(Str::limit($request->input('motivo_ko'), 254));

        $visura->save();

        if ($visura->wasChanged('motivo_ko') && $motivoKoprima == null) {
            if ($visura->motivo_ko && strlen($visura->motivo_ko) < 70) {

                $tab = TabMotivoKo::firstOrNew(['nome' => $visura->motivo_ko, 'tipo' => 'caf-patronato']);
                if ($tab->conteggio) {
                    $tab->conteggio = $tab->conteggio + 1;
                } else {
                    $tab->conteggio = 1;
                }
                $tab->save();
            }
        }
        $records = collect([$visura]);

        if ($visura->wasChanged(['esito_id'])) {
            $esito = EsitoVisura::find($visura->esito_id);
            if ($esito->notifica_mail) {
                dispatch(function () use ($visura) {
                    /** @var User $agente */
                    $agente = $visura->agente;
                    if ($agente->hasPermissionTo('agente')) {
                        $agente->notify(new NotificaVisuraCambioEsitoAdAgente($visura));
                    }

                })->afterResponse();

            }
            if ($this->currentUser()->hasPermissionTo('supervisore')) {
                Notifica::notificaAdAdmin('Cambio esito pratica', 'Esito per la pratica '.$visura->nominativo().' modificato a '.$esito->nome);
            }

        }

        if ($request->input('aggiorna') == 'dash') {
            $view = 'Backend.Dashboard.admin.servizi';
        } else {
            $view = 'Backend.Visura.tbody';
        }

        return ['success' => true, 'id' => $id,
            'html' => base64_encode(view($view, [
                'records' => $records,
                'controller' => VisuraController::class,
                'puoModificare' => $this->puoModificare(),
                'puoModificareEsito' => $this->puoModificareEsito(),

            ])->render()),
        ];
    }

    /**
     * @param  Visura  $model
     * @param  Request  $request
     * @return mixed
     */
    protected function salvaDatiAzienda($model, $request)
    {

        $nuovo = ! $model->id;

        if ($nuovo) {

        }

        // Ciclo su campi
        $campi = [
            'data' => 'app\getInputData',
            'agente_id' => '',
            'partita_iva' => '',
            'ragione_sociale' => 'app\getInputUcwords',
            'citta' => '',
            'note' => '',
            'uid' => '',
            'pagato' => 'app\getInputCheckbox',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        if ($this->isCatastaleRequest($request)) {
            $tipo = TipoVisura::find($request->input('tipo_visura_id'));
            $model->note = $this->buildCatastoNotePayload($request, $tipo);
        }

        $model->save();

        return $model;
    }

    /**
     * @param  Visura  $model
     * @param  Request  $request
     * @return mixed
     */
    protected function salvaDatiPrivato($model, $request)
    {

        $nuovo = ! $model->id;

        if ($nuovo) {

        }

        // Ciclo su campi
        $campi = [
            'data' => 'app\getInputData',
            'agente_id' => '',
            'note' => '',
            'uid' => '',
            'codice_fiscale' => 'strtoupper',
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'cellulare' => '',
            'indirizzo' => 'app\getInputUcwords',
            'citta' => '',
            'cap' => '',

        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        if ($this->isCatastaleRequest($request)) {
            $tipo = TipoVisura::find($request->input('tipo_visura_id'));
            $model->note = $this->buildCatastoNotePayload($request, $tipo);
        }

        $model->save();

        return $model;
    }

    public function richiediOpenApi(int $id)
    {
        $record = Visura::with('tipo')->find($id);
        abort_if(! $record, 404, 'Questa visura non esiste');

        if ($record->openapi_request_id) {
            return ['success' => false, 'message' => 'Richiesta automatica già presente'];
        }

        try {
            $this->avviaRichiestaOpenApi($record, $record->tipo);
        } catch (\Throwable $exception) {
            return ['success' => false, 'message' => $this->userFacingOpenApiMessage($exception->getMessage(), 'Impossibile avviare la richiesta automatica')];
        }

        return ['success' => true, 'message' => 'Richiesta automatica inviata'];
    }

    public function sincronizzaOpenApi(int $id)
    {
        $record = Visura::with('tipo')->find($id);
        abort_if(! $record, 404, 'Questa visura non esiste');
        abort_if(! $record->openapi_request_id, 422, 'Richiesta automatica non presente');

        $provider = $this->resolveOpenApiProvider($record);
        $service = $provider === 'catasto'
            ? $this->buildCatastoServiceForRecord($record)
            : $this->buildVisureServiceForRecord($record);
        $statusData = $provider === 'catasto'
            ? $service->statoVisuraCatastale($record->openapi_request_id)
            : $service->statoRichiesta($record->openapi_request_id);
        if (! $statusData) {
            return ['success' => false, 'message' => $this->userFacingOpenApiMessage($service->message, 'Errore sincronizzazione richiesta automatica')];
        }

        $this->salvaStatoOpenApi($record, $statusData);

        return ['success' => true, 'message' => 'Stato aggiornato', 'stato' => $record->openapi_stato_richiesta];
    }

    public function scaricaDocumentoOpenApi(int $id)
    {
        $record = Visura::with('tipo')->find($id);
        abort_if(! $record, 404, 'Questa visura non esiste');
        abort_if(! $record->openapi_request_id, 422, 'Richiesta automatica non presente');

        $provider = $this->resolveOpenApiProvider($record);
        $service = $provider === 'catasto'
            ? $this->buildCatastoServiceForRecord($record)
            : $this->buildVisureServiceForRecord($record);
        $document = $provider === 'catasto'
            ? $service->scaricaDocumentoVisuraCatastale($record->openapi_request_id)
            : $service->scaricaDocumento($record->openapi_request_id);
        if (! $document) {
            return redirect()->back()->withErrors([
                'openapi' => $this->userFacingOpenApiMessage($service->message, 'Documento non disponibile'),
            ]);
        }

        $saved = $this->salvaDocumentoOpenApiSuAllegati($record, $document);
        if (! $saved) {
            return redirect()->back()->withErrors([
                'openapi' => 'Documento non disponibile',
            ]);
        }

        $content = $saved['content'];
        $fileName = $saved['fileName'];
        $mimeType = $saved['mimeType'];

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $fileName, ['Content-Type' => $mimeType]);
    }

    protected function avviaRichiestaOpenApi(Visura $record, TipoVisura $tipoServizio): void
    {
        if (! Schema::hasColumn('visure', 'openapi_hash_visura')) {
            return;
        }

        $hash = trim((string) $tipoServizio->openapi_hash_visura);
        if ($hash !== '') {
            $service = $this->buildVisureServiceForRecord($record);
            $responseData = $service->creaRichiesta($hash, $this->payloadOpenApi($record));
            if (! $responseData) {
                throw new \RuntimeException($this->userFacingOpenApiMessage($service->message, 'Impossibile creare la richiesta automatica'));
            }

            $record->openapi_hash_visura = $hash;
            $record->openapi_request_id = (string) ($responseData['request_id'] ?? $responseData['id'] ?? '');
            if ($record->openapi_request_id === '') {
                throw new \RuntimeException('Impossibile completare la richiesta automatica');
            }
            $record->openapi_stato_richiesta = (string) ($responseData['stato_richiesta'] ?? $responseData['status'] ?? 'in_lavorazione');
            $record->openapi_response = $responseData;
            $record->openapi_last_sync_at = now();
            $record->save();

            return;
        }

        if ($this->isCatastaleType($tipoServizio)) {
            $service = $this->buildCatastoServiceForRecord($record);
            $responseData = $service->creaVisuraCatastale($this->payloadCatasto($record, $tipoServizio));
            if (! $responseData) {
                throw new \RuntimeException($this->userFacingOpenApiMessage($service->message, 'Impossibile creare la richiesta automatica'));
            }

            $record->openapi_hash_visura = 'catasto:visura_catastale';
            $record->openapi_request_id = (string) ($responseData['id'] ?? $responseData['request_id'] ?? '');
            if ($record->openapi_request_id === '') {
                throw new \RuntimeException('Impossibile completare la richiesta automatica');
            }
            $record->openapi_stato_richiesta = (string) ($responseData['stato_richiesta'] ?? $responseData['status'] ?? 'in_lavorazione');
            $record->openapi_response = $responseData;
            $record->openapi_last_sync_at = now();
            $record->save();
        }
    }

    protected function salvaStatoOpenApi(Visura $record, array $statusData): void
    {
        if (! Schema::hasColumn('visure', 'openapi_hash_visura')) {
            return;
        }

        $record->openapi_stato_richiesta = (string) ($statusData['stato_richiesta'] ?? $statusData['status'] ?? $record->openapi_stato_richiesta);
        $record->openapi_response = $statusData;
        $record->openapi_last_sync_at = now();
        $record->save();
    }

    protected function payloadOpenApi(Visura $record): array
    {
        return array_filter([
            'cf_piva_id' => $record->partita_iva ?: $record->codice_fiscale,
            'partita_iva' => $record->partita_iva,
            'codice_fiscale' => $record->codice_fiscale,
            'ragione_sociale' => $record->ragione_sociale,
            'nome' => $record->nome,
            'cognome' => $record->cognome,
            'email' => $record->email,
            'cellulare' => $record->cellulare,
            'indirizzo' => $record->indirizzo,
            'cap' => $record->cap,
            'citta' => $record->citta,
            'note' => $record->note,
            'internal_id' => $record->id,
            'internal_uid' => $record->uid,
        ], function ($value) {
            return ! is_null($value) && $value !== '';
        });
    }

    protected function processaOpenApiAutomatico(Visura $record): void
    {
        if (! $record->openapi_request_id) {
            return;
        }

        try {
            $provider = $this->resolveOpenApiProvider($record);
            $service = $provider === 'catasto'
                ? $this->buildCatastoServiceForRecord($record)
                : $this->buildVisureServiceForRecord($record);
            $statusData = $provider === 'catasto'
                ? $service->statoVisuraCatastale($record->openapi_request_id)
                : $service->statoRichiesta($record->openapi_request_id);
            if ($statusData) {
                $this->salvaStatoOpenApi($record, $statusData);
            }

            $stato = strtolower((string) ($record->openapi_stato_richiesta ?? ''));
            $isReady = $stato === ''
                || str_contains($stato, 'evas')
                || str_contains($stato, 'complet')
                || str_contains($stato, 'chius')
                || str_contains($stato, 'ready')
                || str_contains($stato, 'dispon');

            if (! $isReady) {
                return;
            }

            $document = $provider === 'catasto'
                ? $service->scaricaDocumentoVisuraCatastale($record->openapi_request_id)
                : $service->scaricaDocumento($record->openapi_request_id);
            if (! $document) {
                return;
            }

            $this->salvaDocumentoOpenApiSuAllegati($record, $document);
        } catch (\Throwable $exception) {
            Log::warning('Processo OpenAPI automatico non completato', [
                'visura_id' => $record->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function salvaDocumentoOpenApiSuAllegati(Visura $record, array $document): ?array
    {
        $content = $this->estraiContenutoDocumento($document);
        if ($content === null) {
            return null;
        }

        $fileName = (string) ($document['nome'] ?? ('visura_'.$record->id.'.pdf'));
        $mimeType = (string) ($document['mime_type'] ?? $document['content_type'] ?? 'application/octet-stream');

        $path = ltrim(config('configurazione.allegati_visure.cartella'), '/')
            .'/'.Str::ulid().'-'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        Storage::put($path, $content);

        $allegato = new AllegatoServizio;
        $allegato->uid = $record->uid;
        $allegato->filename_originale = $fileName;
        $allegato->path_filename = $path;
        $allegato->dimensione_file = strlen($content);
        $allegato->mime_type = $mimeType;
        $allegato->file_contenuto_base64 = base64_encode($content);
        $allegato->per_cliente = 0;
        $allegato->allegato_id = $record->id;
        $allegato->allegato_type = Visura::class;
        $allegato->save();
        VisuraAttachmentMailer::notifyCliente($record, $allegato);

        if (Schema::hasColumn('visure', 'openapi_documento_nome')) {
            $record->openapi_documento_nome = $fileName;
            $record->openapi_documento_mime = $mimeType;
            $record->openapi_documento_dimensione = strlen($content);
            $record->openapi_documento_scaricato_at = now();
            $record->openapi_last_sync_at = now();
            $record->save();
        }

        return [
            'content' => $content,
            'fileName' => $fileName,
            'mimeType' => $mimeType,
        ];
    }

    protected function estraiContenutoDocumento(array $document): ?string
    {
        if (isset($document['raw_content']) && is_string($document['raw_content']) && $document['raw_content'] !== '') {
            return $document['raw_content'];
        }

        $base64 = (string) ($document['file'] ?? '');
        if ($base64 === '') {
            return null;
        }

        $decoded = base64_decode($base64, true);

        return $decoded === false ? $base64 : $decoded;
    }

    protected function resolveOpenApiProvider(Visura $record): string
    {
        $hash = strtolower((string) $record->openapi_hash_visura);
        if (str_starts_with($hash, 'catasto:')) {
            return 'catasto';
        }
        if ($this->isCatastaleType($record->tipo)) {
            return 'catasto';
        }

        return 'visengine';
    }

    protected function isCatastaleType(?TipoVisura $tipo): bool
    {
        if (! $tipo) {
            return false;
        }

        return str_contains(strtolower((string) $tipo->nome), 'catast');
    }

    protected function payloadCatasto(Visura $record, TipoVisura $tipoServizio): array
    {
        $noteData = $this->parseStructuredNote((string) $record->note);
        $tipoNome = strtolower((string) $tipoServizio->nome);
        $isTerreni = str_contains($tipoNome, 'terren');
        $isSoggetto = (($noteData['entita'] ?? null) === 'soggetto') || str_contains($tipoNome, 'soggetto');
        $tipoVisura = str_contains($tipoNome, 'storic') ? 'storica' : 'ordinaria';
        $tipoCatasto = $isTerreni ? 'T' : 'F';
        $richiedente = trim((string) $this->currentUser()->nominativo());
        if ($richiedente === '') {
            $richiedente = trim((string) $this->currentUser()->email);
        }

        // Variante soggetto: id_soggetto (preferita) o cf_piva + provincia.
        if ($isSoggetto) {
            $payload = [
                'entita' => 'soggetto',
                'tipo_visura' => $tipoVisura,
                'tipo_catasto' => $tipoCatasto,
                'richiedente' => $richiedente,
                'id_soggetto' => $noteData['id_soggetto'] ?? null,
                'cf_piva' => $record->partita_iva ?: $record->codice_fiscale,
                'provincia' => $noteData['provincia'] ?? null,
            ];

            return array_filter($payload, function ($value) {
                return ! is_null($value) && $value !== '';
            });
        }

        // Variante immobile: id_immobile (preferita) o dati catastali.
        if (! empty($noteData['id_immobile'])) {
            $payload = [
                'entita' => 'immobile',
                'id_immobile' => $noteData['id_immobile'],
                'tipo_visura' => $tipoVisura,
                'richiedente' => $richiedente,
            ];

            return array_filter($payload, function ($value) {
                return ! is_null($value) && $value !== '';
            });
        }

        $payload = [
            'tipo_visura' => $tipoVisura,
            'entita' => 'immobile',
            'tipo_catasto' => $tipoCatasto,
            'richiedente' => $richiedente,
            'provincia' => $noteData['provincia'] ?? null,
            'comune' => $noteData['comune'] ?? $record->citta,
            'sezione' => $noteData['sezione'] ?? null,
            'sezione_urbana' => $noteData['sezione_urbana'] ?? null,
            'foglio' => $noteData['foglio'] ?? null,
            'particella' => $noteData['particella'] ?? null,
            'subalterno' => $noteData['subalterno'] ?? null,
            'raw_note' => $record->note,
        ];

        return array_filter($payload, function ($value) {
            return ! is_null($value) && $value !== '';
        });
    }

    protected function parseStructuredNote(string $note): array
    {
        $note = trim($note);
        if ($note === '') {
            return [];
        }

        $decoded = json_decode($note, true);
        if (is_array($decoded)) {
            return array_change_key_case($decoded, CASE_LOWER);
        }

        $data = [];
        foreach (preg_split('/\\r\\n|\\r|\\n/', $note) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode(':', $line, 2));
            if ($key !== '' && $value !== '') {
                $data[strtolower($key)] = $value;
            }
        }

        return $data;
    }

    protected function buildVisureServiceForRecord(Visura $record): OpenApiVisureService
    {
        $token = $this->getAgenteOpenApiToken($record, 'visure');
        if (! $token) {
            throw new \RuntimeException('Servizio automatico non disponibile per questo agente');
        }

        return new OpenApiVisureService($token);
    }

    protected function buildCatastoServiceForRecord(Visura $record): OpenApiCatastoService
    {
        $token = $this->getAgenteOpenApiToken($record, 'catasto');
        if (! $token) {
            throw new \RuntimeException('Servizio automatico non disponibile per questo agente');
        }

        return new OpenApiCatastoService($token);
    }

    protected function getAgenteOpenApiToken(Visura $record, string $tipo): ?string
    {
        $agente = User::query()->with('agente')->find($record->agente_id);
        if (! $agente || ! $agente->agente) {
            return null;
        }

        if ($tipo === 'catasto') {
            return $agente->agente->openapi_catasto_token ?: null;
        }

        return $agente->agente->openapi_visure_token ?: null;
    }

    protected function isOpenApiCreditoTokenIssue(string $message): bool
    {
        $message = strtolower(trim($message));
        if ($message === '') {
            return false;
        }

        $tokenMarkers = [
            'wrong token',
            'invalid token',
            'token non valido',
            'unauthorized',
            '(401)',
            ' 401',
        ];

        foreach ($tokenMarkers as $marker) {
            if (str_contains($message, $marker)) {
                return true;
            }
        }

        $creditoMarkers = [
            'credito insufficiente',
            'credito non sufficiente',
            'saldo insufficiente',
            'fondi insufficienti',
            'insufficient credit',
            'insufficient funds',
            '(402)',
            ' 402',
            'servizio automatico non disponibile per questo agente',
        ];

        foreach ($creditoMarkers as $marker) {
            if (str_contains($message, $marker)) {
                return true;
            }
        }

        return false;
    }

    protected function userFacingOpenApiMessage(?string $message, string $fallback): string
    {
        $message = trim((string) $message);
        if ($message === '') {
            return $fallback;
        }

        if ($this->isOpenApiCreditoTokenIssue($message)) {
            return 'Il servizio automatico visure al momento non è disponibile. Puoi ricaricare il portafoglio visure oppure inviare la pratica al backoffice.';
        }

        return 'Il servizio automatico visure al momento non è disponibile. Riprova più tardi o invia la pratica al backoffice.';
    }

    protected function extractCatastoDataFromNote(string $note): array
    {
        $data = $this->parseStructuredNote($note);
        $noteLibera = (string) ($data['note_libera'] ?? (! str_starts_with(trim($note), '{') ? $note : ''));

        return [$data, $noteLibera];
    }

    protected function isCatastaleRequest(Request $request): bool
    {
        $tipo = TipoVisura::find($request->input('tipo_visura_id'));

        return $this->isCatastaleType($tipo);
    }

    protected function defaultCatastoEntita(?TipoVisura $tipo): string
    {
        $nome = strtolower((string) optional($tipo)->nome);

        return str_contains($nome, 'soggetto') ? 'soggetto' : 'immobile';
    }

    protected function buildCatastoNotePayload(Request $request, ?TipoVisura $tipo): string
    {
        $entita = (string) ($request->input('catasto_entita') ?: $this->defaultCatastoEntita($tipo));
        $tipoNome = strtolower((string) optional($tipo)->nome);
        $tipoVisura = str_contains($tipoNome, 'storic') ? 'storica' : 'ordinaria';
        $tipoCatasto = str_contains($tipoNome, 'terren') ? 'T' : 'F';

        $payload = [
            'provider' => 'catasto',
            'note_libera' => (string) $request->input('note', ''),
            'entita' => $entita,
            'tipo_visura' => $tipoVisura,
            'tipo_catasto' => $tipoCatasto,
            'provincia' => strtoupper((string) $request->input('provincia_catasto')),
            'comune' => (string) $request->input('comune_catasto'),
            'sezione' => (string) $request->input('sezione_catasto'),
            'sezione_urbana' => (string) $request->input('sezione_urbana_catasto'),
            'foglio' => (string) $request->input('foglio_catasto'),
            'particella' => (string) $request->input('particella_catasto'),
            'subalterno' => (string) $request->input('subalterno_catasto'),
            'id_immobile' => (string) $request->input('id_immobile_catasto'),
            'id_soggetto' => (string) $request->input('id_soggetto_catasto'),
        ];
        $payload = array_filter($payload, function ($v) {
            return $v !== '';
        });

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    protected function backToIndex()
    {
        return redirect()->action([get_class($this), 'index']);
    }

    /** Query per index
     * @return array
     */
    protected function queryBuilderIndexSemplice()
    {
        return Visura::get();
    }

    protected function rules($id = null, ?Request $request = null)
    {

        $rules = [
            'data' => ['required'],
            'agente_id' => ['required'],
            'partita_iva' => ['nullable', new PartitaIvaRule],
            'ragione_sociale' => ['nullable', 'max:255'],
            'citta' => ['nullable', 'max:255'],
            'note' => ['nullable'],
            'uid' => ['required', 'max:255'],
            'esito_finale' => ['nullable', 'max:255'],
            'mese_pagamento' => ['nullable'],
            'motivo_ko' => ['nullable', 'max:255'],
            'pagato' => ['nullable'],
        ];

        if ($request && $this->isCatastaleRequest($request)) {
            $entita = $request->input('catasto_entita');
            if (! $entita) {
                $tipo = TipoVisura::find($request->input('tipo_visura_id'));
                $entita = $this->defaultCatastoEntita($tipo);
            }

            $rules = array_merge($rules, [
                'provincia_catasto' => ['required', 'string', 'max:80'],
                'comune_catasto' => ['required', 'string', 'max:120'],
                'catasto_entita' => ['nullable', 'in:immobile,soggetto'],
                'foglio_catasto' => [$entita === 'immobile' ? 'required' : 'nullable', 'string', 'max:20'],
                'particella_catasto' => [$entita === 'immobile' ? 'required' : 'nullable', 'string', 'max:20'],
                'subalterno_catasto' => ['nullable', 'string', 'max:20'],
                'sezione_catasto' => ['nullable', 'string', 'max:20'],
                'sezione_urbana_catasto' => ['nullable', 'string', 'max:20'],
                'id_immobile_catasto' => ['nullable', 'string', 'max:60'],
                'id_soggetto_catasto' => ['nullable', 'string', 'max:60'],
            ]);
        }

        return $rules;
    }

    protected function puoModificareEsito()
    {
        return $this->currentUser()->hasAnyPermission(['admin', 'supervisore']);
    }

    protected function puoModificare()
    {
        return ! $this->currentUser()->hasPermissionTo('supervisore');
    }
}
