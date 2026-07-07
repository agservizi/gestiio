<?php

namespace App\Http\Controllers\Backend;

use App\Enums\OrdineEbikeStatoEnum;
use App\Http\Controllers\Controller;
use App\Models\Notifica;
use App\Models\OrdineEbike;
use App\Models\ProdottoEbike;
use App\Models\RigaOrdineEbike;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NotificaEbikeNuovoOrdine;
use App\Notifications\NotificaEbikePagamentoConfermato;
use App\Notifications\NotificaEbikePagamentoDaVerificare;
use App\Notifications\NotificaEbikeSpedito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EbikeOrdineController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            /** @var User $authUser */
            $authUser = Auth::user();
            abort_unless($authUser?->hasPermissionTo('admin') || $authUser?->hasPermissionTo('ebike-b2b'), 403);

            return $next($request);
        });
    }

    /**
     * @return mixed
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $records = OrdineEbike::query()
            ->with(['agente', 'righe'])
            ->orderByDesc('id')
            ->paginate(config('configurazione.paginazione'))
            ->withQueryString();

        return view('Backend.EbikeOrdine.index', [
            'records' => $records,
            'controller' => get_class($this),
            'titoloPagina' => 'Ordini ebike',
            'isAdmin' => $authUser->hasPermissionTo('admin'),
        ]);
    }

    /**
     * @return mixed
     */
    public function create()
    {
        abort_unless(Auth::user()?->hasPermissionTo('ebike-b2b'), 403, 'Il servizio ebike B2B non è abilitato per il tuo profilo.');

        return view('Backend.EbikeOrdine.create', [
            'controller' => get_class($this),
            'titoloPagina' => 'Nuovo ordine ebike',
            'prodotti' => ProdottoEbike::attivi()->orderBy('nome')->get(),
            'iban' => Setting::get('ebike_iban'),
            'intestatario' => Setting::get('ebike_intestatario_conto'),
            'banca' => Setting::get('ebike_banca'),
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna agli ordini'],
        ]);
    }

    /**
     * @return mixed
     */
    public function store(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        abort_unless($authUser->hasPermissionTo('ebike-b2b'), 403, 'Il servizio ebike B2B non è abilitato per il tuo profilo.');

        $request->validate([
            'quantita' => ['required', 'array'],
            'quantita.*' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $righeRichieste = collect($request->input('quantita', []))
            ->map(fn ($qty) => (int) $qty)
            ->filter(fn (int $qty) => $qty > 0);

        if ($righeRichieste->isEmpty()) {
            throw ValidationException::withMessages([
                'quantita' => 'Seleziona almeno un prodotto con quantità maggiore di zero.',
            ]);
        }

        $ordine = DB::transaction(function () use ($righeRichieste, $request, $authUser) {
            $ordine = new OrdineEbike;
            $ordine->agente_id = $authUser->id;
            $ordine->stato = OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO;
            $ordine->note = $request->input('note');
            $ordine->save();

            foreach ($righeRichieste as $prodottoId => $quantita) {
                $prodotto = ProdottoEbike::whereKey($prodottoId)->lockForUpdate()->first();

                if (! $prodotto || ! $prodotto->attivo) {
                    throw ValidationException::withMessages([
                        'quantita' => 'Uno dei prodotti selezionati non è più disponibile.',
                    ]);
                }

                if ($prodotto->giacenza < $quantita) {
                    throw ValidationException::withMessages([
                        'quantita' => 'Giacenza insufficiente per '.$prodotto->nome.'.',
                    ]);
                }

                $riga = new RigaOrdineEbike;
                $riga->ordine_id = $ordine->id;
                $riga->prodotto_id = $prodotto->id;
                $riga->nome_prodotto = $prodotto->nome;
                $riga->quantita = $quantita;
                $riga->prezzo_unitario = $prodotto->prezzo;
                $riga->save();

                $prodotto->decrement('giacenza', $quantita);
            }

            $ordine->ricalcolaTotale();

            return $ordine;
        });

        dispatch(function () use ($ordine) {
            $userAdmin = User::find(2);
            $userAdmin?->notify(new NotificaEbikeNuovoOrdine($ordine));
            Notifica::notificaAdAdmin(
                'Nuovo ordine ebike',
                'Nuovo ordine creato da <span class="fw-bold">'.($ordine->agente?->nominativo() ?? 'Agente #'.$ordine->agente_id).'</span>.',
                'info'
            );
        })->afterResponse();

        return redirect()->action([self::class, 'show'], ['id' => $ordine->id])
            ->with('status', 'Ordine creato. Effettua il bonifico istantaneo e carica la ricevuta.');
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function show($id)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $record = OrdineEbike::with(['agente', 'confermatoDa', 'righe.prodotto'])->find($id);
        abort_if(! $record, 404, 'Questo ordine non esiste');

        return view('Backend.EbikeOrdine.show', [
            'record' => $record,
            'controller' => get_class($this),
            'titoloPagina' => 'Ordine ebike #'.$record->id,
            'isAdmin' => $authUser->hasPermissionTo('admin'),
            'iban' => Setting::get('ebike_iban'),
            'intestatario' => Setting::get('ebike_intestatario_conto'),
            'banca' => Setting::get('ebike_banca'),
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna agli ordini'],
        ]);
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function caricaPagamento(Request $request, $id)
    {
        $record = OrdineEbike::find($id);
        abort_if(! $record, 404, 'Questo ordine non esiste');
        abort_unless($record->agente_id === Auth::id(), 403);

        if ($record->stato !== OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO) {
            throw ValidationException::withMessages([
                'stato' => 'Questo ordine non è più in attesa di bonifico.',
            ]);
        }

        $request->validate([
            'cro_bonifico' => ['required', 'string', 'max:255'],
            'data_bonifico_dichiarata' => ['required', 'date'],
            'ricevuta_bonifico' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ]);

        $record->cro_bonifico = $request->input('cro_bonifico');
        $record->data_bonifico_dichiarata = $request->input('data_bonifico_dichiarata');
        $record->ricevuta_bonifico = $request->file('ricevuta_bonifico')->store('ebike/ricevute/'.$record->id, 'public');
        $record->stato = OrdineEbikeStatoEnum::PAGAMENTO_DA_VERIFICARE;
        $record->save();

        dispatch(function () use ($record) {
            $userAdmin = User::find(2);
            $userAdmin?->notify(new NotificaEbikePagamentoDaVerificare($record));
            Notifica::notificaAdAdmin(
                'Bonifico da verificare',
                'Ricevuta caricata per l\'ordine ebike #'.$record->id.' di <span class="fw-bold">'.($record->agente?->nominativo() ?? 'Agente #'.$record->agente_id).'</span>.',
                'warning'
            );
        })->afterResponse();

        return redirect()->action([self::class, 'show'], ['id' => $record->id])
            ->with('status', 'Ricevuta caricata. In attesa di verifica da parte di admin.');
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function confermaPagamento(Request $request, $id)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin'), 403);

        $record = OrdineEbike::find($id);
        abort_if(! $record, 404, 'Questo ordine non esiste');

        if ($record->stato !== OrdineEbikeStatoEnum::PAGAMENTO_DA_VERIFICARE
            && $record->stato !== OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO) {
            throw ValidationException::withMessages([
                'stato' => 'Il pagamento di questo ordine è già stato gestito.',
            ]);
        }

        $record->stato = OrdineEbikeStatoEnum::PAGAMENTO_CONFERMATO;
        $record->pagamento_confermato_da = Auth::id();
        $record->pagamento_confermato_at = now();
        $record->scadenza_spedizione = now()->addDays(OrdineEbike::GIORNI_SLA_SPEDIZIONE)->toDateString();
        $record->save();

        if ($record->agente) {
            $record->agente->notify(new NotificaEbikePagamentoConfermato($record));
            Notifica::notificaAdAgente(
                $record->agente,
                'Pagamento confermato',
                'Il pagamento del tuo ordine ebike #'.$record->id.' è stato confermato. Verrà spedito entro '.OrdineEbike::GIORNI_SLA_SPEDIZIONE.' giorni.',
                'success'
            );
        }

        return redirect()->action([self::class, 'show'], ['id' => $record->id])
            ->with('status', 'Pagamento confermato e agente notificato.');
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function impostaTracking(Request $request, $id)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin'), 403);

        $record = OrdineEbike::find($id);
        abort_if(! $record, 404, 'Questo ordine non esiste');

        if ($record->stato !== OrdineEbikeStatoEnum::PAGAMENTO_CONFERMATO) {
            throw ValidationException::withMessages([
                'stato' => 'Puoi impostare il tracking solo dopo aver confermato il pagamento.',
            ]);
        }

        $request->validate([
            'corriere' => ['required', 'string', 'max:255'],
            'tracking_number' => ['required', 'string', 'max:255'],
        ]);

        $record->corriere = $request->input('corriere');
        $record->tracking_number = $request->input('tracking_number');
        $record->stato = OrdineEbikeStatoEnum::SPEDITO;
        $record->spedito_at = now();
        $record->save();

        if ($record->agente) {
            $record->agente->notify(new NotificaEbikeSpedito($record));
            Notifica::notificaAdAgente(
                $record->agente,
                'Ordine spedito',
                'Il tuo ordine ebike #'.$record->id.' è stato spedito con '.$record->corriere.'. Tracking: '.$record->tracking_number,
                'success'
            );
        }

        return redirect()->action([self::class, 'show'], ['id' => $record->id])
            ->with('status', 'Tracking impostato e agente notificato.');
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function segnaConsegnato($id)
    {
        $record = OrdineEbike::find($id);
        abort_if(! $record, 404, 'Questo ordine non esiste');
        /** @var User $authUser */
        $authUser = Auth::user();
        abort_unless($authUser->hasPermissionTo('admin') || $record->agente_id === $authUser->id, 403);

        if ($record->stato !== OrdineEbikeStatoEnum::SPEDITO) {
            throw ValidationException::withMessages([
                'stato' => 'Questo ordine non risulta spedito.',
            ]);
        }

        $record->stato = OrdineEbikeStatoEnum::CONSEGNATO;
        $record->consegnato_at = now();
        $record->save();

        return redirect()->action([self::class, 'show'], ['id' => $record->id])
            ->with('status', 'Ordine segnato come consegnato.');
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function annulla(Request $request, $id)
    {
        $record = OrdineEbike::find($id);
        abort_if(! $record, 404, 'Questo ordine non esiste');
        /** @var User $authUser */
        $authUser = Auth::user();
        $isAdmin = $authUser->hasPermissionTo('admin');
        abort_unless($isAdmin || $record->agente_id === $authUser->id, 403);

        if (in_array($record->stato, [OrdineEbikeStatoEnum::SPEDITO, OrdineEbikeStatoEnum::CONSEGNATO, OrdineEbikeStatoEnum::ANNULLATO], true)) {
            throw ValidationException::withMessages([
                'stato' => 'Questo ordine non può più essere annullato.',
            ]);
        }

        if (! $isAdmin && $record->stato !== OrdineEbikeStatoEnum::IN_ATTESA_PAGAMENTO) {
            throw ValidationException::withMessages([
                'stato' => 'Puoi annullare l\'ordine solo prima di caricare il bonifico. Contatta l\'admin.',
            ]);
        }

        $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($record, $request) {
            foreach ($record->righe as $riga) {
                $riga->prodotto?->increment('giacenza', $riga->quantita);
            }

            $record->stato = OrdineEbikeStatoEnum::ANNULLATO;
            $record->annullato_motivo = $request->input('motivo');
            $record->save();
        });

        return redirect()->action([self::class, 'index'])
            ->with('status', 'Ordine annullato e giacenza ripristinata.');
    }
}
