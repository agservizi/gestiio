<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Services\InpostService;
use App\Models\SpedizioneInpost;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use function App\getInputNumero;

class SpedizioneInpostController extends Controller
{
    protected bool $conFiltro = false;

    public function index(Request $request)
    {
        $records = $this->applicaFiltri($request)->paginate(config('configurazione.paginazione'));
        $records->appends($request->query());
        $this->aggiornaTrackingTabella($records);

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.SpedizioneInpost.tabella', [
                    'records' => $records,
                    'controller' => get_class($this),
                ])->render()),
            ];
        }

        return view('Backend.SpedizioneInpost.index', [
            'records' => $records,
            'controller' => get_class($this),
            'titoloPagina' => 'Elenco ' . SpedizioneInpost::NOME_PLURALE,
            'testoNuovo' => 'Nuova ' . SpedizioneInpost::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in destinatario, mittente, tracking',
        ]);
    }

    public function create()
    {
        $record = new SpedizioneInpost();
        $record->nazione_destinazione = 'IT';
        $record->delivery_type = 'point';

        $user = Auth::user();
        if ($user instanceof User && $user->hasPermissionTo('agente')) {
            $record->agente_id = $user->id;
        }

        return view('Backend.SpedizioneInpost.edit', [
            'record' => $record,
            'controller' => get_class($this),
            'titoloPagina' => 'Nuova spedizione InPost',
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco ' . SpedizioneInpost::NOME_PLURALE],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $record = new SpedizioneInpost();
        $this->salvaDati($record, $request);
        $this->creaSpedizioneRemota($record);

        return $this->backToIndex();
    }

    public function show($id)
    {
        $record = SpedizioneInpost::with(['chiamate' => fn ($q) => $q->latest()])->find($id);
        abort_if(!$record, 404, 'Questa spedizione InPost non esiste');

        return view('Backend.SpedizioneInpost.show', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => SpedizioneInpost::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco ' . SpedizioneInpost::NOME_PLURALE],
        ]);
    }

    public function edit($id)
    {
        $record = SpedizioneInpost::find($id);
        abort_if(!$record, 404, 'Questa spedizione InPost non esiste');

        return view('Backend.SpedizioneInpost.edit', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => 'Modifica ' . SpedizioneInpost::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco ' . SpedizioneInpost::NOME_PLURALE],
        ]);
    }

    public function update(Request $request, $id)
    {
        $record = SpedizioneInpost::find($id);
        abort_if(!$record, 404, 'Questa spedizione InPost non esiste');

        $request->validate($this->rules());
        $this->salvaDati($record, $request);
        if (!$record->shipment_uuid) {
            $this->creaSpedizioneRemota($record);
        }

        return $this->backToIndex();
    }

    public function destroy($id)
    {
        $record = SpedizioneInpost::find($id);
        abort_if(!$record, 404, 'Questa spedizione InPost non esiste');
        $record->delete();

        return [
            'success' => true,
            'redirect' => action([self::class, 'index']),
        ];
    }

    public function trackingRefresh($id)
    {
        $record = SpedizioneInpost::find($id);
        abort_if(!$record, 404, 'Questa spedizione InPost non esiste');

        $result = $this->aggiornaTrackingRecord($record, true);

        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'trackingHtml' => $record->tracking() ?: '-',
            'trackingStatusHtml' => $record->trackingStatusBadge(),
            'trackingUpdatedAt' => $record->trackingUpdatedAtLabel() ?: '-',
        ];
    }

    public function trackingRefreshBulk(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || !count($ids)) {
            return ['success' => false, 'message' => 'Nessuna spedizione selezionata'];
        }

        $records = SpedizioneInpost::whereIn('id', $ids)->get();
        $updated = 0;
        $rows = [];

        foreach ($records as $record) {
            $result = $this->aggiornaTrackingRecord($record, true);
            if ($result['success']) {
                $updated++;
            }

            $rows[$record->id] = [
                'trackingHtml' => $record->tracking() ?: '-',
                'trackingStatusHtml' => $record->trackingStatusBadge(),
                'trackingUpdatedAt' => $record->trackingUpdatedAtLabel() ?: '-',
            ];
        }

        return [
            'success' => true,
            'message' => "Tracking aggiornato per {$updated} spedizioni",
            'rows' => $rows,
        ];
    }

    public function etichetta($id)
    {
        $record = SpedizioneInpost::find($id);
        abort_if(!$record, 404, 'Questa spedizione InPost non esiste');

        $service = new InpostService();
        $label = $service->label($record);
        abort_if(!$label, 404, 'Etichetta non disponibile');

        return Response::make($label['content'], 200, [
            'Content-Type' => $label['content_type'],
            'Content-Disposition' => 'inline; filename="' . $label['filename'] . '"',
        ]);
    }

    public function sync($id)
    {
        $record = SpedizioneInpost::find($id);
        abort_if(!$record, 404, 'Questa spedizione InPost non esiste');
        abort_if(!$record->shipment_uuid, 404, 'Shipment UUID non disponibile');

        $service = new InpostService();
        $response = $service->getShipment($record->shipment_uuid, $record);
        $record->response = array_merge($record->response ?? [], ['shipmentRead' => $response]);
        $record->tracking_number = data_get($response, 'trackingNumber') ?: data_get($response, 'tracking.number') ?: $record->tracking_number;
        $record->esito = data_get($response, 'status') ?: $record->esito;
        $record->save();

        return redirect()->back();
    }

    protected function applicaFiltri(Request $request)
    {
        $qb = SpedizioneInpost::query()->latest()->with('agente:id,alias');
        $term = trim((string) $request->input('cerca'));

        if ($term !== '') {
            foreach (explode(' ', $term) as $chunk) {
                $qb->where(DB::raw('concat_ws(" ",ragione_sociale_destinatario,nome_mittente,tracking_number)'), 'like', "%{$chunk}%");
            }
        }

        return $qb;
    }

    protected function salvaDati(SpedizioneInpost $record, Request $request): void
    {
        if (!$record->exists) {
            $record->caricato_da_user_id = Auth::id();
        }

        $altriDati = is_array($request->input('altri_dati')) ? $request->input('altri_dati') : [];
        $packageType = (string)($altriDati['package_type'] ?? 'small');
        $datiColli = $this->normalizeDatiColli($request->input('dati_colli', []), $packageType);

        $campi = [
            'agente_id' => '',
            'delivery_type' => '',
            'ragione_sociale_destinatario' => '',
            'indirizzo_destinatario' => '',
            'cap_destinatario' => '',
            'localita_destinazione' => '',
            'provincia_destinatario' => '',
            'nazione_destinazione' => '',
            'email_destinatario' => '',
            'mobile_referente_consegna' => '',
            'numero_pacchi' => '',
            'peso_totale' => 'app\getInputNumero',
            'volume_totale' => 'app\getInputNumero',
            'punto_inpost_id' => '',
            'punto_inpost_label' => '',
            'nome_mittente' => '',
            'email_mittente' => '',
            'mobile_mittente' => '',
        ];

        foreach ($campi as $campo => $funzione) {
            $valore = $request->input($campo);
            if ($funzione) {
                $valore = $funzione($valore);
            }
            $record->{$campo} = $valore;
        }

        $record->numero_pacchi = 1;
        $record->dati_colli = $datiColli;
        $record->peso_totale = getInputNumero($this->computePesoTotale($datiColli));
        $record->volume_totale = getInputNumero($this->computeVolumeTotale($datiColli));
        $record->altri_dati = array_merge($altriDati, [
            'package_type' => $packageType,
            'package_reference' => trim((string)($altriDati['package_reference'] ?? '')),
            'annotation' => trim((string)($altriDati['annotation'] ?? '')),
        ]);

        $record->save();
    }

    protected function creaSpedizioneRemota(SpedizioneInpost $record): void
    {
        $service = new InpostService();
        $record->request_payload = $service->buildShipmentPayload($record);

        $response = $service->shipment($record);
        $record->response = array_merge($record->response ?? [], $response);
        $record->labels = data_get($response, 'labels') ?: data_get($response, 'label');
        $record->shipment_uuid = data_get($response, 'shipmentUuid') ?: data_get($response, 'uuid') ?: data_get($response, 'id') ?: $record->shipment_uuid;
        $record->tracking_number = data_get($response, 'trackingNumber') ?: data_get($response, 'tracking.number') ?: $record->tracking_number;
        $record->label_url = data_get($response, 'label.url') ?: data_get($response, 'labels.0.url') ?: $record->label_url;
        $record->esito = data_get($response, 'status') ?: (data_get($response, 'error') ? 'ERROR' : 'CREATED');
        $record->esito_testo = data_get($response, 'message') ?: data_get($response, 'error.message') ?: $record->esito;
        $record->save();
    }

    protected function aggiornaTrackingTabella($records): void
    {
        $batchSize = (int) config('services.inpost.tracking_batch_size', 10);
        if ($batchSize <= 0) {
            return;
        }

        collect($records->items())
            ->filter(fn ($record) => $record instanceof SpedizioneInpost)
            ->take($batchSize)
            ->each(fn ($record) => $this->aggiornaTrackingRecord($record, false));
    }

    protected function aggiornaTrackingRecord(SpedizioneInpost $record, bool $force = false): array
    {
        if (!$record->tracking_number) {
            return ['success' => false, 'message' => 'Tracking non disponibile'];
        }

        if (!$force) {
            $updatedAt = data_get($record->response, 'trackingUpdatedAt');
            $staleMinutes = (int) config('services.inpost.tracking_stale_minutes', 120);
            if ($updatedAt) {
                try {
                    if (now()->diffInMinutes(\Carbon\Carbon::parse($updatedAt)) < $staleMinutes) {
                        return ['success' => true, 'message' => 'Tracking recente'];
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        $service = new InpostService();
        $tracking = $service->tracking($record->tracking_number);
        $response = $record->response ?? [];
        $response['trackingResponse'] = $tracking;
        $response['trackingUpdatedAt'] = now()->toIso8601String();
        $record->response = $response;
        $record->saveQuietly();

        return ['success' => true, 'message' => 'Tracking aggiornato'];
    }

    protected function rules(): array
    {
        return [
            'agente_id' => ['required'],
            'delivery_type' => ['required', 'in:point,address'],
            'ragione_sociale_destinatario' => ['required', 'max:120'],
            'indirizzo_destinatario' => ['nullable', 'max:120', 'required_if:delivery_type,address'],
            'cap_destinatario' => ['nullable', 'max:20', 'required_if:delivery_type,address'],
            'localita_destinazione' => ['nullable', 'max:120', 'required_if:delivery_type,address'],
            'nazione_destinazione' => ['required', 'size:2'],
            'email_destinatario' => ['required', 'email', 'max:255'],
            'mobile_referente_consegna' => ['required', 'max:32'],
            'numero_pacchi' => ['required'],
            'peso_totale' => ['required'],
            'volume_totale' => ['required'],
            'punto_inpost_id' => ['nullable', 'max:255', 'required_if:delivery_type,point'],
            'punto_inpost_label' => ['nullable', 'max:255'],
            'nome_mittente' => ['required', 'max:120'],
            'email_mittente' => ['nullable', 'email', 'max:255'],
            'mobile_mittente' => ['nullable', 'max:32'],
            'altri_dati.package_type' => ['required', 'in:small,medium,large'],
            'altri_dati.package_reference' => ['nullable', 'max:255'],
            'altri_dati.annotation' => ['nullable', 'max:255'],
        ];
    }

    protected function normalizeDatiColli($raw, string $packageType): array
    {
        $presets = [
            'small' => ['altezza' => 8, 'larghezza' => 38, 'profondita' => 64, 'peso_reale' => 25],
            'medium' => ['altezza' => 19, 'larghezza' => 38, 'profondita' => 64, 'peso_reale' => 25],
            'large' => ['altezza' => 41, 'larghezza' => 38, 'profondita' => 64, 'peso_reale' => 25],
        ];

        $collo = $presets[$packageType] ?? $presets['small'];

        $pesoVolumetrico = (($collo['larghezza'] ?? 0) * ($collo['altezza'] ?? 0) * ($collo['profondita'] ?? 0)) / 4000;
        $collo['peso_volumetrico'] = round((float)$pesoVolumetrico, 1);

        return [$collo];
    }

    protected function computePesoTotale(array $datiColli): string
    {
        $pesoTotale = 0;
        foreach ($datiColli as $collo) {
            $pesoReale = (float)($collo['peso_reale'] ?? 0);
            $pesoVolumetrico = (float)($collo['peso_volumetrico'] ?? 0);
            $pesoTotale += max($pesoReale, $pesoVolumetrico);
        }

        return number_format($pesoTotale, 1, ',', '');
    }

    protected function computeVolumeTotale(array $datiColli): string
    {
        $volumeTotale = 0;
        foreach ($datiColli as $collo) {
            $larghezza = (float)($collo['larghezza'] ?? 0);
            $altezza = (float)($collo['altezza'] ?? 0);
            $profondita = (float)($collo['profondita'] ?? 0);
            $volumeTotale += ($larghezza / 100) * ($altezza / 100) * ($profondita / 100);
        }

        return number_format($volumeTotale, 3, ',', '');
    }

    protected function backToIndex()
    {
        return redirect()->action([self::class, 'index']);
    }
}
