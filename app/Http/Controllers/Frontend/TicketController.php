<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\DatiRitorno;
use App\Models\AllegatoMessaggioTicket;
use App\Models\ContrattoTelefonia;
use App\Models\MessaggioTicket;
use App\Models\Notifica;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NotificaNuovoTicketAdAdmin;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    private const UPLOAD_UID_SESSION_KEY = 'ticket_upload_uids';

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function index()
    {
        /** @var LengthAwarePaginator $records */
        $records = $this->ticketsQuery()
            ->with('messaggi')
            ->latest('id')
            ->paginate(12);
        $records->withQueryString();

        return view('Frontend.Ticket.index', [
            'records' => $records,
            'titoloPagina' => 'I tuoi ticket',
            'unreadCount' => $this->unreadMessagesCount(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Ticket::class);

        $record = new Ticket;
        $uploadUid = (string) Str::ulid();
        $this->rememberUploadUid($uploadUid);

        return view('Frontend.Ticket.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo '.Ticket::NOME_SINGOLARE,
            'controller' => static::class,
            'contratti' => $this->contrattiForSelect(),
            'uploadUid' => $uploadUid,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco '.Ticket::NOME_PLURALE],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ticket::class);

        $request->validate($this->rules(null));
        $this->validateOwnedContratto((int) $request->input('contratto_id'));

        $record = new Ticket;
        $this->salvaDati($record, $request);
        $this->forgetUploadUid($request->input('uid'));

        Log::info('frontend_ticket_created', [
            'ticket_id' => $record->id,
            'user_id' => Auth::id(),
            'contratto_id' => $record->contratto_id,
        ]);

        dispatch(function () use ($record) {
            Notifica::notificaAdAdmin('Nuovo ticket', '<span class="fw-bold">'.$record->oggetto.'</span> da cliente <span class="fw-bold">'.$this->currentUser()->nominativo().'</span>');

            User::find(2)?->notify(new NotificaNuovoTicketAdAdmin($record));
        })->afterResponse();

        $datiRitorno = new DatiRitorno;
        $datiRitorno->success(true)
            ->chiudiDialog(true)
            ->oggettoReload('kt_help', view('Frontend.AreaUtente.elencoTickets', $this->ticketWidgetPayload()));

        return $datiRitorno->getArray();
    }

    public function show($id)
    {
        $record = $this->resolveOwnedTicket((int) $id, ['messaggi.utente', 'messaggi.allegati']);
        $this->authorize('view', $record);

        $uploadUid = (string) Str::ulid();
        $this->rememberUploadUid($uploadUid);

        dispatch(function () use ($record) {
            MessaggioTicket::where('ticket_id', $record->id)
                ->where('user_id', '<>', Auth::id())
                ->whereNull('letto')
                ->update(['letto' => now()]);
        })->afterResponse();

        return view('Frontend.Ticket.show', [
            'record' => $record,
            'controller' => self::class,
            'uploadUid' => $uploadUid,
            'titoloPagina' => 'Visualizzazione '.ucfirst(Ticket::NOME_SINGOLARE),
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco '.Ticket::NOME_PLURALE],
        ]);
    }

    public function edit($id)
    {
        $record = $this->resolveOwnedTicket((int) $id);
        $this->authorize('update', $record);

        return view('Frontend.Ticket.edit', [
            'record' => $record,
            'controller' => self::class,
            'contratti' => $this->contrattiForSelect(),
            'uploadUid' => old('uid', $record->uid),
            'titoloPagina' => 'Modifica '.Ticket::NOME_SINGOLARE,
            'eliminabile' => false,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco '.Ticket::NOME_PLURALE],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'messaggio' => ['required', 'string'],
            'uid' => ['required', 'string', 'max:64'],
        ]);

        $record = $this->resolveOwnedTicket((int) $id);
        $this->authorize('update', $record);

        if (! $this->canUseUploadUid($request->input('uid')) && $request->input('uid') !== $record->uid) {
            throw ValidationException::withMessages(['uid' => 'Sessione allegati non valida. Riprova.']);
        }

        $messaggio = new MessaggioTicket;
        $messaggio->ticket_id = $record->id;
        $messaggio->user_id = Auth::id();
        $messaggio->messaggio = $request->input('messaggio');
        $messaggio->uid = $request->input('uid');
        $messaggio->save();

        $record->touch();

        AllegatoMessaggioTicket::where('uid', $messaggio->uid)
            ->whereNull('messaggio_id')
            ->update(['messaggio_id' => $messaggio->id, 'uid' => null]);

        $this->forgetUploadUid($request->input('uid'));

        Log::info('frontend_ticket_replied', [
            'ticket_id' => $record->id,
            'message_id' => $messaggio->id,
            'user_id' => Auth::id(),
        ]);

        $datiRitorno = new DatiRitorno;
        $datiRitorno->success(true)
            ->chiudiDialog(true)
            ->oggettoReload('kt_help', view('Frontend.AreaUtente.elencoTickets', $this->ticketWidgetPayload()));

        return $datiRitorno->getArray();
    }

    protected function salvaDati(Ticket $model, Request $request): Ticket
    {
        $nuovo = ! $model->id;

        if ($nuovo) {
            $model->stato = 'aperto';
            $model->user_id = Auth::id();
        }

        $campi = [
            'oggetto' => 'app\\getInputUcfirst',
            'contratto_id' => '',
            'tipo' => '',
            'uid' => '',
        ];

        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione !== '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        if ($request->filled('contratto_id')) {
            $contratto = ContrattoTelefonia::delCliente()->find($request->input('contratto_id'));
            if ($contratto && $contratto->agente_id) {
                $model->agente_id = $contratto->agente_id;
            }
        }

        $model->save();

        $messaggio = new MessaggioTicket;
        $messaggio->ticket_id = $model->id;
        $messaggio->user_id = Auth::id();
        $messaggio->messaggio = $request->input('messaggio');
        $messaggio->uid = $request->input('uid');
        $messaggio->save();

        AllegatoMessaggioTicket::where('uid', $model->uid)
            ->whereNull('messaggio_id')
            ->update(['messaggio_id' => $messaggio->id, 'uid' => null]);

        return $model;
    }

    protected function rules($id = null): array
    {
        return [
            'oggetto' => ['required', 'max:255'],
            'tipo' => ['required', 'max:255'],
            'messaggio' => ['required', 'string'],
            'contratto_id' => ['required', 'integer'],
            'uid' => ['required', 'string', 'max:64'],
        ];
    }

    public function uploadAllegato(Request $request)
    {
        $request->validate([
            'uid' => ['required', 'string', 'max:64'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt,zip'],
        ]);

        $uid = $request->input('uid');
        if (! $this->canUseUploadUid($uid)) {
            return response()->json(['success' => false, 'message' => 'Sessione allegati non valida'], 422);
        }

        $file = new AllegatoMessaggioTicket;
        $filePath = $request->file('file');
        $estensione = $filePath->extension();
        $fileName = Str::ulid().'.'.$estensione;
        $cartella = config('configurazione.allegati_ticket.cartella');

        $request->file('file')->storeAs($cartella, $fileName);
        $file->path_filename = $cartella.'/'.$fileName;
        $file->filename_originale = $filePath->getClientOriginalName();
        $file->mime_type = $filePath->getMimeType();
        $contenuto = file_get_contents($filePath->getRealPath());
        $file->file_contenuto_base64 = $contenuto !== false ? base64_encode($contenuto) : null;
        $file->uid = $uid;
        $file->dimensione_file = $filePath->getSize();
        $file->save();

        Log::info('frontend_ticket_attachment_uploaded', [
            'attachment_id' => $file->id,
            'user_id' => Auth::id(),
            'uid' => $uid,
        ]);

        return response()->json(['success' => true, 'id' => $file->id, 'filename' => $fileName]);
    }

    public function downloadAllegato($messaggioId, $allegatoId)
    {
        $record = AllegatoMessaggioTicket::find($allegatoId);
        abort_if(! $record, 404, 'Questo allegato non esiste');
        abort_if((int) $record->messaggio_id !== (int) $messaggioId, 404, 'Questo allegato non esiste');

        $messaggio = MessaggioTicket::with('ticket')->find($messaggioId);
        abort_if(! $messaggio || ! $messaggio->ticket || (int) $messaggio->ticket->user_id !== (int) Auth::id(), 403, 'Allegato non disponibile');

        Log::info('frontend_ticket_attachment_downloaded', [
            'attachment_id' => $record->id,
            'ticket_id' => $messaggio->ticket->id,
            'user_id' => Auth::id(),
        ]);

        if ($record->file_contenuto_base64) {
            $contenuto = base64_decode($record->file_contenuto_base64, true);
            if ($contenuto !== false) {
                return response($contenuto, 200, [
                    'Content-Type' => $record->mime_type ?: 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename="'.addslashes($record->filename_originale).'"',
                ]);
            }
        }

        return response()->download(Storage::path($record->path_filename), $record->filename_originale);
    }

    public function deleteAllegato(Request $request)
    {
        $record = AllegatoMessaggioTicket::find($request->input('id'));
        abort_if(! $record, 404, 'File non trovato');

        $owned = false;
        if ($record->messaggio_id) {
            $messaggio = MessaggioTicket::with('ticket')->find($record->messaggio_id);
            $owned = $messaggio && $messaggio->ticket && (int) $messaggio->ticket->user_id === (int) Auth::id();
        } else {
            $owned = $record->uid && $this->canUseUploadUid($record->uid);
        }

        abort_if(! $owned, 403, 'Operazione non consentita');

        Log::info('frontend_ticket_attachment_deleted', [
            'attachment_id' => $record->id,
            'user_id' => Auth::id(),
            'ticket_message_id' => $record->messaggio_id,
        ]);

        $path = $record->path_filename;
        $record->delete();

        return $path;
    }

    protected function ticketsQuery()
    {
        return Ticket::query()->where('user_id', Auth::id());
    }

    protected function resolveOwnedTicket(int $id, array $with = []): Ticket
    {
        $query = $this->ticketsQuery();
        if ($with !== []) {
            $query->with($with);
        }

        $record = $query->find($id);
        abort_if(! $record, 404, 'Questo ticket non esiste');

        return $record;
    }

    protected function validateOwnedContratto(int $contrattoId): void
    {
        $found = ContrattoTelefonia::delCliente()->whereKey($contrattoId)->exists();
        if (! $found) {
            throw ValidationException::withMessages(['contratto_id' => 'Contratto non valido per il tuo account']);
        }
    }

    protected function rememberUploadUid(string $uid): void
    {
        $uids = session()->get(self::UPLOAD_UID_SESSION_KEY, []);
        $uids[] = $uid;
        session()->put(self::UPLOAD_UID_SESSION_KEY, array_values(array_unique($uids)));
    }

    protected function forgetUploadUid(?string $uid): void
    {
        if (! $uid) {
            return;
        }

        $uids = session()->get(self::UPLOAD_UID_SESSION_KEY, []);
        $uids = array_values(array_filter($uids, static fn ($item) => $item !== $uid));
        session()->put(self::UPLOAD_UID_SESSION_KEY, $uids);
    }

    protected function canUseUploadUid(string $uid): bool
    {
        return in_array($uid, session()->get(self::UPLOAD_UID_SESSION_KEY, []), true);
    }

    protected function contrattiForSelect(): array
    {
        $arr = [];
        foreach (ContrattoTelefonia::delCliente()->get() as $c) {
            $arr[$c->id] = $c->tipoContratto->nome;
        }

        return $arr;
    }

    protected function unreadMessagesCount(): int
    {
        return MessaggioTicket::query()
            ->whereHas('ticket', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->where('user_id', '<>', Auth::id())
            ->whereNull('letto')
            ->count();
    }

    protected function ticketWidgetPayload(): array
    {
        $ticketsRecenti = $this->ticketsQuery()->latest('updated_at')->limit(8)->get();

        return [
            'ticketsRecenti' => $ticketsRecenti,
            'ticketsTotali' => $this->ticketsQuery()->count(),
            'ticketsDaLeggere' => $this->unreadMessagesCount(),
        ];
    }
}
