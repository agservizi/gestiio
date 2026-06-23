<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ClienteAssistenza;
use App\Models\ProdottoAssistenza;
use App\Models\RichiestaAssistenza;
use App\Models\User;
use App\Notifications\NotificaRichiestaAssistenzaCredenziali;
use App\Rules\CodiceFiscaleRule;
use App\Rules\TelefonoRule;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class RichiestaAssistenzaController extends Controller
{
    protected $conFiltro = false;

    protected ?string $lastMailError = null;

    public function index(Request $request): View|JsonResponse
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

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $orderByUser = $authUser?->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } elseif ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($authUser instanceof User && $orderByUser != $orderByString) {
            $authUser->setExtra([$nomeClasse => $orderBy]);
        }

        // Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => base64_encode(view('Backend.RichiestaAssistenza.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render()),
            ]);
        }

        return view('Backend.RichiestaAssistenza.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco '.RichiestaAssistenza::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova '.RichiestaAssistenza::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in cognome, nome, codice fiscale, email',
            'prodotti' => Cache::remember('prodotti_assistenza', 3600, fn () => ProdottoAssistenza::pluck('nome', 'id')),

        ]);

    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = RichiestaAssistenza::query()
            ->select('id', 'cliente_id', 'prodotto_assistenza_id', 'created_at', 'nome_utente', 'password', 'pin')
            ->with('prodotto:id,nome')
            ->with('cliente:id,nome,cognome,email,codice_fiscale');
        $term = $request->input('cerca');
        if ($term) {
            $queryBuilder->whereHas('cliente', function ($q) use ($term) {
                $arrTerm = explode(' ', $term);
                foreach ($arrTerm as $t) {
                    $q->where(function ($query) use ($t) {
                        $query->where('cognome', 'like', "%$t%")
                            ->orWhere('nome', 'like', "%$t%")
                            ->orWhere('codice_fiscale', 'like', "%$t%")
                            ->orWhere('email', 'like', "%$t%");
                    });
                }
            });
        }

        // $this->conFiltro = true;
        return $queryBuilder;
    }

    public function create(Request $request): View
    {
        $record = new RichiestaAssistenza;
        $record->cliente_id = $request->input('cliente_id');

        return view('Backend.RichiestaAssistenza.edit', [
            'record' => $record,
            'clienteInline' => $record->cliente_id ? ClienteAssistenza::find($record->cliente_id) : new ClienteAssistenza,
            'titoloPagina' => 'Nuovo '.RichiestaAssistenza::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([RichiestaAssistenzaController::class, 'index']) => 'Torna a elenco '.RichiestaAssistenza::NOME_PLURALE],

        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules(null));
        $record = new RichiestaAssistenza;
        $this->salvaDati($record, $request);
        $inviata = $this->inviaCredenzialiConPdfAlCliente($record);
        $status = $inviata
            ? 'Richiesta assistenza creata correttamente e credenziali inviate al cliente'
            : 'Richiesta assistenza creata correttamente (invio mail non riuscito: verifica email cliente)';

        return $this->backToIndex($status);
    }

    public function show($id): View
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(! $record, 404, 'Questa richiestaassistenza non esiste');

        return view('Backend.RichiestaAssistenza.show', [
            'record' => $record,
            'controller' => RichiestaAssistenzaController::class,
            'titoloPagina' => RichiestaAssistenza::NOME_SINGOLARE,
            'breadcrumbs' => [action([RichiestaAssistenzaController::class, 'index']) => 'Torna a elenco '.RichiestaAssistenza::NOME_PLURALE],

        ]);
    }

    public function edit($id): View
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(! $record, 404, 'Questa richiestaassistenza non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }

        return view('Backend.RichiestaAssistenza.edit', [
            'record' => $record,
            'clienteInline' => $record->cliente ?? new ClienteAssistenza,
            'controller' => RichiestaAssistenzaController::class,
            'titoloPagina' => 'Modifica '.RichiestaAssistenza::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([RichiestaAssistenzaController::class, 'index']) => 'Torna a elenco '.RichiestaAssistenza::NOME_PLURALE],

        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(! $record, 404, 'Questa '.RichiestaAssistenza::NOME_SINGOLARE.' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);
        $inviata = $this->inviaCredenzialiConPdfAlCliente($record);
        $status = $inviata
            ? 'Richiesta assistenza aggiornata correttamente e credenziali inviate al cliente'
            : 'Richiesta assistenza aggiornata correttamente (invio mail non riuscito: verifica email cliente)';

        return $this->backToIndex($status);
    }

    public function reinviaCredenziali(int $id): RedirectResponse
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(! $record, 404, 'Questa richiesta assistenza non esiste');

        $inviata = $this->inviaCredenzialiConPdfAlCliente($record);
        if (! $inviata) {
            return redirect()->back()->withErrors([
                'mail' => 'Impossibile inviare la mail al cliente. '.($this->lastMailError ?: 'Verifica email e dati richiesta.'),
            ]);
        }

        return redirect()->back()->with('status', 'Credenziali reinviate correttamente al cliente');
    }

    public function destroy($id): JsonResponse
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(! $record, 404, 'Questa richiestaassistenza non esiste');

        $record->delete();

        return response()->json([
            'success' => true,
            'redirect' => action([RichiestaAssistenzaController::class, 'index']),
        ]);
    }

    public function pdf($id)
    {
        $richiesta = RichiestaAssistenza::with('cliente')->with('prodotto')->find($id);
        abort_if(! $richiesta, 404, 'Questa richiesta assistenza non esiste');
        $pdf = $this->buildPdfPayload($richiesta);

        return response($pdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.addslashes($pdf['filename']).'"',
        ]);

    }

    protected function buildPdfPayload(RichiestaAssistenza $richiesta): array
    {
        $nomeProdotto = Str::lower((string) optional($richiesta->prodotto)->nome);
        $isNamirial = (int) $richiesta->prodotto_assistenza_id === 1 || Str::contains($nomeProdotto, 'namirial');
        $isInfocert = (int) $richiesta->prodotto_assistenza_id === 2 || Str::contains($nomeProdotto, 'infocert');

        if ($isNamirial) {
            return $this->buildPdfNamirialPayload($richiesta);
        }

        if ($isInfocert) {
            return $this->buildPdfInfocertPayload($richiesta);
        }

        abort(422, 'PDF non disponibile per il prodotto assistenza selezionato');
    }

    protected function buildPdfNamirialPayload(RichiestaAssistenza $richiesta): array
    {
        $fpdf = new Fpdi;

        $pagecount = $fpdf->setSourceFile(public_path('/pdf/spid_namirial.pdf'));
        $tpl = $fpdf->importPage(1);
        $fpdf->AddPage();
        $fpdf->useTemplate($tpl);
        $fpdf->SetFont('Arial', 'B');

        $fpdf->SetFontSize('20'); // set font size
        $fpdf->SetAutoPageBreak(false);
        $fpdf->SetXY(60, 62);
        $fpdf->Cell(50, 8, $richiesta->nome_utente, 0, 0);

        $fpdf->SetXY(60, 77);
        $fpdf->Cell(50, 8, $richiesta->password, 0, 0);

        $fpdf->SetXY(9, 45);
        $fpdf->Cell(50, 8, $richiesta->pin, 0, 0);

        return [
            'content' => $fpdf->Output('S'),
            'filename' => 'spid_'.Str::slug((string) optional($richiesta->cliente)->codice_fiscale).'.pdf',
        ];
    }

    protected function buildPdfInfocertPayload(RichiestaAssistenza $richiesta): array
    {
        $fpdf = new Fpdi;

        $pagecount = $fpdf->setSourceFile(public_path('/pdf/spid_infocert.pdf'));
        $tpl = $fpdf->importPage(1);
        $fpdf->AddPage();
        $fpdf->useTemplate($tpl);
        $fpdf->SetFont('Arial', 'B');

        $fpdf->SetFontSize('20'); // set font size
        $fpdf->SetAutoPageBreak(false);
        $fpdf->SetXY(60, 32.5);
        $fpdf->Cell(50, 8, $richiesta->pin, 0, 0);

        $fpdf->SetXY(60, 59);
        $fpdf->Cell(50, 8, $richiesta->nome_utente, 0, 0);

        $fpdf->SetXY(60, 73.5);
        $fpdf->Cell(50, 8, $richiesta->password, 0, 0);

        return [
            'content' => $fpdf->Output('S'),
            'filename' => 'spid_'.Str::slug((string) optional($richiesta->cliente)->codice_fiscale).'.pdf',
        ];
    }

    protected function salvaDati(RichiestaAssistenza $model, Request $request): RichiestaAssistenza
    {

        $nuovo = ! $model->exists;

        if ($nuovo) {

        }

        $clienteId = $this->resolveClienteId($request, $model->cliente_id);

        // Ciclo su campi
        $campi = [
            'prodotto_assistenza_id' => '',
            'nome_utente' => '',
            'password' => '',
            'pin' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $this->salvaContattiClienteRapidi($request, $clienteId);
        $model->cliente_id = $clienteId;

        $model->save();

        return $model;
    }

    protected function salvaContattiClienteRapidi(Request $request, int $clienteId): void
    {
        if (! $request->has('cliente_email_edit') && ! $request->has('cliente_telefono_edit')) {
            return;
        }

        $cliente = ClienteAssistenza::find($clienteId);
        if (! $cliente) {
            return;
        }

        if ($request->has('cliente_email_edit')) {
            $cliente->email = strtolower((string) $request->input('cliente_email_edit', ''));
        }

        if ($request->has('cliente_telefono_edit')) {
            $cliente->telefono = \App\getInputTelefono($request->input('cliente_telefono_edit'));
        }

        $cliente->save();
    }

    protected function resolveClienteId(Request $request, ?int $currentClienteId = null): int
    {
        $clienteId = $request->input('cliente_id');
        $codiceFiscale = strtoupper((string) $request->input('cliente_codice_fiscale', ''));

        if ($clienteId) {
            $cliente = ClienteAssistenza::find($clienteId);
        } elseif ($codiceFiscale !== '') {
            $cliente = ClienteAssistenza::firstOrNew(['codice_fiscale' => $codiceFiscale]);
        } elseif ($currentClienteId) {
            $cliente = ClienteAssistenza::find($currentClienteId);
        } else {
            $cliente = new ClienteAssistenza;
        }

        abort_if(! $cliente, 404, 'Cliente assistenza non trovato');

        if ($codiceFiscale !== '' || ! $cliente->id) {
            $cliente->nome = \App\getInputUcwords($request->input('cliente_nome'));
            $cliente->cognome = \App\getInputUcwords($request->input('cliente_cognome'));
            $cliente->codice_fiscale = $codiceFiscale;
            $cliente->email = strtolower((string) $request->input('cliente_email', ''));
            $cliente->telefono = \App\getInputTelefono($request->input('cliente_telefono'));
            $cliente->save();
        }

        return (int) $cliente->id;
    }

    protected function inviaCredenzialiConPdfAlCliente(RichiestaAssistenza $richiesta): bool
    {
        $this->lastMailError = null;
        $richiesta->loadMissing('cliente', 'prodotto');
        $email = trim((string) optional($richiesta->cliente)->email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->lastMailError = 'Email cliente assente o non valida.';
            Log::warning('Richiesta assistenza: email cliente non valida per invio credenziali', [
                'richiesta_id' => (int) $richiesta->id,
                'cliente_id' => (int) $richiesta->cliente_id,
            ]);

            return false;
        }

        try {
            $pdf = $this->buildPdfPayload($richiesta);
            Notification::route('mail', $email)->notify(
                new NotificaRichiestaAssistenzaCredenziali($richiesta, $pdf['content'], $pdf['filename'])
            );

            return true;
        } catch (\Throwable $e) {
            $this->lastMailError = $this->friendlyMailError($e);
            Log::error('Richiesta assistenza: invio credenziali cliente fallito', [
                'richiesta_id' => (int) $richiesta->id,
                'cliente_id' => (int) $richiesta->cliente_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function friendlyMailError(\Throwable $e): string
    {
        $message = trim((string) $e->getMessage());
        $lower = strtolower($message);

        if (str_contains($lower, 'you can only send testing emails') || str_contains($lower, 'verify a domain at resend.com/domains')) {
            return 'Resend è in modalità test: puoi inviare solo verso indirizzi autorizzati finché il dominio non è verificato.';
        }

        if (str_contains($lower, 'pdf non disponibile')) {
            return 'PDF non disponibile per il prodotto assistenza selezionato.';
        }

        return $message !== '' ? $message : 'Errore sconosciuto durante invio email.';
    }

    protected function backToIndex(?string $status = null): RedirectResponse
    {
        $redirect = redirect()->action([get_class($this), 'index']);

        if ($status !== null && $status !== '') {
            $redirect->with('status', $status);
        }

        return $redirect;
    }

    /** Query per index
     * @return array
     */
    protected function queryBuilderIndexSemplice()
    {
        return RichiestaAssistenza::get();
    }

    protected function rules($id = null)
    {

        $rules = [
            'cliente_id' => ['nullable', 'required_without:cliente_codice_fiscale'],
            'cliente_nome' => ['nullable', 'required_without:cliente_id', 'max:255'],
            'cliente_cognome' => ['nullable', 'required_without:cliente_id', 'max:255'],
            'cliente_codice_fiscale' => ['nullable', 'required_without:cliente_id', new CodiceFiscaleRule],
            'cliente_email' => ['nullable', 'max:255'],
            'cliente_telefono' => ['nullable', new TelefonoRule],
            'cliente_email_edit' => ['nullable', 'max:255'],
            'cliente_telefono_edit' => ['nullable', new TelefonoRule],
            'prodotto_assistenza_id' => ['required'],
            'nome_utente' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
            'pin' => ['nullable', 'max:255'],
        ];

        return $rules;
    }
}
