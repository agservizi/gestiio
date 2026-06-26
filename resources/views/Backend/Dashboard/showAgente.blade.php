@php
    $container = 'container-fluid';
@endphp
@extends('Backend._layout._main')

@section('toolbar', '')
@section('content')
    @php
        $heroOperativo = $heroOperativo ?? ['ticket_aperti_miei' => 0, 'pratiche_ferme' => 0, 'attivita_oggi' => 0];
        $chatOperativa = $chatOperativa ?? ['count' => 0, 'url' => action([\App\Http\Controllers\Backend\ChatController::class, 'index'])];
        $filtriGlobali = $filtriGlobali ?? ['periodo' => '7d', 'priorita' => '', 'stato' => 'aperto', 'cliente' => ''];
        $ticketDaPrendereInCarico = $ticketDaPrendereInCarico ?? collect();
        $visureInAttesaDocumenti = $visureInAttesaDocumenti ?? collect();
        $cafInAttesaDocumenti = $cafInAttesaDocumenti ?? collect();
        $scadenzeOggi = $scadenzeOggi ?? collect();
        $monitorOperativo = $monitorOperativo ?? [
            'trend_7d' => 0,
            'trend_30d' => 0,
            'pratiche_attenzione' => 0,
            'ferme_oltre_x_giorni' => 0,
            'tempo_medio_risposta_ore' => 0,
            'soglia_rossa' => 10,
            'soglia_gialla' => 5,
        ];
        $timelineAttivita = $timelineAttivita ?? collect();
        $agente = Auth::user()->agente ?? null;
        $wallets = [
            ['label' => 'Servizi', 'value' => (float)($agente->portafoglio_servizi ?? 0), 'class' => 'desk-wallet-servizi'],
            ['label' => 'Spedizioni', 'value' => (float)($agente->portafoglio_spedizioni ?? 0), 'class' => 'desk-wallet-spedizioni'],
            ['label' => 'Visure', 'value' => (float)($agente->portafoglio_visure ?? 0), 'class' => 'desk-wallet-visure'],
        ];
        $saldoPlafond = array_sum(array_column($wallets, 'value'));
        $inAttesaDocumenti = $visureInAttesaDocumenti->map(function($r){
            return [
                'id' => $r->id,
                'record_type' => 'visura',
                'tipo' => 'Visura',
                'cliente' => $r->nominativo(),
                'eta_giorni' => $r->created_at ? $r->created_at->diffInDays(now()) : 0,
                'open_url' => action([\App\Http\Controllers\Backend\VisuraController::class,'edit'],$r->id),
                'assign_url' => action([\App\Http\Controllers\Backend\VisuraController::class,'edit'],$r->id),
                'complete_url' => action([\App\Http\Controllers\Backend\VisuraController::class,'edit'],$r->id),
            ];
        })->merge($cafInAttesaDocumenti->map(function($r){
            return [
                'id' => $r->id,
                'record_type' => 'caf',
                'tipo' => 'CAF',
                'cliente' => $r->nominativo(),
                'eta_giorni' => $r->created_at ? $r->created_at->diffInDays(now()) : 0,
                'open_url' => action([\App\Http\Controllers\Backend\CafPatronatoController::class,'edit'],$r->id),
                'assign_url' => action([\App\Http\Controllers\Backend\CafPatronatoController::class,'edit'],$r->id),
                'complete_url' => action([\App\Http\Controllers\Backend\CafPatronatoController::class,'edit'],$r->id),
            ];
        }));
        $queueTotale = $ticketDaPrendereInCarico->count() + $inAttesaDocumenti->count() + $scadenzeOggi->count();
    @endphp

    <div class="agent-os">
        <section class="agent-hero mb-7">
            <div class="agent-hero-main">
                <div class="agent-kicker">Console agente</div>
                <h1>{{Auth::user()->nome ? 'Ciao '.Auth::user()->nome : 'Dashboard agente'}}</h1>
                <p>Priorità, pratiche e plafond in un’unica scrivania operativa.</p>
                <div class="agent-shortcuts">
                    @can('servizio_ticket')
                        <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'])}}" class="btn btn-sm btn-primary">Ticket</a>
                    @endcan
                    @can('servizio_visure')
                        <a href="{{action([\App\Http\Controllers\Backend\VisuraController::class,'index'])}}" class="btn btn-sm btn-light-primary">Visure</a>
                    @endcan
                    @can('servizio_caf_patronato')
                        <a href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}" class="btn btn-sm btn-light-primary">CAF/Patronato</a>
                    @endcan
                    @can('servizio_contratti_telefonia')
                        <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}" class="btn btn-sm btn-light-primary">Telefonia</a>
                    @endcan
                    @can('servizio_contratti_energia')
                        <a href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'index'])}}" class="btn btn-sm btn-light-primary">Energia</a>
                    @endcan
                    @can('servizio_spedizioni')
                        <a href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'index'])}}" class="btn btn-sm btn-light-primary">Spedizioni</a>
                        <a href="{{action([\App\Http\Controllers\Backend\SpedizioneInpostController::class,'index'])}}" class="btn btn-sm btn-light-primary">InPost</a>
                    @endcan
                    @can('servizio_documentazione')
                        <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index'])}}" class="btn btn-sm btn-light-primary">Documentazione</a>
                    @endcan
                </div>
            </div>
            <div class="agent-command-card">
                <div class="agent-command-actions">
                    <a href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'create'])}}" class="btn btn-primary">Ricarica plafond</a>
                    <a href="{{$chatOperativa['url']}}" class="btn btn-light-primary">
                        Chat interna
                        @if((int)$chatOperativa['count'] > 0)
                            <span class="badge badge-dark ms-2">{{(int)$chatOperativa['count']}}</span>
                        @endif
                    </a>
                </div>
                <div class="agent-command-metrics">
                    <div>
                        <span>Plafond</span>
                        <strong>{!! importo($saldoPlafond, true) !!}</strong>
                    </div>
                    <div>
                        <span>Code</span>
                        <strong>{{number_format($queueTotale)}}</strong>
                    </div>
                    <div>
                        <span>Oggi</span>
                        <strong>{{number_format($heroOperativo['attivita_oggi'])}}</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-5 mb-7">
            <div class="col-xl-3 col-md-6">
                <div class="agent-kpi">
                    <span>Da gestire</span>
                    <strong>{{number_format($queueTotale)}}</strong>
                    <em>Elementi nelle code operative</em>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="agent-kpi">
                    <span>Ticket aperti</span>
                    <strong>{{number_format($heroOperativo['ticket_aperti_miei'])}}</strong>
                    <em>Assegnati a te</em>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="agent-kpi {{$heroOperativo['pratiche_ferme'] >= $monitorOperativo['soglia_gialla'] ? 'agent-kpi-alert' : ''}}">
                    <span>In attenzione</span>
                    <strong>{{number_format($heroOperativo['pratiche_ferme'])}}</strong>
                    <em>Ferme da oltre 3 giorni</em>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="agent-kpi agent-kpi-good">
                    <span>Attività oggi</span>
                    <strong>{{number_format($heroOperativo['attivita_oggi'])}}</strong>
                    <em>Nuove o aggiornate</em>
                </div>
            </div>
        </section>

        <section class="agent-wallet mb-7">
            <div class="agent-wallet-total">
                <span>Plafond disponibile</span>
                <strong class="{{$saldoPlafond > 0 ? 'text-success' : 'text-danger'}}">{!! importo($saldoPlafond, true) !!}</strong>
            </div>
            <div class="row g-4 flex-grow-1">
                @foreach($wallets as $wallet)
                    <div class="col-md-4">
                        <div class="agent-wallet-item {{$wallet['class']}}">
                            <span>{{$wallet['label']}}</span>
                            <strong>{!! importo($wallet['value'], true) !!}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="agent-filter mb-7">
            <form method="GET" action="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'])}}" class="row g-4 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label fw-bold">Periodo</label>
                    <select class="form-select form-select-sm form-select-solid" name="periodo">
                        <option value="oggi" {{$filtriGlobali['periodo']==='oggi'?'selected':''}}>Oggi</option>
                        <option value="7d" {{$filtriGlobali['periodo']==='7d'?'selected':''}}>Ultimi 7 giorni</option>
                        <option value="30d" {{$filtriGlobali['periodo']==='30d'?'selected':''}}>Ultimi 30 giorni</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label fw-bold">Priorità</label>
                    <select class="form-select form-select-sm form-select-solid" name="priorita">
                        <option value="" {{$filtriGlobali['priorita']===''?'selected':''}}>Tutte</option>
                        <option value="alta" {{$filtriGlobali['priorita']==='alta'?'selected':''}}>Alta</option>
                        <option value="media" {{$filtriGlobali['priorita']==='media'?'selected':''}}>Media</option>
                        <option value="bassa" {{$filtriGlobali['priorita']==='bassa'?'selected':''}}>Bassa</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label fw-bold">Stato</label>
                    <select class="form-select form-select-sm form-select-solid" name="stato">
                        <option value="aperto" {{$filtriGlobali['stato']==='aperto'?'selected':''}}>Aperti</option>
                        <option value="chiuso" {{$filtriGlobali['stato']==='chiuso'?'selected':''}}>Chiuse</option>
                        <option value="tutti" {{$filtriGlobali['stato']==='tutti'?'selected':''}}>Tutte</option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-8">
                    <label class="form-label fw-bold">Cliente / ricerca</label>
                    <input type="text" class="form-control form-control-sm form-control-solid" name="cliente" value="{{$filtriGlobali['cliente']}}"
                           placeholder="Nome, P.IVA, CF, oggetto ticket">
                </div>
                <div class="col-lg-2 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Applica</button>
                    <a href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'])}}" class="btn btn-sm btn-light w-100">Reset</a>
                </div>
            </form>
        </section>

        <section class="agent-workbar mb-7">
            <div class="agent-workbar-main">
                <span>Focus rapido</span>
                <div class="agent-workbar-links">
                    <a href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'], ['periodo' => 'oggi', 'stato' => 'aperto'])}}">Oggi</a>
                    <a href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'], ['periodo' => '7d', 'priorita' => 'alta', 'stato' => 'aperto'])}}">Alta priorità</a>
                    <a href="{{action([\App\Http\Controllers\Backend\DashboardController::class,'show'], ['periodo' => '30d', 'stato' => 'aperto'])}}">Ferme</a>
                </div>
            </div>
            <div class="agent-next-action">
                <span>Prossima azione</span>
                <strong>
                    @if($ticketDaPrendereInCarico->count() > 0)
                        Prendi in carico il primo ticket aperto
                    @elseif($inAttesaDocumenti->count() > 0)
                        Richiedi i documenti mancanti
                    @elseif($scadenzeOggi->count() > 0)
                        Controlla le scadenze di oggi
                    @else
                        Nessuna urgenza nel filtro corrente
                    @endif
                </strong>
            </div>
        </section>

        <div class="row g-7">
            <div class="col-xl-8">
                <section class="agent-panel agent-operations-panel mb-7">
                    <div class="agent-panel-head">
                        <div>
                            <h3>Code operative</h3>
                            <p>Apri, assegna o chiudi rapidamente gli elementi selezionati.</p>
                        </div>
                    </div>
                    @include('Backend.Dashboard.partials.agentQueue', [
                        'id' => 'queue-ticket',
                        'title' => 'Ticket da prendere in carico',
                        'empty' => 'Nessun ticket nel filtro corrente.',
                        'tone' => 'primary',
                        'actionHint' => 'Lavora prima i ticket più vecchi o ad alta priorità.',
                        'items' => $ticketDaPrendereInCarico->map(function($ticket) {
                            $etaGiorni = $ticket->created_at ? $ticket->created_at->diffInDays(now()) : 0;
                            $openUrl = action([\App\Http\Controllers\Backend\TicketsController::class,'show'],$ticket->id);
                            return [
                                'type' => 'ticket',
                                'id' => $ticket->id,
                                'title' => $ticket->uidTicket(),
                                'subtitle' => $ticket->oggetto,
                                'badge' => $etaGiorni >= 3 ? 'Alta' : ($etaGiorni >= 1 ? 'Media' : 'Bassa'),
                                'badge_class' => $etaGiorni >= 3 ? 'badge-light-danger' : ($etaGiorni >= 1 ? 'badge-light-warning' : 'badge-light-success'),
                                'meta' => $etaGiorni.' gg',
                                'open_url' => $openUrl,
                                'assign_url' => $openUrl,
                                'complete_url' => $openUrl,
                            ];
                        })
                    ])

                    @include('Backend.Dashboard.partials.agentQueue', [
                        'id' => 'queue-docs',
                        'title' => 'In attesa documenti',
                        'empty' => 'Nessuna pratica in attesa documenti.',
                        'tone' => 'warning',
                        'actionHint' => 'Sollecita i documenti prima che la pratica si fermi.',
                        'items' => $inAttesaDocumenti->map(function($item) {
                            return [
                                'type' => $item['record_type'],
                                'id' => $item['id'],
                                'title' => $item['cliente'],
                                'subtitle' => $item['tipo'],
                                'badge' => $item['eta_giorni'] >= 3 ? 'Ferma' : 'Aperta',
                                'badge_class' => $item['eta_giorni'] >= 3 ? 'badge-light-warning' : 'badge-light-primary',
                                'meta' => $item['eta_giorni'].' gg',
                                'open_url' => $item['open_url'],
                                'assign_url' => $item['assign_url'],
                                'complete_url' => $item['complete_url'],
                            ];
                        })
                    ])

                    @include('Backend.Dashboard.partials.agentQueue', [
                        'id' => 'queue-deadline',
                        'title' => 'Scadenze oggi',
                        'empty' => 'Nessuna scadenza per oggi.',
                        'tone' => 'success',
                        'actionHint' => 'Chiudi o assegna le attività con data odierna.',
                        'items' => $scadenzeOggi->map(function($item) {
                            return [
                                'type' => $item['tipo'],
                                'id' => $item['id'],
                                'title' => $item['cliente'],
                                'subtitle' => strtoupper($item['tipo']),
                                'badge' => 'Oggi',
                                'badge_class' => 'badge-light-info',
                                'meta' => $item['data']?->format('d/m/Y'),
                                'open_url' => $item['apri_url'],
                                'assign_url' => $item['assegna_url'],
                                'complete_url' => $item['completa_url'],
                            ];
                        })
                    ])
                </section>
            </div>

            <div class="col-xl-4 agent-side-column">
                <section class="agent-panel mb-7">
                    <div class="agent-panel-head">
                        <div>
                            <h3>Monitor operativo</h3>
                            <p>Andamento personale e segnali da controllare.</p>
                        </div>
                    </div>
                    <div class="agent-monitor">
                        <div class="agent-signal agent-signal-primary">
                            <span>Trend 7 giorni</span>
                            <strong>{{number_format($monitorOperativo['trend_7d'])}}</strong>
                        </div>
                        <div class="agent-signal">
                            <span>Trend 30 giorni</span>
                            <strong>{{number_format($monitorOperativo['trend_30d'])}}</strong>
                        </div>
                        <div class="agent-signal {{$monitorOperativo['pratiche_attenzione'] > 0 ? 'agent-signal-warning' : ''}}">
                            <span>Pratiche in attenzione</span>
                            <strong>{{number_format($monitorOperativo['pratiche_attenzione'])}}</strong>
                        </div>
                        <div class="agent-signal {{$monitorOperativo['ferme_oltre_x_giorni'] > 0 ? 'agent-signal-danger' : ''}}">
                            <span>Ferme da oltre soglia</span>
                            <strong>{{number_format($monitorOperativo['ferme_oltre_x_giorni'])}}</strong>
                        </div>
                        <div class="agent-response-time">
                            <span>Tempo medio risposta</span>
                            <strong>{{$monitorOperativo['tempo_medio_risposta_ore']}} ore</strong>
                            <em>{{$monitorOperativo['tempo_medio_risposta_ore'] <= 24 ? 'Entro soglia operativa' : 'Da riportare sotto controllo'}}</em>
                        </div>
                    </div>
                </section>

                <section class="agent-panel agent-timeline-panel">
                    <div class="agent-panel-head">
                        <div>
                            <h3>Attività recente</h3>
                            <p>Ultimi aggiornamenti collegati al tuo lavoro.</p>
                        </div>
                        @if($timelineAttivita->count() > 0)
                            <div class="agent-panel-count">{{$timelineAttivita->count()}}</div>
                        @endif
                    </div>
                    @if($timelineAttivita->isEmpty())
                        <div class="agent-empty">
                            <strong>Nessuna attività</strong>
                            <span>Non ci sono aggiornamenti recenti nel filtro corrente.</span>
                        </div>
                    @else
                        <div class="agent-timeline" data-timeline-page-size="4">
                            @foreach($timelineAttivita as $itemIndex => $item)
                                <a href="{{$item['url']}}" class="agent-timeline-item" data-timeline-index="{{$itemIndex}}">
                                    <span>{{$item['quando']?->format('d/m H:i')}}</span>
                                    <strong>{{$item['tipo']}} - {{$item['descrizione']}}</strong>
                                    <em>{{$item['prossima_azione']}}</em>
                                </a>
                            @endforeach
                        </div>
                        <div class="agent-timeline-pagination">
                            <button type="button" class="btn btn-sm btn-light-primary" data-timeline-prev>Indietro</button>
                            <span data-timeline-status>1 / 1</span>
                            <button type="button" class="btn btn-sm btn-light-primary" data-timeline-next>Avanti</button>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection
@push('customCss')
    <style>
        .agent-os {
            --agent-border: #dfe8f3;
            --agent-soft: #f5f8fc;
            --agent-text: #101827;
            --agent-muted: #69758d;
            --agent-blue: #009ef7;
            --agent-green: #34b978;
            --agent-amber: #d99a00;
            --agent-ink: #111827;
            padding: .15rem;
        }

        .agent-hero,
        .agent-wallet,
        .agent-filter,
        .agent-panel,
        .agent-kpi {
            border: 1px solid var(--agent-border);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(16, 24, 39, .055);
        }

        .agent-hero {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            overflow: hidden;
            padding: 2rem;
            border-color: rgba(0, 158, 247, .18);
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .98) 0%, rgba(247, 251, 255, .98) 50%, rgba(247, 255, 250, .96) 100%);
        }

        .agent-hero::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            background: linear-gradient(90deg, var(--agent-blue), var(--agent-green), var(--agent-amber));
        }

        .agent-hero-main {
            position: relative;
            z-index: 1;
            min-width: 0;
            flex: 1 1 auto;
        }

        .agent-kicker {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            padding: .25rem .65rem;
            border: 1px solid rgba(0, 158, 247, .18);
            border-radius: 8px;
            color: var(--agent-blue);
            background: rgba(0, 158, 247, .08);
            font-size: .75rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .agent-hero h1 {
            margin: .8rem 0 .35rem;
            color: var(--agent-text);
            font-size: clamp(1.6rem, 2.2vw, 2.2rem);
            font-weight: 900;
            line-height: 1.05;
        }

        .agent-hero p,
        .agent-panel-head p {
            margin: 0;
            color: var(--agent-muted);
        }

        .agent-command-card {
            position: relative;
            z-index: 1;
            flex: 0 0 390px;
            display: grid;
            gap: .9rem;
            padding: 1rem;
            border: 1px solid rgba(16, 24, 39, .08);
            border-radius: 8px;
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 22px 60px rgba(16, 24, 39, .11);
            backdrop-filter: blur(12px);
        }

        .agent-command-actions {
            display: flex;
            gap: .65rem;
        }

        .agent-command-actions .btn {
            flex: 1 1 0;
            min-height: 42px;
            font-weight: 800;
        }

        .agent-command-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .65rem;
        }

        .agent-command-metrics div {
            min-height: 72px;
            padding: .85rem;
            border: 1px solid #e9f1f8;
            border-radius: 8px;
            background: #f8fbff;
        }

        .agent-command-metrics span {
            display: block;
            color: var(--agent-muted);
            font-size: .72rem;
            font-weight: 850;
            text-transform: uppercase;
        }

        .agent-command-metrics strong {
            display: block;
            margin-top: .25rem;
            color: var(--agent-text);
            font-size: 1.05rem;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
        }

        .agent-shortcuts {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-top: 1.25rem;
        }

        .agent-shortcuts .btn {
            min-width: 94px;
            min-height: 38px;
            border: 1px solid rgba(0, 158, 247, .14);
            box-shadow: 0 10px 26px rgba(0, 158, 247, .055);
            font-weight: 800;
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }

        .agent-shortcuts .btn:hover,
        .agent-command-actions .btn:hover,
        .agent-workbar-links a:hover {
            transform: translateY(-1px);
        }

        .agent-kpi {
            position: relative;
            overflow: hidden;
            height: 100%;
            padding: 1.35rem;
            border-left: 0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .agent-kpi::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--agent-blue);
        }

        .agent-kpi span,
        .agent-wallet-total span,
        .agent-wallet-item span {
            display: block;
            color: var(--agent-muted);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .agent-kpi strong,
        .agent-wallet-total strong,
        .agent-wallet-item strong {
            display: block;
            margin-top: .2rem;
            color: var(--agent-text);
            font-size: 1.55rem;
            font-weight: 850;
            font-variant-numeric: tabular-nums;
        }

        .agent-kpi em {
            display: block;
            margin-top: .25rem;
            color: var(--agent-muted);
            font-style: normal;
        }

        .agent-kpi-alert {
            border-color: rgba(241, 65, 108, .22);
        }

        .agent-kpi-alert::before {
            background: #f1416c;
        }

        .agent-kpi-good {
            border-color: rgba(52, 185, 120, .22);
        }

        .agent-kpi-good::before {
            background: var(--agent-green);
        }

        .agent-wallet {
            display: flex;
            gap: 1rem;
            align-items: stretch;
            padding: 1.25rem;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        }

        .agent-wallet-total,
        .agent-wallet-item {
            border-radius: 8px;
            border: 1px solid var(--agent-border);
            background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
            padding: 1rem 1.15rem;
        }

        .agent-wallet-total {
            width: 260px;
        }

        .agent-wallet-item {
            height: 100%;
            border-left: 4px solid #009ef7;
        }

        .desk-wallet-spedizioni {
            border-left-color: #50cd89;
        }

        .desk-wallet-visure {
            border-left-color: #7239ea;
        }

        .agent-filter {
            padding: 1.25rem;
            box-shadow: 0 12px 30px rgba(16, 24, 39, .04);
        }

        .agent-workbar {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 1rem;
        }

        .agent-workbar-main,
        .agent-next-action {
            flex: 1;
            padding: 1rem 1.25rem;
            border: 1px solid var(--agent-border);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(16, 24, 39, .04);
        }

        .agent-workbar-main > span,
        .agent-next-action span {
            display: block;
            margin-bottom: .5rem;
            color: var(--agent-muted);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .agent-workbar-links {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .agent-workbar-links a {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: .45rem .8rem;
            border: 1px solid #dfeaf5;
            border-radius: 8px;
            color: #162033;
            background: #f8fbff;
            font-weight: 700;
        }

        .agent-workbar-links a:hover {
            color: #009ef7;
            border-color: #bfe5ff;
            background: #eef8ff;
        }

        .agent-next-action strong {
            display: block;
            color: var(--agent-text);
            font-size: 1rem;
        }

        .agent-panel {
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        }

        .agent-panel-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.45rem 1.6rem;
            border-bottom: 1px solid var(--agent-border);
            background: rgba(255, 255, 255, .75);
        }

        .agent-panel-head h3 {
            margin: 0 0 .25rem;
            color: var(--agent-text);
            font-size: 1.1rem;
            font-weight: 800;
        }

        .agent-panel-count {
            min-width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #009ef7;
            background: #eef8ff;
            font-weight: 850;
        }

        .agent-operations-panel {
            height: calc(100% - 1.75rem);
        }

        .agent-side-column {
            display: flex;
            flex-direction: column;
        }

        .agent-timeline-panel {
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
            flex-direction: column;
        }

        .agent-queue {
            padding: 1rem 1.5rem 1.35rem;
            border-bottom: 1px solid var(--agent-border);
            background: transparent;
        }

        .agent-queue:last-child {
            border-bottom: 0;
        }

        .agent-queue-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .agent-queue-title {
            display: flex;
            gap: .85rem;
            align-items: flex-start;
            min-width: 0;
        }

        .agent-queue-mark {
            width: 6px;
            height: 42px;
            border-radius: 8px;
            background: #009ef7;
            flex: 0 0 auto;
        }

        .agent-queue-warning .agent-queue-mark {
            background: #ffc700;
        }

        .agent-queue-success .agent-queue-mark {
            background: #50cd89;
        }

        .agent-queue-top h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
        }

        .agent-queue-top span {
            color: var(--agent-muted);
        }

        .agent-queue-status {
            min-width: 90px;
            padding: .65rem .8rem;
            border: 1px solid #e9f1f8;
            border-radius: 8px;
            text-align: center;
            background: #ffffff;
            box-shadow: 0 10px 22px rgba(16, 24, 39, .045);
        }

        .agent-queue-status strong,
        .agent-queue-status span {
            display: block;
        }

        .agent-queue-status strong {
            color: var(--agent-text);
            font-size: 1.15rem;
            line-height: 1;
        }

        .agent-queue-status span {
            color: var(--agent-muted);
            font-size: .75rem;
            font-weight: 700;
        }

        .agent-bulkbar {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: .85rem;
            padding: .7rem .85rem;
            border: 1px solid #e9f1f8;
            border-radius: 8px;
            background: rgba(248, 251, 255, .9);
        }

        .agent-check-all {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin: 0;
            color: var(--agent-text);
            font-weight: 700;
        }

        .agent-selected-counter {
            color: var(--agent-muted);
            font-weight: 700;
        }

        .agent-queue-actions {
            margin-left: auto;
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .agent-list {
            display: grid;
            gap: .75rem;
        }

        .agent-row {
            display: grid;
            grid-template-columns: 28px minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #eef3f8;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 8px 22px rgba(16, 24, 39, .035);
            transition: border-color .15s ease, background .15s ease, transform .15s ease, box-shadow .15s ease;
        }

        .agent-row:hover,
        .agent-row.is-selected,
        .agent-row:has(.bulk-check-item:checked) {
            border-color: #bfe5ff;
            background: #f5fbff;
            box-shadow: 0 14px 32px rgba(0, 158, 247, .08);
            transform: translateY(-1px);
        }

        .agent-row-title {
            min-width: 0;
        }

        .agent-row-title strong {
            display: block;
            color: var(--agent-text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .agent-row-title span {
            display: block;
            color: var(--agent-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .agent-row-signals {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .5rem;
            min-width: 120px;
        }

        .agent-row-signals span:last-child {
            color: var(--agent-muted);
            font-weight: 700;
            white-space: nowrap;
        }

        .agent-row-actions {
            display: flex;
            justify-content: flex-end;
        }

        .agent-empty {
            display: grid;
            gap: .25rem;
            padding: 1.1rem 1.25rem;
            border: 1px dashed var(--agent-border);
            border-radius: 8px;
            background: rgba(245, 248, 252, .82);
        }

        .agent-empty strong {
            color: var(--agent-text);
        }

        .agent-empty span {
            color: var(--agent-muted);
        }

        .agent-monitor {
            display: grid;
            gap: .75rem;
            padding: 1.25rem 1.5rem 1.5rem;
        }

        .agent-signal,
        .agent-response-time {
            padding: .95rem 1rem;
            border: 1px solid #eef3f8;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 8px 22px rgba(16, 24, 39, .035);
        }

        .agent-signal {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            border-left: 4px solid #dfeaf5;
        }

        .agent-signal-primary {
            border-left-color: #009ef7;
        }

        .agent-signal-warning {
            border-left-color: #ffc700;
        }

        .agent-signal-danger {
            border-left-color: #f1416c;
        }

        .agent-signal span,
        .agent-response-time span,
        .agent-response-time em {
            color: var(--agent-muted);
        }

        .agent-signal strong,
        .agent-response-time strong {
            color: var(--agent-text);
            font-variant-numeric: tabular-nums;
        }

        .agent-response-time strong,
        .agent-response-time em {
            display: block;
        }

        .agent-response-time strong {
            margin: .25rem 0;
            font-size: 1.25rem;
        }

        .agent-response-time em {
            font-style: normal;
        }

        .agent-timeline {
            display: grid;
            gap: .75rem;
            padding: 1.25rem 1.5rem 1.5rem;
            flex: 1 1 auto;
            align-content: start;
        }

        .agent-timeline-item {
            display: block;
            min-height: 86px;
            padding: .85rem 1rem;
            border: 1px solid #eef3f8;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 8px 22px rgba(16, 24, 39, .035);
            transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease;
        }

        .agent-timeline-item:hover {
            border-color: #bfe5ff;
            transform: translateY(-1px);
            box-shadow: 0 14px 32px rgba(0, 158, 247, .08);
        }

        .agent-timeline-item span,
        .agent-timeline-item em {
            display: block;
            color: var(--agent-muted);
            font-size: .8rem;
            font-style: normal;
        }

        .agent-timeline-item strong {
            display: block;
            margin: .25rem 0;
            color: var(--agent-text);
            line-height: 1.25;
        }

        .agent-timeline-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-top: auto;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--agent-border);
            background: rgba(255, 255, 255, .78);
        }

        .agent-timeline-pagination span {
            color: var(--agent-muted);
            font-weight: 800;
            white-space: nowrap;
        }

        @media (max-width: 991.98px) {
            .agent-hero,
            .agent-wallet {
                flex-direction: column;
                align-items: stretch;
            }

            .agent-command-card {
                flex-basis: auto;
            }

            .agent-command-actions {
                flex-direction: column;
            }

            .agent-command-metrics {
                grid-template-columns: 1fr;
            }

            .agent-wallet-total {
                width: auto;
            }

            .agent-workbar,
            .agent-bulkbar {
                flex-direction: column;
                align-items: stretch;
            }

            .agent-operations-panel {
                height: auto;
            }

            .agent-queue-actions {
                margin-left: 0;
            }

            .agent-row {
                grid-template-columns: 28px minmax(0, 1fr);
            }

            .agent-row-signals,
            .agent-row-actions {
                grid-column: 2;
                justify-self: start;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .agent-shortcuts .btn,
            .agent-command-actions .btn,
            .agent-workbar-links a,
            .agent-row,
            .agent-timeline-item {
                transition: none;
            }

            .agent-shortcuts .btn:hover,
            .agent-command-actions .btn:hover,
            .agent-workbar-links a:hover,
            .agent-row:hover,
            .agent-timeline-item:hover {
                transform: none;
            }
        }
    </style>
@endpush
@push('customScript')
    <script>
        const bulkActionUrl = @json(action([\App\Http\Controllers\Backend\DashboardController::class, 'bulkAction']));

        $(function () {
            function refreshQueueSelection(queue) {
                const selected = queue.find('.bulk-check-item:checked').length;
                const total = queue.find('.bulk-check-item').length;
                queue.find('.agent-selected-counter strong').text(selected);
                queue.find('.bulk-check-all').prop('checked', total > 0 && selected === total);
                queue.find('.agent-row').each(function () {
                    const row = $(this);
                    row.toggleClass('is-selected', row.find('.bulk-check-item').is(':checked'));
                });
            }

            $(document).on('change', '.bulk-check-all', function () {
                const panel = $(this).closest('.agent-queue');
                panel.find('.bulk-check-item').prop('checked', $(this).is(':checked'));
                refreshQueueSelection(panel);
            });

            $(document).on('change', '.bulk-check-item', function () {
                refreshQueueSelection($(this).closest('.agent-queue'));
            });

            $(document).on('click', '[data-bulk-action]', function () {
                const actionType = $(this).data('bulk-action');
                const target = $(this).data('bulk-target');
                const rows = $(target).find('.bulk-check-item:checked').closest('.agent-row');

                if (!rows.length) {
                    Swal.fire('Seleziona almeno un elemento', 'Per usare l\'azione rapida seleziona almeno una riga.', 'warning');
                    return;
                }

                const items = [];
                rows.each(function () {
                    const row = $(this);
                    const type = row.data('record-type');
                    const id = row.data('record-id');
                    if (type && id) {
                        items.push({type: type, id: parseInt(id)});
                    }
                });

                if (!items.length) {
                    Swal.fire('Azione non disponibile', 'Nessun elemento valido selezionato.', 'info');
                    return;
                }

                $.ajax({
                    url: bulkActionUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        azione: actionType,
                        items: items
                    },
                    success: function (response) {
                        if (!response || !response.success) {
                            Swal.fire('Errore', 'Non è stato possibile completare l\'azione.', 'error');
                            return;
                        }

                        if (response.redirect) {
                            window.location.href = response.redirect;
                            return;
                        }

                        Swal.fire('Operazione completata', response.message || 'Azione eseguita.', 'success').then(function () {
                            window.location.reload();
                        });
                    },
                    error: function () {
                        Swal.fire('Errore', 'Si è verificato un errore durante l\'azione bulk.', 'error');
                    }
                });
            });

            $('.agent-timeline[data-timeline-page-size]').each(function () {
                const timeline = $(this);
                const panel = timeline.closest('.agent-timeline-panel');
                const items = timeline.find('.agent-timeline-item');
                const pageSize = parseInt(timeline.data('timeline-page-size'), 10) || 4;
                const totalPages = Math.max(1, Math.ceil(items.length / pageSize));
                let page = 1;

                function renderTimelinePage() {
                    const start = (page - 1) * pageSize;
                    const end = start + pageSize;
                    items.each(function (index) {
                        $(this).toggle(index >= start && index < end);
                    });

                    panel.find('[data-timeline-status]').text(page + ' / ' + totalPages);
                    panel.find('[data-timeline-prev]').prop('disabled', page <= 1);
                    panel.find('[data-timeline-next]').prop('disabled', page >= totalPages);
                    panel.find('.agent-timeline-pagination').toggle(items.length > pageSize);
                }

                panel.find('[data-timeline-prev]').on('click', function () {
                    if (page > 1) {
                        page--;
                        renderTimelinePage();
                    }
                });

                panel.find('[data-timeline-next]').on('click', function () {
                    if (page < totalPages) {
                        page++;
                        renderTimelinePage();
                    }
                });

                renderTimelinePage();
            });
        });
    </script>
@endpush
