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
        $percentualeProduzione = \App\percentuale($produzioneInLavorazione, $produzioneConteggio);
        $guadagno = \App\Models\GuadagnoAgenzia::firstOrNew(['mese' => $filtroMese, 'anno' => $filtroAnno]);
        $percentualeUtile = \App\percentuale($guadagno->utile, $guadagno->entrate);
        $ticketAperti = (int) data_get($conteggioTikets, 'aperto.conteggio', 0) + (int) data_get($conteggioTikets, 'in_lavorazione.conteggio', 0);
        $ticketChiusi = (int) data_get($conteggioTikets, 'chiuso.conteggio', 0);
        $chatDashboard = $chatDashboard ?? [
            'messaggi_non_letti' => 0,
            'thread_attive' => 0,
            'nuovi_messaggi_oggi' => 0,
        ];
    @endphp

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-md-6 col-lg-3">
            <div class="card card-flush h-md-100">
                <div class="card-header border-0 pt-5 pb-2">
                    <h3 class="card-title">Produzione mese</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="fs-2hx fw-bold">{{ number_format($produzioneConteggio) }}</div>
                    <div class="text-muted mb-4">Contratti totali</div>
                    <div class="d-flex justify-content-between fw-semibold mb-2">
                        <span>In lavorazione</span>
                        <span>{{ number_format($produzioneInLavorazione) }} ({{ $percentualeProduzione }}%)</span>
                    </div>
                    <div class="progress h-8px bg-light-primary">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percentualeProduzione }}%" aria-valuenow="{{ $percentualeProduzione }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card card-flush h-md-100">
                <div class="card-header border-0 pt-5 pb-2">
                    <h3 class="card-title">Economico mese</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Entrate</span>
                        <span class="fw-bolder">{{ \App\importo($guadagno->entrate,true) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Uscite</span>
                        <span class="fw-bolder">{{ \App\importo($guadagno->uscite,true) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Utile</span>
                        <span class="fw-bolder">{{ \App\importo($guadagno->utile,true) }}</span>
                    </div>
                    <div class="text-muted">Incidenza utile: {{ $percentualeUtile }}%</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card card-flush h-md-100">
                <div class="card-header border-0 pt-5 pb-0">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M4 5C4 3.89543 4.89543 3 6 3H18C19.1046 3 20 3.89543 20 5V8C18.8954 8 18 8.89543 18 10C18 11.1046 18.8954 12 20 12V15C20 16.1046 19.1046 17 18 17H6C4.89543 17 4 16.1046 4 15V12C5.10457 12 6 11.1046 6 10C6 8.89543 5.10457 8 4 8V5Z" fill="currentColor"/>
                                <path d="M9 7.5C9 6.67157 9.67157 6 10.5 6H13.5C14.3284 6 15 6.67157 15 7.5C15 8.32843 14.3284 9 13.5 9H10.5C9.67157 9 9 8.32843 9 7.5Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <h3 class="card-title m-0">Ticket</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="bg-light-primary rounded p-4 mb-4">
                        <div class="text-muted fw-semibold fs-8 mb-1">Aperti / in lavorazione</div>
                        <div class="fs-2 fw-bolder text-primary">{{ number_format($ticketAperti) }}</div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-5">
                        <span class="text-muted fw-semibold">Chiusi</span>
                        <span class="badge badge-light-success fw-bolder px-4 py-2">{{ number_format($ticketChiusi) }}</span>
                    </div>

                    <a href="{{ action([\App\Http\Controllers\Backend\TicketsController::class, 'index']) }}" class="btn btn-light-primary btn-sm">Apri ticket</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card card-flush h-md-100">
                <div class="card-header border-0 pt-5 pb-2">
                    <h3 class="card-title">Assistenza</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Richieste totali</span>
                        <span class="fw-bolder">{{ number_format($kpiDashboard['richieste_assistenza_totali']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Nuove oggi</span>
                        <span class="fw-bolder">{{ number_format($kpiDashboard['richieste_assistenza_oggi']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Senza credenziali</span>
                        <span class="fw-bolder text-danger">{{ number_format($alertDashboard['richieste_senza_credenziali']) }}</span>
                    </div>
                    <a href="{{ action([\App\Http\Controllers\Backend\RichiestaAssistenzaController::class, 'index']) }}" class="btn btn-light-warning btn-sm">Apri richieste</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-xl-6">
            <div class="card card-flush h-lg-100">
                <div class="card-header border-0 pt-5 pb-2">
                    <div class="card-title flex-column">
                        <h3 class="fw-bolder mb-1">Esito finale</h3>
                        <div class="fs-6 fw-bold text-gray-400">Tutti i contratti</div>
                    </div>
                </div>
                <div class="card-body p-9 pt-3">
                    <div class="d-flex flex-wrap">
                        <div class="position-relative d-flex flex-center h-150px w-150px me-5 mb-7">
                            <div class="position-absolute translate-middle start-50 top-50 d-flex flex-column flex-center">
                                <span class="fs-2qx fw-bolder">{{ $datiTortaEsiti['totale'] }}</span>
                                <span class="fs-6 fw-bold text-gray-400">Ordini</span>
                            </div>
                            <canvas id="kt_card_widget_17_chart"></canvas>
                        </div>
                        <div class="d-flex flex-column justify-content-center flex-row-fluid pe-5 mb-5">
                            @for($n=0;$n<count($datiTortaEsiti['labels']);$n++)
                                <div class="d-flex fs-6 fw-bold align-items-center mb-3">
                                    <div class="bullet me-3 h-5px w-15px" style="background-color: {{ $datiTortaEsiti['backgroundColor'][$n] }};"></div>
                                    <div class="text-gray-400">{{ $datiTortaEsiti['labels'][$n] }}</div>
                                    <div class="ms-auto fw-bolder text-gray-700">{{ $datiTortaEsiti['data'][$n] }}</div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-flush h-lg-100">
                <div class="card-header border-0 pt-5 pb-2">
                    <div class="card-title flex-column">
                        <h3 class="fw-bolder mb-1">Chat interna</h3>
                        <div class="fs-6 fw-bold text-gray-400">Monitoraggio conversazioni</div>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted fw-semibold">Messaggi non letti</span>
                        <span class="badge badge-light-danger fw-bolder px-4 py-2">{{ number_format((int) $chatDashboard['messaggi_non_letti']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted fw-semibold">Thread attive (7 giorni)</span>
                        <span class="badge badge-light-primary fw-bolder px-4 py-2">{{ number_format((int) $chatDashboard['thread_attive']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <span class="text-muted fw-semibold">Nuovi messaggi oggi</span>
                        <span class="badge badge-light-success fw-bolder px-4 py-2">{{ number_format((int) $chatDashboard['nuovi_messaggi_oggi']) }}</span>
                    </div>

                    <a href="{{ action([\App\Http\Controllers\Backend\ChatController::class, 'index']) }}" class="btn btn-light-primary btn-sm">Apri chat interna</a>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            @include('Backend.Dashboard.linksGestori',['altezza'=>'h-lg-100'])
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-xxl-6">
            <div class="card card-flush h-md-100">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Contratti recenti</span>
                    </h3>
                    <div class="card-toolbar">
                        <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax" href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'create'])}}">
                            <span class="d-md-none">+</span><span class="d-none d-md-block">Nuovo contratto</span>
                        </a>
                    </div>
                </div>
                <div class="card-body card-scroll py-3">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                            <tr class="fw-bold text-muted">
                                <th>Data</th>
                                <th class="min-w-150px">Agente</th>
                                <th class="min-w-140px">Prodotto</th>
                                <th class="min-w-120px text-center">Esito</th>
                                <th class="min-w-100px text-end"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @include('Backend.Dashboard.admin.contratti',['records'=>$contratti])
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6">
            <div class="card card-flush h-md-100">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Caf / Patronato</span>
                    </h3>
                    <div class="card-toolbar">
                        <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax" href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'create'])}}">
                            <span class="d-md-none">+</span><span class="d-none d-md-block">Nuova pratica caf patronato</span>
                        </a>
                    </div>
                </div>
                <div class="card-body card-scroll py-3">
                    <div class="table-responsive">
                        <table class="table table-row-bordered" id="tabella-elenco">
                            <thead>
                            <tr class="fw-bolder fs-6 text-gray-800">
                                <th>Data</th>
                                <th>Tipo pratica</th>
                                <th>Esito</th>
                                <th>Nominativo</th>
                                <th class="text-center">Azioni</th>
                            </tr>
                            </thead>
                            <tbody>
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
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Priorità assistenza</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Richieste con credenziali mancanti</span>
                    </h3>
                </div>
                <div class="card-body pt-0">
                    @if($azioniRapide->isEmpty())
                        <div class="text-muted py-5">Nessuna priorità operativa al momento.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gs-0 gy-3">
                                <thead>
                                <tr class="fw-bold text-muted">
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Prodotto</th>
                                    <th>Creato il</th>
                                    <th class="text-end">Azione</th>
                                </tr>
                                </thead>
                                <tbody>
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
@push('customScript')
    <script>
        $(function () {
            $('#mese').on('select2:select', function (e) {
                location.href = location.pathname + '?mese=' + $(this).val();
            });

            var KTCardsWidget17 = {
                init: function () {
                    !function () {

                        var target = document.getElementById("kt_card_widget_17_chart");
                        if (target) {
                            var datiTortaEsiti =@json($datiTortaEsiti);

                            var s = target.getContext("2d");
                            new Chart(s, {
                                type: "doughnut",
                                data: {
                                    datasets: [{
                                        data: datiTortaEsiti['data'],
                                        backgroundColor: datiTortaEsiti['backgroundColor']
                                    }], labels: datiTortaEsiti['labels']
                                },
                                options: {
                                    chart: {fontFamily: "inherit"},
                                    cutoutPercentage: 75,
                                    responsive: !0,
                                    maintainAspectRatio: !1,
                                    cutout: "75%",
                                    title: {display: !1},
                                    animation: {animateScale: !0, animateRotate: !0},
                                    tooltips: {
                                        enabled: !0,
                                        intersect: !1,
                                        mode: "nearest",
                                        bodySpacing: 5,
                                        yPadding: 10,
                                        xPadding: 10,
                                        caretPadding: 0,
                                        displayColors: !1,
                                        backgroundColor: "#20D489",
                                        titleFontColor: "#ffffff",
                                        cornerRadius: 4,
                                        footerSpacing: 0,
                                        titleSpacing: 0
                                    },
                                    plugins: {legend: {display: !1}}
                                }
                            })
                        }
                    }()
                }
            };
            "undefined" != typeof module && (module.exports = KTCardsWidget17), KTUtil.onDOMContentLoaded((function () {
                KTCardsWidget17.init()
            }));
        });
    </script>
@endpush
