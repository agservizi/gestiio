<?php

namespace App\Http\Controllers\Backend;

use App\Http\MieClassiCache\CacheUnaVoltaAlGiorno;
use App\Models\CafPatronato;
use App\Models\ContrattoEnergia;
use App\Models\ClienteAssistenza;
use App\Models\ChatMessage;
use App\Models\ChatThreadUser;
use App\Models\ContrattoTelefonia;
use App\Models\EsitoCafPatronato;
use App\Models\EsitoVisura;
use App\Models\File;
use App\Models\ProduzioneOperatore;
use App\Models\RichiestaAssistenza;
use App\Models\RegistroLogin;
use App\Models\SpedizioneBrt;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Visura;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use function App\mese;

class DashboardController extends Controller
{
    protected function salutoDashboard(): string
    {
        /** @var User|null $user */
        $user = Auth::user();
        $nome = trim((string)($user?->nome ?? ''));
        $genere = strtolower((string)($user?->genere ?? $user?->sesso ?? ''));

        if ($genere === '') {
            $codiceFiscale = strtoupper((string)($user?->codice_fiscale ?? ''));
            if ($codiceFiscale !== '') {
                try {
                    $parserCodiceFiscale = new CodiceFiscale();
                    if ($parserCodiceFiscale->parse($codiceFiscale) !== false) {
                        $genere = strtolower((string)$parserCodiceFiscale->getGender());
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        $ora = now()->hour;

        if ($ora < 12) {
            $saluto = 'Buongiorno';
            $icona = '☀️';
        } elseif ($ora < 18) {
            $saluto = 'Buon pomeriggio';
            $icona = '🌤️';
        } else {
            $saluto = 'Buonasera';
            $icona = '🌙';
        }

        $rientro = match ($genere) {
            'f', 'femmina', 'female' => 'bentornata',
            'm', 'maschio', 'male' => 'bentornato',
            default => 'che piacere rivederti',
        };

        return $nome !== ''
            ? $icona . ' ' . $saluto . ' ' . $nome . ', ' . $rientro . ' nella tua dashboard'
            : $icona . ' ' . $saluto . ', ' . $rientro . ' nella tua dashboard';
    }

    protected function produzioneDisponibile(): bool
    {
        return Schema::hasTable('produzioni_operatori');
    }

    public function show(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_if(!$user, 403);

        CacheUnaVoltaAlGiorno::get();

        if ($user->hasPermissionTo('admin')) {
            return $this->showAdmin($request);
        } else if ($user->hasPermissionTo('supervisore')) {
            return $this->showSupervisore($request);
        } else {
            return $this->showAgente($request);
        }

    }

    protected function showSupervisore(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_if(!$user, 403);

        $canTelefonia = $user->can('servizio_contratti_telefonia');
        $canEnergia = $user->can('servizio_contratti_energia');
        $canCafPatronato = $user->can('servizio_caf_patronato');
        $canTicket = $user->can('servizio_ticket');
        $canVisure = $user->can('servizio_visure');
        $canSpedizioni = $user->can('servizio_spedizioni');
        $canDocumentazione = $user->can('servizio_documentazione');

        $this->elencoMesi();
        $mese = $request->input('mese', now()->format('Y_m'));
        [$filtroAnno, $filtroMese] = explode('_', $mese);

        $contrattiTelefonia = collect();
        if ($canTelefonia) {
            $contrattiTelefonia = ContrattoTelefonia::query()
                ->with('agente')
                ->with('tipoContratto.gestore')
                ->with('esito')
                ->limit(10)
                ->orderByDesc('data')
                ->get();
        }

        $contrattiEnergia = collect();
        if ($canEnergia) {
            $contrattiEnergia = ContrattoEnergia::query()
                ->with('agente')
                ->with('tipoContratto.gestore')
                ->with('esito')
                ->limit(10)
                ->orderByDesc('data')
                ->get();
        }

        $serviziCafPatronato = collect();
        if ($canCafPatronato) {
            $serviziCafPatronato = CafPatronato::query()
                ->with('esito')
                ->with('agente')
                ->with('tipo:id,nome')
                ->withCount('allegati')
                ->withCount('allegatiPerCliente')
                ->limit(10)
                ->orderByDesc('data')
                ->get();
        }

        $ticketRecenti = collect();
        if ($canTicket) {
            $ticketRecenti = Ticket::query()
                ->with('utente')
                ->with('causaleTicket')
                ->orderByDesc('id')
                ->limit(5)
                ->where('stato', '<>', 'chiuso')
                ->get();
        }

        $conteggioTikets = collect();
        if ($canTicket) {
            $conteggioTikets = Ticket::groupBy('stato')
                ->select('stato', DB::raw('count(*) as conteggio'))
                ->get()
                ->keyBy('stato');
        }

        $kpiSupervisore = [
            'contratti_telefonia_mese' => $canTelefonia
                ? ContrattoTelefonia::query()
                    ->whereYear('data', $filtroAnno)
                    ->whereMonth('data', $filtroMese)
                    ->count()
                : 0,
            'contratti_energia_mese' => $canEnergia
                ? ContrattoEnergia::query()
                    ->whereYear('data', $filtroAnno)
                    ->whereMonth('data', $filtroMese)
                    ->count()
                : 0,
            'pratiche_caf_mese' => $canCafPatronato
                ? CafPatronato::query()
                    ->whereYear('data', $filtroAnno)
                    ->whereMonth('data', $filtroMese)
                    ->count()
                : 0,
            'ticket_aperti' => $canTicket
                ? Ticket::query()->where('stato', '<>', 'chiuso')->count()
                : 0,
            'pratiche_ferme' => $canCafPatronato
                ? CafPatronato::query()
                    ->whereIn('esito_id', ['bozza', 'da-gestire'])
                    ->whereDate('created_at', '<=', now()->subDays(7))
                    ->count()
                : 0,
            'visure_mese' => $canVisure
                ? Visura::query()
                    ->whereYear('created_at', $filtroAnno)
                    ->whereMonth('created_at', $filtroMese)
                    ->count()
                : 0,
            'spedizioni_mese' => $canSpedizioni
                ? SpedizioneBrt::query()
                    ->whereYear('created_at', $filtroAnno)
                    ->whereMonth('created_at', $filtroMese)
                    ->count()
                : 0,
            'documenti_mese' => $canDocumentazione
                ? File::query()
                    ->whereYear('created_at', $filtroAnno)
                    ->whereMonth('created_at', $filtroMese)
                    ->count()
                : 0,
        ];

        $alertSupervisore = [
            'caf_bloccate' => $canCafPatronato
                ? CafPatronato::query()
                    ->whereNotNull('motivo_ko')
                    ->where('motivo_ko', '!=', '')
                    ->count()
                : 0,
            'ticket_aperti_oltre_48h' => $canTicket
                ? Ticket::query()
                    ->where('stato', '<>', 'chiuso')
                    ->whereDate('created_at', '<=', now()->subDays(2))
                    ->count()
                : 0,
            'visure_senza_esito' => $canVisure
                ? Visura::query()->whereNull('esito_finale')->count()
                : 0,
        ];

        $serviziAbilitati = collect([
            [
                'enabled' => $canTelefonia,
                'permesso' => 'servizio_contratti_telefonia',
                'titolo' => 'Contratti telefonia',
                'descrizione' => 'Monitoraggio contratti telefonia del periodo selezionato',
                'url' => action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class, 'index']),
                'cta' => 'Apri contratti',
                'kpi_valore' => $kpiSupervisore['contratti_telefonia_mese'],
                'kpi_testo' => 'Pratiche mese',
            ],
            [
                'enabled' => $canEnergia,
                'permesso' => 'servizio_contratti_energia',
                'titolo' => 'Contratti energia',
                'descrizione' => 'Monitoraggio contratti luce e gas del periodo',
                'url' => action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'index']),
                'cta' => 'Apri energia',
                'kpi_valore' => $kpiSupervisore['contratti_energia_mese'],
                'kpi_testo' => 'Pratiche mese',
            ],
            [
                'enabled' => $canCafPatronato,
                'permesso' => 'servizio_caf_patronato',
                'titolo' => 'Caf / Patronato',
                'descrizione' => 'Supervisione pratiche CAF e Patronato',
                'url' => action([\App\Http\Controllers\Backend\CafPatronatoController::class, 'index']),
                'cta' => 'Apri pratiche',
                'kpi_valore' => $kpiSupervisore['pratiche_caf_mese'],
                'kpi_testo' => 'Pratiche mese',
            ],
            [
                'enabled' => $canTicket,
                'permesso' => 'servizio_ticket',
                'titolo' => 'Ticket assistenza',
                'descrizione' => 'Gestione ticket aperti e priorità operative',
                'url' => action([\App\Http\Controllers\Backend\TicketsController::class, 'index']),
                'cta' => 'Apri ticket',
                'kpi_valore' => $kpiSupervisore['ticket_aperti'],
                'kpi_testo' => 'Ticket aperti',
            ],
            [
                'enabled' => $canVisure,
                'permesso' => 'servizio_visure',
                'titolo' => 'Visure',
                'descrizione' => 'Controllo visure in lavorazione e assegnazione',
                'url' => action([\App\Http\Controllers\Backend\VisuraController::class, 'index']),
                'cta' => 'Apri visure',
                'kpi_valore' => $kpiSupervisore['visure_mese'],
                'kpi_testo' => 'Visure mese',
            ],
            [
                'enabled' => $canSpedizioni,
                'permesso' => 'servizio_spedizioni',
                'titolo' => 'Spedizioni BRT',
                'descrizione' => 'Monitoraggio spedizioni gestite nel periodo',
                'url' => action([\App\Http\Controllers\Backend\SpedizioneBrtController::class, 'index']),
                'cta' => 'Apri spedizioni',
                'kpi_valore' => $kpiSupervisore['spedizioni_mese'],
                'kpi_testo' => 'Spedizioni mese',
            ],
            [
                'enabled' => $canDocumentazione,
                'permesso' => 'servizio_documentazione',
                'titolo' => 'Documentazione',
                'descrizione' => 'Archivio documenti caricati nel periodo selezionato',
                'url' => action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'index']),
                'cta' => 'Apri documenti',
                'kpi_valore' => $kpiSupervisore['documenti_mese'],
                'kpi_testo' => 'File mese',
            ],
        ])->where('enabled', true)->values();

        return view('Backend.Dashboard.showSupervisore', [
            'titoloPagina' => $this->salutoDashboard(),
            'mainMenu' => 'dashboard',
            'contrattiTelefonia' => $contrattiTelefonia,
            'contrattiEnergia' => $contrattiEnergia,
            'serviziCafPatronato' => $serviziCafPatronato,
            'ticketRecenti' => $ticketRecenti,
            'conteggioTikets' => $conteggioTikets,
            'kpiSupervisore' => $kpiSupervisore,
            'alertSupervisore' => $alertSupervisore,
            'datiTortaEsiti' => $this->datiTortaEsiti(),
            'elencoMesi' => $this->elencoMesi(),
            'mese' => $mese,
            'filtroAnno' => $filtroAnno,
            'filtroMese' => $filtroMese,
            'canTelefonia' => $canTelefonia,
            'canEnergia' => $canEnergia,
            'canCafPatronato' => $canCafPatronato,
            'canTicket' => $canTicket,
            'canVisure' => $canVisure,
            'canSpedizioni' => $canSpedizioni,
            'canDocumentazione' => $canDocumentazione,
            'serviziAbilitati' => $serviziAbilitati,
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    protected function showAdmin($request)
    {
        $id = Auth::user()->id;
        $this->elencoMesi();


        $mese = $request->input('mese', now()->format('Y_m'));

        list($filtroAnno, $filtroMese) = explode('_', $mese);


        $contratti = ContrattoTelefonia::query()
            ->with('agente')
            ->with('tipoContratto.gestore')
            ->with('esito')
            ->limit(10)
            ->orderByDesc('data')
            ->get();

        $servizi = \App\Models\CafPatronato::query()
            ->with('esito')
            ->with('agente')
            ->with('tipo:id,nome')
            ->withCount('allegati')
            ->withCount('allegatiPerCliente')
            ->limit(10)
            ->orderByDesc('data')
            ->get();

        $tikets = Ticket::query()
            ->with('utente')
            ->orderByDesc('id')
            ->limit(1)
            ->where('stato', '<>', 'chiuso')
            ->get();

        $conteggioTikets = Ticket::groupBy('stato')
            ->select('stato', DB::raw('count(*) as conteggio'))
            ->get()->keyBy('stato');

        $kpiDashboard = [
            'richieste_assistenza_totali' => RichiestaAssistenza::count(),
            'richieste_assistenza_oggi' => RichiestaAssistenza::whereDate('created_at', now())->count(),
            'clienti_assistenza_totali' => ClienteAssistenza::count(),
            'ticket_aperti' => Ticket::where('stato', '<>', 'chiuso')->count(),
        ];

        $alertDashboard = [
            'richieste_senza_credenziali' => RichiestaAssistenza::query()
                ->where(function ($q) {
                    $q->whereNull('nome_utente')->orWhere('nome_utente', '');
                })
                ->orWhere(function ($q) {
                    $q->whereNull('password')->orWhere('password', '');
                })
                ->orWhere(function ($q) {
                    $q->whereNull('pin')->orWhere('pin', '');
                })
                ->count(),
            'clienti_senza_contatti' => ClienteAssistenza::query()
                ->where(function ($q) {
                    $q->whereNull('email')->orWhere('email', '');
                })
                ->orWhere(function ($q) {
                    $q->whereNull('telefono')->orWhere('telefono', '');
                })
                ->count(),
        ];

        $azioniRapide = RichiestaAssistenza::query()
            ->with(['cliente:id,nome,cognome,codice_fiscale,email,telefono', 'prodotto:id,nome'])
            ->where(function ($q) {
                $q->whereNull('nome_utente')->orWhere('nome_utente', '');
            })
            ->orWhere(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            })
            ->orWhere(function ($q) {
                $q->whereNull('pin')->orWhere('pin', '');
            })
            ->latest('id')
            ->limit(8)
            ->get();

        $produzioneMese = $this->produzioneDisponibile()
            ? ProduzioneOperatore::find($id . '_' . $mese)
            : null;

        $chatDashboard = [
            'messaggi_non_letti' => 0,
            'thread_attive' => 0,
            'nuovi_messaggi_oggi' => 0,
        ];

        if (Schema::hasTable('chat_thread_users') && Schema::hasTable('chat_messages')) {
            $threadIds = ChatThreadUser::query()
                ->where('user_id', $id)
                ->pluck('thread_id');

            if ($threadIds->isNotEmpty()) {
                $chatDashboard['messaggi_non_letti'] = ChatThreadUser::conteggioNonLetti($id);

                $chatDashboard['thread_attive'] = ChatMessage::query()
                    ->whereIn('thread_id', $threadIds)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->distinct('thread_id')
                    ->count('thread_id');

                $chatDashboard['nuovi_messaggi_oggi'] = ChatMessage::query()
                    ->whereIn('thread_id', $threadIds)
                    ->where('user_id', '<>', $id)
                    ->whereDate('created_at', now())
                    ->count();
            }
        }


        return view('Backend.Dashboard.showAdmin', [
            'titoloPagina' => $this->salutoDashboard(),
            'mainMenu' => 'dashboard',
            'contratti' => $contratti,
            'servizi' => $servizi,
            'tikets' => $tikets,
            'conteggioTikets' => $conteggioTikets,
            'datiTortaEsiti' => $this->datiTortaEsiti(),
            'produzioneMese' => $produzioneMese,
            'elencoMesi' => $this->elencoMesi(),
            'mese' => $mese,
            'filtroAnno' => $filtroAnno,
            'filtroMese' => $filtroMese,
            'kpiDashboard' => $kpiDashboard,
            'alertDashboard' => $alertDashboard,
            'azioniRapide' => $azioniRapide,
            'chatDashboard' => $chatDashboard,
        ]);

    }


    /**
     * @return array
     */
    protected function elencoMesi()
    {
        $arr = [];
        $dataInizio = now()->startOfMonth();
        $dataFine = Carbon::createFromDate(config('configurazione.primoAnno'), config('configurazione.primoMese'));
        $arr[$dataInizio->format('Y_m')] = 'Questo mese';
        while ($dataInizio->greaterThanOrEqualTo($dataFine)) {
            $dataInizio->subMonthNoOverflow();
            $arr[$dataInizio->format('Y_m')] = ucfirst($dataInizio->translatedFormat('M Y'));
        }
        return $arr;
    }

    protected function showAgente(Request $request)
    {
        $id = Auth::user()->id;

        $periodo = $request->input('periodo', '7d');
        $priorita = $request->input('priorita', '');
        $stato = $request->input('stato', 'aperto');
        $cliente = trim((string)$request->input('cliente', ''));

        $days = match ($periodo) {
            'oggi' => 0,
            '30d' => 30,
            default => 7,
        };

        $dataRiferimento = $days === 0 ? now()->startOfDay() : now()->subDays($days);

        $questoMese = now();
        $mesePrecedente = $questoMese->copy()->subMonths(1);

        $ticketDaPrendereInCaricoQb = Ticket::query()
            ->with(['utente:id,nome,cognome', 'causaleTicket:id,descrizione_causale'])
            ->where('agente_id', $id);

        $visureInAttesaQb = Visura::query()
            ->with(['agente:id,nome,cognome'])
            ->withCount('allegati')
            ->withCount('allegatiPerCliente')
            ->where('agente_id', $id)
            ->whereNull('esito_finale');

        $cafInAttesaQb = CafPatronato::query()
            ->with(['agente:id,nome,cognome'])
            ->withCount('allegati')
            ->withCount('allegatiPerCliente')
            ->where('agente_id', $id)
            ->whereNull('esito_finale');

        if ($days === 0) {
            $ticketDaPrendereInCaricoQb->whereDate('created_at', now());
            $visureInAttesaQb->whereDate('data', now());
            $cafInAttesaQb->whereDate('data', now());
        } else {
            $ticketDaPrendereInCaricoQb->where('created_at', '>=', $dataRiferimento);
            $visureInAttesaQb->where('created_at', '>=', $dataRiferimento);
            $cafInAttesaQb->where('created_at', '>=', $dataRiferimento);
        }

        if ($stato === 'aperto') {
            $ticketDaPrendereInCaricoQb->where('stato', '<>', 'chiuso');
        } elseif ($stato === 'chiuso') {
            $ticketDaPrendereInCaricoQb->where('stato', 'chiuso');
        }

        if ($cliente !== '') {
            $ticketDaPrendereInCaricoQb->where(function ($query) use ($cliente) {
                $query
                    ->where('oggetto', 'like', '%' . $cliente . '%')
                    ->orWhere('uid', 'like', '%' . $cliente . '%')
                    ->orWhereHas('utente', function ($utenteQuery) use ($cliente) {
                        $utenteQuery->where(DB::raw('concat_ws(\' \',nome,cognome)'), 'like', '%' . $cliente . '%');
                    });
            });

            $visureInAttesaQb->where(function ($query) use ($cliente) {
                $query
                    ->where(DB::raw('concat_ws(\' \',nome,cognome,ragione_sociale,partita_iva,codice_fiscale)'), 'like', '%' . $cliente . '%');
            });

            $cafInAttesaQb->where(function ($query) use ($cliente) {
                $query
                    ->where(DB::raw('concat_ws(\' \',nome,cognome,codice_fiscale,email)'), 'like', '%' . $cliente . '%');
            });
        }

        if ($priorita !== '') {
            $ticketDaPrendereInCaricoQb->where(function ($query) use ($priorita) {
                if ($priorita === 'alta') {
                    $query->where('created_at', '<=', now()->subDays(3));
                } elseif ($priorita === 'media') {
                    $query->whereBetween('created_at', [now()->subDays(3), now()->subDay()]);
                } elseif ($priorita === 'bassa') {
                    $query->where('created_at', '>=', now()->subDay());
                }
            });

            $visureInAttesaQb->where(function ($query) use ($priorita) {
                if ($priorita === 'alta') {
                    $query->where('created_at', '<=', now()->subDays(5));
                } elseif ($priorita === 'media') {
                    $query->whereBetween('created_at', [now()->subDays(5), now()->subDays(2)]);
                } elseif ($priorita === 'bassa') {
                    $query->where('created_at', '>=', now()->subDays(2));
                }
            });

            $cafInAttesaQb->where(function ($query) use ($priorita) {
                if ($priorita === 'alta') {
                    $query->where('created_at', '<=', now()->subDays(5));
                } elseif ($priorita === 'media') {
                    $query->whereBetween('created_at', [now()->subDays(5), now()->subDays(2)]);
                } elseif ($priorita === 'bassa') {
                    $query->where('created_at', '>=', now()->subDays(2));
                }
            });
        }

        $ticketDaPrendereInCarico = (clone $ticketDaPrendereInCaricoQb)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $visureInAttesaDocumenti = (clone $visureInAttesaQb)
            ->havingRaw('(allegati_count + allegati_per_cliente_count) = 0')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $cafInAttesaDocumenti = (clone $cafInAttesaQb)
            ->havingRaw('(allegati_count + allegati_per_cliente_count) = 0')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $scadenzeOggi = collect();

        $visureOggi = Visura::query()
            ->where('agente_id', $id)
            ->whereDate('data', now())
            ->whereNull('esito_finale')
            ->limit(6)
            ->get()
            ->map(function ($record) {
                return [
                    'tipo' => 'visura',
                    'id' => $record->id,
                    'cliente' => $record->nominativo(),
                    'data' => $record->data,
                    'apri_url' => action([VisuraController::class, 'edit'], $record->id),
                    'assegna_url' => action([VisuraController::class, 'edit'], $record->id),
                    'completa_url' => action([VisuraController::class, 'edit'], $record->id),
                ];
            });

        $cafOggi = CafPatronato::query()
            ->where('agente_id', $id)
            ->whereDate('data', now())
            ->whereNull('esito_finale')
            ->limit(6)
            ->get()
            ->map(function ($record) {
                return [
                    'tipo' => 'caf',
                    'id' => $record->id,
                    'cliente' => $record->nominativo(),
                    'data' => $record->data,
                    'apri_url' => action([CafPatronatoController::class, 'edit'], $record->id),
                    'assegna_url' => action([CafPatronatoController::class, 'edit'], $record->id),
                    'completa_url' => action([CafPatronatoController::class, 'edit'], $record->id),
                ];
            });

        $scadenzeOggi = $scadenzeOggi->merge($visureOggi)->merge($cafOggi)->take(10);

        $praticheFerme = Visura::query()
            ->where('agente_id', $id)
            ->whereNull('esito_finale')
            ->where('created_at', '<=', now()->subDays(3))
            ->count()
            + CafPatronato::query()
                ->where('agente_id', $id)
                ->whereNull('esito_finale')
                ->where('created_at', '<=', now()->subDays(3))
                ->count();

        $attivitaOggi = Ticket::query()->where('agente_id', $id)->whereDate('created_at', now())->count()
            + Visura::query()->where('agente_id', $id)->whereDate('created_at', now())->count()
            + CafPatronato::query()->where('agente_id', $id)->whereDate('created_at', now())->count();

        $mediaRispostaOre = (float) Ticket::query()
            ->where('agente_id', $id)
            ->where('stato', 'chiuso')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as media_ore')
            ->value('media_ore');

        $monitorOperativo = [
            'trend_7d' => Ticket::query()->where('agente_id', $id)->where('created_at', '>=', now()->subDays(7))->count()
                + Visura::query()->where('agente_id', $id)->where('created_at', '>=', now()->subDays(7))->count()
                + CafPatronato::query()->where('agente_id', $id)->where('created_at', '>=', now()->subDays(7))->count(),
            'trend_30d' => Ticket::query()->where('agente_id', $id)->where('created_at', '>=', now()->subDays(30))->count()
                + Visura::query()->where('agente_id', $id)->where('created_at', '>=', now()->subDays(30))->count()
                + CafPatronato::query()->where('agente_id', $id)->where('created_at', '>=', now()->subDays(30))->count(),
            'pratiche_attenzione' => $praticheFerme,
            'ferme_oltre_x_giorni' => Ticket::query()->where('agente_id', $id)->where('stato', '<>', 'chiuso')->where('created_at', '<=', now()->subDays(2))->count(),
            'tempo_medio_risposta_ore' => round($mediaRispostaOre, 1),
            'soglia_rossa' => 10,
            'soglia_gialla' => 5,
        ];

        $timelineAttivita = collect();

        $timelineTicket = Ticket::query()
            ->where('agente_id', $id)
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'quando' => $record->updated_at,
                    'tipo' => 'Ticket',
                    'descrizione' => $record->oggetto,
                    'prossima_azione' => $record->stato === 'chiuso' ? 'Monitorare eventuali riaperture' : 'Aggiorna ticket',
                    'url' => action([TicketsController::class, 'show'], $record->id),
                ];
            });

        $timelineVisure = Visura::query()
            ->where('agente_id', $id)
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'quando' => $record->updated_at,
                    'tipo' => 'Visura',
                    'descrizione' => $record->nominativo(),
                    'prossima_azione' => $record->esito_finale ? 'Pratica completata' : 'Verifica documenti / aggiorna esito',
                    'url' => action([VisuraController::class, 'edit'], $record->id),
                ];
            });

        $timelineCaf = CafPatronato::query()
            ->where('agente_id', $id)
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'quando' => $record->updated_at,
                    'tipo' => 'CAF',
                    'descrizione' => $record->nominativo(),
                    'prossima_azione' => $record->esito_finale ? 'Pratica completata' : 'Contatta cliente / completa allegati',
                    'url' => action([CafPatronatoController::class, 'edit'], $record->id),
                ];
            });

        $timelineLogins = RegistroLogin::query()
            ->where('user_id', $id)
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->map(function ($record) {
                return [
                    'quando' => $record->created_at,
                    'tipo' => 'Accesso',
                    'descrizione' => 'Accesso piattaforma da ' . ($record->ip ?: 'IP non disponibile'),
                    'prossima_azione' => 'Continua lavorazione attività prioritarie',
                    'url' => action([ProfiloController::class, 'show']),
                ];
            });

        $timelineAttivita = $timelineAttivita
            ->merge($timelineTicket)
            ->merge($timelineVisure)
            ->merge($timelineCaf)
            ->merge($timelineLogins)
            ->sortByDesc('quando')
            ->take(15)
            ->values();

        $heroOperativo = [
            'ticket_aperti_miei' => Ticket::query()->where('agente_id', $id)->where('stato', '<>', 'chiuso')->count(),
            'pratiche_ferme' => $praticheFerme,
            'attivita_oggi' => $attivitaOggi,
        ];

        $chatAttive = ChatThreadUser::conteggioNonLetti($id);
        $chatOperativa = [
            'count' => $chatAttive,
            'url' => action([ChatController::class, 'index']),
        ];

        $filtriGlobali = [
            'periodo' => $periodo,
            'priorita' => $priorita,
            'stato' => $stato,
            'cliente' => $cliente,
        ];

        $produzioneMese = $this->produzioneDisponibile()
            ? ProduzioneOperatore::findByIdAnnoMese($id, $questoMese->year, $questoMese->month)
            : null;

        $produzioneMesePrecedente = $this->produzioneDisponibile()
            ? ProduzioneOperatore::findByIdAnnoMese($id, $mesePrecedente->year, $mesePrecedente->month)
            : null;

        return view('Backend.Dashboard.showAgente', [
            'titoloPagina' => $this->salutoDashboard(),
            'mainMenu' => 'dashboard',
            'record' => Auth::user(),
            'produzioneMese' => $produzioneMese,
            'produzioneMesePrecedente' => $produzioneMesePrecedente,
            'datiBarreOrdini' => $this->datiBarreOrdini(now()->year),
            'heroOperativo' => $heroOperativo,
            'chatOperativa' => $chatOperativa,
            'filtriGlobali' => $filtriGlobali,
            'ticketDaPrendereInCarico' => $ticketDaPrendereInCarico,
            'visureInAttesaDocumenti' => $visureInAttesaDocumenti,
            'cafInAttesaDocumenti' => $cafInAttesaDocumenti,
            'scadenzeOggi' => $scadenzeOggi,
            'monitorOperativo' => $monitorOperativo,
            'timelineAttivita' => $timelineAttivita,

        ]);

    }


    protected function datiTortaEsiti()
    {

        $esitiFinali = ContrattoTelefonia::query()
            ->groupBy('esito_finale')
            ->select('esito_finale', DB::raw('count(*) as conteggio'))
            ->get();

        $arrValori = [];
        $arrTesti = [];
        $arrColori = [];
        $totale = 0;
        foreach ($esitiFinali as $o) {
            $arrValori[] = $o->conteggio;
            $totale += $o->conteggio;
            $arrTesti[] = ucfirst(str_replace('-', ' ', $o->esito_finale));
            $arrColori[] = ContrattoTelefonia::ESITI[$o->esito_finale];
        }

        return [
            'data' => $arrValori,
            'backgroundColor' => $arrColori,
            'labels' => $arrTesti,
            'totale' => $totale
        ];
    }

    public function bulkAction(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_if(!$user, 403);

        $dati = $request->validate([
            'azione' => ['required', 'in:open,assign,complete'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:ticket,visura,caf'],
            'items.*.id' => ['required', 'integer'],
        ]);

        $azione = $dati['azione'];
        $items = collect($dati['items']);

        if ($azione === 'open') {
            $first = $items->first();
            return [
                'success' => true,
                'redirect' => $this->resolveBulkOpenUrl($first['type'], (int)$first['id']),
                'message' => 'Apertura elemento selezionato',
            ];
        }

        $processed = 0;
        $esitoVisuraOkId = EsitoVisura::query()->where('esito_finale', 'ok')->value('id');
        $esitoCafOkId = EsitoCafPatronato::query()->where('esito_finale', 'ok')->value('id');

        $items->each(function ($item) use ($user, $azione, $esitoVisuraOkId, $esitoCafOkId, &$processed) {
            $id = (int)$item['id'];
            $type = $item['type'];

            if ($type === 'ticket') {
                $record = Ticket::query()->where('id', $id)->where('agente_id', $user->id)->first();
                if (!$record) {
                    return;
                }

                if ($azione === 'assign') {
                    $record->agente_id = $user->id;
                    $record->save();
                    $processed++;
                } elseif ($azione === 'complete' && $record->stato !== 'chiuso') {
                    $record->stato = 'chiuso';
                    $record->save();
                    $processed++;
                }
            }

            if ($type === 'visura') {
                $record = Visura::query()->where('id', $id)->where('agente_id', $user->id)->first();
                if (!$record) {
                    return;
                }

                if ($azione === 'assign') {
                    $record->agente_id = $user->id;
                    $record->save();
                    $processed++;
                } elseif ($azione === 'complete' && $esitoVisuraOkId) {
                    $record->esito_id = $esitoVisuraOkId;
                    $record->esito_finale = 'ok';
                    $record->save();
                    $processed++;
                }
            }

            if ($type === 'caf') {
                $record = CafPatronato::query()->where('id', $id)->where('agente_id', $user->id)->first();
                if (!$record) {
                    return;
                }

                if ($azione === 'assign') {
                    $record->agente_id = $user->id;
                    $record->save();
                    $processed++;
                } elseif ($azione === 'complete' && $esitoCafOkId) {
                    $record->esito_id = $esitoCafOkId;
                    $record->esito_finale = 'ok';
                    $record->save();
                    $processed++;
                }
            }
        });

        return [
            'success' => true,
            'processed' => $processed,
            'message' => 'Azione completata su ' . $processed . ' elementi',
        ];
    }

    protected function resolveBulkOpenUrl(string $type, int $id): string
    {
        return match ($type) {
            'ticket' => action([TicketsController::class, 'show'], $id),
            'visura' => action([VisuraController::class, 'edit'], $id),
            'caf' => action([CafPatronatoController::class, 'edit'], $id),
            default => action([DashboardController::class, 'show']),
        };
    }

    protected function datiBarreOrdini($anno)
    {

        $arrOk = [];
        $arrMese = [];

        if (!$this->produzioneDisponibile()) {
            for ($mese = 1; $mese <= 12; $mese++) {
                $arrOk[] = 0;
                $arrMese[] = mese($mese);
            }

            return [
                'arrOk' => $arrOk,
                'arrMese' => $arrMese
            ];
        }

        $produzioneAnno = ProduzioneOperatore::query()
            ->where('user_id', Auth::id())
            ->where('anno', $anno)
            ->get()->keyBy('mese');

        for ($mese = 1; $mese <= 12; $mese++) {
            if (isset($produzioneAnno[$mese])) {
                $arrOk[] = $produzioneAnno[$mese]->importo_totale;
            } else {
                $arrOk[] = 0;
            }
            $arrMese[] = mese($mese);
        }


        return [
            'arrOk' => $arrOk,
            'arrMese' => $arrMese
        ];
    }


}
