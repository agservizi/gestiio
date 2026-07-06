@extends('Backend._layout._main')
@section('toolbar')
    <select name="mese" id="mese" data-control="select2" data-hide-search="true"
            class="form-select form-select-solid form-select-sm fw-bolder w-200px">
        @foreach($elencoMesi as $key=>$value)
            <option value="{{$key}}" @selected($key==$mese)>{{$value}}</option>
        @endforeach
    </select>
@endsection

@section('content')
    @php
        $kpiDashboard = $kpiDashboard ?? [
            'richieste_assistenza_totali' => 0,
            'richieste_assistenza_oggi' => 0,
            'clienti_assistenza_totali' => 0,
            'ticket_aperti' => 0,
        ];
        $alertDashboard = $alertDashboard ?? [
            'richieste_senza_credenziali' => 0,
            'clienti_senza_contatti' => 0,
        ];
        $azioniRapide = $azioniRapide ?? collect();
        $produzioneConteggio = (int)($produzioneMese?->conteggio_ordini ?? 0);
        $produzioneInLavorazione = (int)($produzioneMese?->conteggio_ordini_in_lavorazione ?? 0);
        $percentualeProduzione = percentuale($produzioneInLavorazione, $produzioneConteggio);
        $guadagno = \App\Models\GuadagnoAgenzia::firstOrNew(['mese' => $filtroMese, 'anno' => $filtroAnno]);
        $percentualeUtile = percentuale($guadagno->utile, $guadagno->entrate);
        $ticketAperti = (int) data_get($conteggioTikets, 'aperto.conteggio', 0) + (int) data_get($conteggioTikets, 'in_lavorazione.conteggio', 0);
        $ticketChiusi = (int) data_get($conteggioTikets, 'chiuso.conteggio', 0);
        $chatDashboard = $chatDashboard ?? [
            'messaggi_non_letti' => 0,
            'thread_attive' => 0,
            'nuovi_messaggi_oggi' => 0,
        ];
        $controlRoomAdmin = $controlRoomAdmin ?? [
            'code' => [],
            'alert' => [],
            'economico' => ['entrate' => 0, 'uscite' => 0, 'utile' => 0, 'assistenze_5' => 0, 'ricariche_plafond' => 0, 'produzione_agenti' => 0],
            'audit' => collect(),
            'azioni' => [],
            'salute' => [],
        ];
    @endphp

    <section class="admin-control-room mb-5 mb-xl-10">
        <div class="admin-control-hero">
            <div>
                <span class="admin-control-kicker">Operazioni</span>
                <h2>Dashboard admin</h2>
                <p>Code, alert e numeri del mese. Le azioni importanti restano qui.</p>
            </div>
            <div class="admin-control-actions">
                @foreach($controlRoomAdmin['azioni'] as $azione)
                    <a href="{{$azione['url']}}" class="btn btn-sm {{$loop->first ? 'btn-primary' : 'btn-light-primary'}}">{{$azione['label']}}</a>
                @endforeach
            </div>
        </div>

        @include('Backend.Dashboard.partials.adminKpiCards')

        @include('Backend.Dashboard.partials.adminOverviewCards')

        <div class="admin-control-grid">
            <div class="admin-control-panel admin-control-panel-wide">
                <div class="admin-control-head">
                    <div>
                        <h3>Code</h3>
                        <span>Aperti o da controllare.</span>
                    </div>
                </div>
                <div class="admin-queue-grid">
                    @foreach($controlRoomAdmin['code'] as $item)
                        <a href="{{$item['url']}}" class="admin-queue-card admin-queue-{{$item['tone']}}">
                            <span>{{$item['label']}}</span>
                            <strong>{{number_format((int)$item['count'])}}</strong>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="admin-control-panel">
                <div class="admin-control-head">
                    <div>
                        <h3>Alert</h3>
                        <span>Prima passa da qui.</span>
                    </div>
                </div>
                <div class="admin-alert-list">
                    @foreach($controlRoomAdmin['alert'] as $alert)
                        <a href="{{$alert['url']}}" class="admin-alert-row admin-alert-{{$alert['severity']}}">
                            <span>{{$alert['label']}}</span>
                            <strong>{{number_format((int)$alert['count'])}}</strong>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="admin-control-panel">
                <div class="admin-control-head">
                    <div>
                        <h3>Economico mese</h3>
                        <span>Il mese in numeri.</span>
                    </div>
                </div>
                <div class="admin-money-main">
                    <span>Utile</span>
                    <strong>{!! importo($controlRoomAdmin['economico']['utile'], true) !!}</strong>
                </div>
                <div class="admin-money-list">
                    <div><span>Entrate</span><strong>{!! importo($controlRoomAdmin['economico']['entrate'], true) !!}</strong></div>
                    <div><span>Uscite</span><strong>{!! importo($controlRoomAdmin['economico']['uscite'], true) !!}</strong></div>
                    <div><span>Assistenze</span><strong>{!! importo($controlRoomAdmin['economico']['assistenze_5'], true) !!}</strong></div>
                    <div><span>Ricariche plafond</span><strong>{!! importo($controlRoomAdmin['economico']['ricariche_plafond'], true) !!}</strong></div>
                </div>
            </div>

            <div class="admin-control-panel">
                <div class="admin-control-head">
                    <div>
                        <h3>Audit documenti</h3>
                        <span>Chi ha toccato cosa.</span>
                    </div>
                    <a href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class, 'index'])}}" class="btn btn-sm btn-light-primary">Apri</a>
                </div>
                <div class="admin-audit-list">
                    @forelse($controlRoomAdmin['audit'] as $log)
                        <div class="admin-audit-row">
                            <strong>{{$log->utente?->nominativo() ?? 'Utente'}}</strong>
                            <span>{{ucfirst((string)$log->azione)}} - {{$log->filename_originale ?: basename((string)$log->path_filename)}}</span>
                            <em>{{$log->created_at?->diffForHumans()}}</em>
                        </div>
                    @empty
                        <div class="admin-empty">Nessun audit recente.</div>
                    @endforelse
                </div>
            </div>

            <div class="admin-control-panel">
                <div class="admin-control-head">
                    <div>
                        <h3>Salute sistema</h3>
                        <span>App, backup e tunnel.</span>
                    </div>
                    <a href="{{action([\App\Http\Controllers\Backend\RegistriController::class, 'index'], 'backup-db')}}" class="btn btn-sm btn-light-primary">Backup</a>
                </div>
                <div class="admin-health-list">
                    @foreach($controlRoomAdmin['salute'] as $check)
                        <div class="admin-health-row">
                            <span>{{$check['label']}}</span>
                            <strong class="badge badge-light-{{$check['tone']}}">{{$check['value']}}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>



    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-xxl-6">
            <div class="card card-flush h-md-100">
                <div class="card-header border-0 pt-5 pb-2">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M5 4C5 3.44772 5.44772 3 6 3H18C18.5523 3 19 3.44772 19 4V20L12 17L5 20V4Z" fill="currentColor"/>
                                <path d="M8 8C8 7.44772 8.44772 7 9 7H15C15.5523 7 16 7.44772 16 8C16 8.55228 15.5523 9 15 9H9C8.44772 9 8 8.55228 8 8ZM8 12C8 11.4477 8.44772 11 9 11H13C13.5523 11 14 11.4477 14 12C14 12.5523 13.5523 13 13 13H9C8.44772 13 8 12.5523 8 12Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <h3 class="card-label fw-bold fs-3 mb-0">Contratti recenti</h3>
                    </div>
                    <div class="card-toolbar">
                        <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax" href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'create'])}}">
                            <span class="d-md-none">+</span><span class="d-none d-md-block">Nuovo contratto</span>
                        </a>
                    </div>
                </div>
                <div class="card-body card-scroll py-3">
                    <div class="bg-light-primary rounded p-3 mb-4 d-flex justify-content-between align-items-center">
                        <span class="text-muted fw-semibold">Ultimi inserimenti</span>
                        <span class="badge badge-light-primary fw-bolder">{{ count($contratti ?? []) }}</span>
                    </div>
                    <div class="table-responsive border rounded bg-light px-2 py-1">
                        <table class="table table-row-dashed align-middle gs-0 gy-3 mb-0">
                            <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-8">
                                <th class="ps-3">Data</th>
                                <th class="min-w-150px">Agente</th>
                                <th class="min-w-140px">Prodotto</th>
                                <th class="min-w-120px text-center">Esito</th>
                                <th class="min-w-100px text-end pe-3">Azioni</th>
                            </tr>
                            </thead>
                            <tbody class="text-gray-700 fw-semibold">
                            @include('Backend.Dashboard.admin.contratti',['records'=>$contratti])
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6">
            <div class="card card-flush h-md-100">
                <div class="card-header border-0 pt-5 pb-2">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M4 7C4 5.89543 4.89543 5 6 5H18C19.1046 5 20 5.89543 20 7V17C20 18.1046 19.1046 19 18 19H6C4.89543 19 4 18.1046 4 17V7Z" fill="currentColor"/>
                                <path d="M7 9C7 8.44772 7.44772 8 8 8H16C16.5523 8 17 8.44772 17 9C17 9.55228 16.5523 10 16 10H8C7.44772 10 7 9.55228 7 9ZM7 13C7 12.4477 7.44772 12 8 12H13C13.5523 12 14 12.4477 14 13C14 13.5523 13.5523 14 13 14H8C7.44772 14 7 13.5523 7 13Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <h3 class="card-label fw-bold fs-3 mb-0">Caf / Patronato</h3>
                    </div>
                    <div class="card-toolbar">
                        <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax" href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'create'])}}">
                            <span class="d-md-none">+</span><span class="d-none d-md-block">Nuova pratica caf patronato</span>
                        </a>
                    </div>
                </div>
                <div class="card-body card-scroll py-3">
                    <div class="bg-light-primary rounded p-3 mb-4 d-flex justify-content-between align-items-center">
                        <span class="text-muted fw-semibold">Pratiche recenti</span>
                        <span class="badge badge-light-primary fw-bolder">{{ count($servizi ?? []) }}</span>
                    </div>
                    <div class="table-responsive border rounded bg-light px-2 py-1">
                        <table class="table table-row-dashed align-middle gs-0 gy-3 mb-0" id="tabella-elenco">
                            <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-8">
                                <th class="ps-3">Data</th>
                                <th>Tipo pratica</th>
                                <th>Esito</th>
                                <th>Nominativo</th>
                                <th class="text-center pe-3">Azioni</th>
                            </tr>
                            </thead>
                            <tbody class="text-gray-700 fw-semibold">
                            @include('Backend.Dashboard.admin.cafPatronato',['records' => $servizi,'puoModificareEsito'=>\App\Models\CafPatronato::puoModificareEsito(),'puoModificare'=>\App\Models\CafPatronato::puoModificare(),'controller'=>\App\Http\Controllers\Backend\CafPatronatoController::class])
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-12">
            <div class="card card-flush h-100">
                <div class="card-header border-0 pt-5 pb-2">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M12 2L3 6V12C3 17.55 6.84 22.74 12 24C17.16 22.74 21 17.55 21 12V6L12 2Z" fill="currentColor"/>
                                <path d="M12 7C12.5523 7 13 7.44772 13 8V13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13V8C11 7.44772 11.4477 7 12 7ZM12 17C12.5523 17 13 16.5523 13 16C13 15.4477 12.5523 15 12 15C11.4477 15 11 15.4477 11 16C11 16.5523 11.4477 17 12 17Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="card-label fw-bold fs-3 mb-0">Priorità assistenza</h3>
                            <div class="text-muted fw-semibold fs-7">Richieste con credenziali mancanti</div>
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <span class="badge badge-light-danger fw-bolder">{{ count($azioniRapide ?? []) }}</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @if($azioniRapide->isEmpty())
                        <div class="text-muted py-5">Nessuna priorità operativa al momento.</div>
                    @else
                        <div class="table-responsive border rounded bg-light px-2 py-1">
                            <table class="table table-row-dashed align-middle gs-0 gy-3 mb-0">
                                <thead>
                                <tr class="fw-bold text-muted text-uppercase fs-8">
                                    <th class="ps-3">ID</th>
                                    <th>Cliente</th>
                                    <th>Prodotto</th>
                                    <th>Creato il</th>
                                    <th class="text-end pe-3">Azione</th>
                                </tr>
                                </thead>
                                <tbody class="text-gray-700 fw-semibold">
                                @foreach($azioniRapide as $azione)
                                    <tr>
                                        <td>{{ $azione->id }}</td>
                                        <td>{{ $azione->cliente?->nominativo() ?? 'Cliente non associato' }}</td>
                                        <td>{{ $azione->prodotto->nome ?? 'Prodotto non associato' }}</td>
                                        <td>{{ $azione->created_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ action([\App\Http\Controllers\Backend\RichiestaAssistenzaController::class, 'edit'], $azione->id) }}" class="btn btn-light-warning btn-sm">Apri</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@push('customCss')
    <style>
        .admin-control-room {
            --acr-border: #e4edf6;
            --acr-soft: #f7fafc;
            --acr-text: #172033;
            --acr-muted: #7a88a6;
            --acr-blue: #009ef7;
        }

        .admin-control-hero,
        .admin-control-panel {
            border: 1px solid var(--acr-border);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(16, 24, 39, .035);
        }

        .admin-control-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.45rem 1.6rem;
            margin-bottom: 1rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, .98) 0%, rgba(248, 251, 255, .98) 100%);
        }

        .admin-control-kicker {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 0 .65rem;
            border: 1px solid #d7ecfb;
            border-radius: 999px;
            background: #eef8ff;
            margin-bottom: .25rem;
            color: var(--acr-blue);
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .admin-control-hero h2,
        .admin-control-head h3 {
            margin: 0;
            color: var(--acr-text);
            font-weight: 850;
        }

        .admin-control-hero h2 {
            margin-top: .45rem;
            font-size: 1.55rem;
            line-height: 1.15;
        }

        .admin-control-hero p,
        .admin-control-head span {
            margin: .25rem 0 0;
            color: var(--acr-muted);
            font-weight: 600;
        }

        .admin-control-actions {
            display: flex;
            flex-wrap: nowrap;
            justify-content: flex-end;
            gap: .55rem;
            max-width: none;
            overflow-x: auto;
            padding-bottom: .15rem;
            white-space: nowrap;
        }

        .admin-control-actions .btn {
            flex: 0 0 auto;
            min-height: 34px;
            padding-inline: .9rem;
            border-radius: 8px;
            white-space: nowrap;
        }

        .admin-control-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1rem;
            align-items: stretch;
        }

        .admin-control-panel {
            grid-column: span 4;
            padding: 1.25rem;
            min-width: 0;
            min-height: 100%;
        }

        .admin-control-panel-wide {
            grid-column: span 8;
        }

        .admin-control-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .admin-control-head h3 {
            font-size: 1.05rem;
        }

        .admin-queue-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: .75rem;
        }

        .admin-queue-card,
        .admin-alert-row,
        .admin-health-row,
        .admin-money-list > div,
        .admin-audit-row {
            border: 1px solid #e9f1f8;
            border-radius: 8px;
            background: var(--acr-soft);
        }

        .admin-queue-card {
            display: block;
            min-height: 108px;
            padding: .95rem;
            border-left: 3px solid var(--acr-blue);
            color: var(--acr-text);
            transition: transform .22s cubic-bezier(.2, .8, .2, 1), border-color .22s cubic-bezier(.2, .8, .2, 1), background .22s cubic-bezier(.2, .8, .2, 1);
        }

        .admin-queue-card:hover {
            background: #fff;
            border-color: #d6e7f5;
            transform: translateY(-2px);
        }

        .admin-queue-card span,
        .admin-money-main span,
        .admin-money-list span,
        .admin-alert-row span,
        .admin-health-row span,
        .admin-audit-row span,
        .admin-audit-row em {
            color: var(--acr-muted);
            font-weight: 700;
        }

        .admin-queue-card strong {
            display: block;
            margin-top: .45rem;
            color: var(--acr-text);
            font-size: 1.7rem;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0;
        }

        .admin-queue-success { border-left-color: #50cd89; }
        .admin-queue-warning { border-left-color: #ffc700; }
        .admin-queue-danger { border-left-color: #f1416c; }
        .admin-queue-info { border-left-color: #7239ea; }
        .admin-queue-dark { border-left-color: #3f4254; }

        .admin-alert-list,
        .admin-health-list,
        .admin-audit-list,
        .admin-money-list {
            display: grid;
            gap: .65rem;
        }

        .admin-alert-row,
        .admin-health-row,
        .admin-money-list > div {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .8rem .95rem;
        }

        .admin-alert-row {
            border-left: 4px solid #ffc700;
        }

        .admin-alert-danger {
            border-left-color: #f1416c;
        }

        .admin-alert-row strong {
            color: var(--acr-text);
            font-size: 1.1rem;
            font-weight: 900;
        }

        .admin-money-main {
            padding: 1rem;
            margin-bottom: .75rem;
            border-radius: 8px;
            border: 1px solid #d7ecfb;
            background: linear-gradient(180deg, #eef8ff 0%, #f7fbff 100%);
        }

        .admin-money-main strong {
            display: block;
            margin-top: .25rem;
            color: var(--acr-blue);
            font-size: 1.65rem;
            font-weight: 900;
        }

        .admin-money-list strong {
            color: var(--acr-text);
            font-weight: 850;
        }

        .admin-audit-row {
            display: grid;
            gap: .2rem;
            padding: .8rem .95rem;
            min-height: 74px;
        }

        .admin-audit-row strong {
            color: var(--acr-text);
        }

        .admin-audit-row span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-audit-row em {
            font-size: .78rem;
            font-style: normal;
        }

        .admin-empty {
            padding: 1rem;
            border: 1px dashed var(--acr-border);
            border-radius: 8px;
            color: var(--acr-muted);
            background: var(--acr-soft);
            font-weight: 700;
        }

        @media (max-width: 1199.98px) {
            .admin-control-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-control-panel,
            .admin-control-panel-wide {
                grid-column: span 1;
            }

            .admin-queue-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .admin-control-hero {
                align-items: stretch;
                flex-direction: column;
            }

            .admin-control-actions {
                justify-content: flex-start;
            }

            .admin-control-grid {
                grid-template-columns: 1fr;
            }

            .admin-queue-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
@push('customScript')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script>
        $(function () {
            $('#mese').on('select2:select', function (e) {
                location.href = location.pathname + '?mese=' + $(this).val();
            });

            var target = document.getElementById('kt_card_widget_17_chart');
            if (!target || typeof Highcharts === 'undefined') return;

            var datiTortaEsiti = @json($datiTortaEsiti);
            target.innerHTML = '';

            var categories = datiTortaEsiti['labels'] || [];
            var values = datiTortaEsiti['data'] || [];
            var colors = datiTortaEsiti['backgroundColor'] || [];
            var points = categories.map(function (label, idx) {
                return {
                    name: label,
                    y: Number(values[idx] || 0),
                    color: colors[idx]
                };
            });

            Highcharts.chart('kt_card_widget_17_chart', {
                chart: {
                    type: 'pie',
                    height: 280,
                    backgroundColor: 'transparent',
                    spacing: [0, 0, 0, 0]
                },
                title: {text: null},
                credits: {enabled: false},
                tooltip: {
                    pointFormatter: function () {
                        return '<span style="color:' + this.color + '">\u25CF</span> ' + this.name + ': <b>' + this.y + '</b>';
                    }
                },
                plotOptions: {
                    pie: {
                        innerSize: '68%',
                        borderWidth: 1,
                        borderColor: '#ffffff',
                        dataLabels: {
                            enabled: true,
                            formatter: function () {
                                return Math.round(this.percentage) + '%';
                            },
                            style: {
                                fontSize: '10px',
                                textOutline: 'none'
                            }
                        },
                        showInLegend: false
                    }
                },
                series: [{
                    name: 'Esiti',
                    colorByPoint: true,
                    data: points
                }]
            });
        });
    </script>
@endpush
