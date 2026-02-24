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
                <div class="card-header border-0 pt-5 pb-0">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M4 19C4 17.8954 4.89543 17 6 17H20V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19Z" fill="currentColor"/>
                                <path d="M6 3C4.89543 3 4 3.89543 4 5V15H20V5C20 3.89543 19.1046 3 18 3H6ZM7 12L10.2 8.8L12.4 11L16.5 6.9L18 8.4L12.4 14L10.2 11.8L8.5 13.5L7 12Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <h3 class="card-title m-0">Produzione mese</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="bg-light-primary rounded p-4 mb-4">
                        <div class="text-muted fw-semibold fs-8 mb-1">Contratti totali</div>
                        <div class="fs-2 fw-bolder text-primary">{{ number_format($produzioneConteggio) }}</div>
                    </div>
                    <div class="d-flex justify-content-between fw-semibold mb-2">
                        <span class="text-muted">In lavorazione</span>
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
                <div class="card-header border-0 pt-5 pb-0">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M4 19C4 17.8954 4.89543 17 6 17H20V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19Z" fill="currentColor"/>
                                <path d="M6 3H18C19.1046 3 20 3.89543 20 5V15H6C4.89543 15 4 15.8954 4 17V5C4 3.89543 4.89543 3 6 3Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <h3 class="card-title m-0">Economico mese</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="bg-light-primary rounded p-4 mb-4">
                        <div class="text-muted fw-semibold fs-8 mb-1">Utile stimato</div>
                        <div class="fs-2 fw-bolder text-primary">{{ \App\importo($guadagno->utile,true) }}</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-semibold">Entrate</span>
                        <span class="badge badge-light-success fw-bolder px-4 py-2">{{ \App\importo($guadagno->entrate,true) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted fw-semibold">Uscite</span>
                        <span class="badge badge-light-danger fw-bolder px-4 py-2">{{ \App\importo($guadagno->uscite,true) }}</span>
                    </div>

                    <div class="d-flex justify-content-between fw-semibold mb-2">
                        <span class="text-muted">Incidenza utile</span>
                        <span>{{ $percentualeUtile }}%</span>
                    </div>
                    <div class="progress h-8px bg-light-success">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentualeUtile }}%" aria-valuenow="{{ $percentualeUtile }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
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
                <div class="card-header border-0 pt-5 pb-0">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M12 3C7.02944 3 3 6.80558 3 11.5C3 14.0409 4.18896 16.3218 6.07143 17.8898V21L9.02857 19.3716C9.94801 19.623 10.9482 19.75 12 19.75C16.9706 19.75 21 15.9444 21 11.25C21 6.55558 16.9706 3 12 3Z" fill="currentColor"/>
                                <path d="M8.5 11.25C8.5 10.6977 8.94772 10.25 9.5 10.25H14.5C15.0523 10.25 15.5 10.6977 15.5 11.25C15.5 11.8023 15.0523 12.25 14.5 12.25H9.5C8.94772 12.25 8.5 11.8023 8.5 11.25Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <h3 class="card-title m-0">Assistenza</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="bg-light-primary rounded p-4 mb-4">
                        <div class="text-muted fw-semibold fs-8 mb-1">Richieste totali</div>
                        <div class="fs-2 fw-bolder text-primary">{{ number_format($kpiDashboard['richieste_assistenza_totali']) }}</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-semibold">Nuove oggi</span>
                        <span class="badge badge-light-primary fw-bolder px-4 py-2">{{ number_format($kpiDashboard['richieste_assistenza_oggi']) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <span class="text-muted fw-semibold">Senza credenziali</span>
                        <span class="badge badge-light-danger fw-bolder px-4 py-2">{{ number_format($alertDashboard['richieste_senza_credenziali']) }}</span>
                    </div>

                    <a href="{{ action([\App\Http\Controllers\Backend\RichiestaAssistenzaController::class, 'index']) }}" class="btn btn-light-warning btn-sm">Apri richieste</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-xl-6">
            <div class="card card-flush h-lg-100">
                <div class="card-header border-0 pt-5 pb-0">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M3 13.2C3 9.32002 6.14 6.18002 10.02 6.18002C11.76 6.18002 13.36 6.82002 14.6 7.86002L16.32 6.14002C16.64 5.82002 17.18 5.98002 17.26 6.42002L17.84 9.76002C17.88 10.02 17.7 10.26 17.44 10.3L14.1 10.88C13.66 10.96 13.5 10.42 13.82 10.1L15.5 8.42002C14.48 7.56002 13.2 7.04002 11.78 7.04002C8.46002 7.04002 5.76002 9.74002 5.76002 13.06C5.76002 16.38 8.46002 19.08 11.78 19.08C14.1 19.08 16.12 17.76 17.12 15.84C17.3 15.5 17.74 15.36 18.08 15.54C18.42 15.72 18.56 16.16 18.38 16.5C17.14 18.88 14.62 20.52 11.78 20.52C7.90002 20.52 3 17.08 3 13.2Z" fill="currentColor"/>
                                <path d="M20 5.00002C20 4.44002 19.56 4.00002 19 4.00002C18.44 4.00002 18 4.44002 18 5.00002V11C18 11.56 18.44 12 19 12C19.56 12 20 11.56 20 11V5.00002Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="fw-bolder mb-0">Esito finale</h3>
                            <div class="fs-7 fw-semibold text-gray-400">Monitoraggio esiti contratti</div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-8 pt-4">
                    <div class="bg-light-primary rounded p-4 mb-5 d-flex align-items-center justify-content-between">
                        <span class="text-muted fw-semibold">Ordini totali</span>
                        <span class="fs-2 fw-bolder text-primary">{{ number_format((int) $datiTortaEsiti['totale']) }}</span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center">
                        <div class="position-relative d-flex flex-center h-170px w-170px me-5 mb-7 mb-xl-0">
                            <div class="position-absolute translate-middle start-50 top-50 d-flex flex-column flex-center" style="z-index: 2; pointer-events: none;">
                                <span class="fs-2qx fw-bolder">{{ number_format((int) $datiTortaEsiti['totale']) }}</span>
                                <span class="fs-6 fw-bold text-gray-400">Totali</span>
                            </div>
                            <div id="kt_card_widget_17_chart" style="width: 170px; height: 170px; z-index: 1;"></div>
                        </div>
                        <div class="d-flex flex-column justify-content-center flex-row-fluid pe-0 pe-xl-5">
                            @for($n=0;$n<count($datiTortaEsiti['labels']);$n++)
                                <div class="d-flex fs-6 fw-bold align-items-center mb-2 p-2 rounded bg-light">
                                    <div class="bullet me-3 h-6px w-20px" style="background-color: {{ $datiTortaEsiti['backgroundColor'][$n] }};"></div>
                                    <div class="text-gray-700">{{ $datiTortaEsiti['labels'][$n] }}</div>
                                    <div class="ms-auto fw-bolder text-gray-900">{{ number_format((int) $datiTortaEsiti['data'][$n]) }}</div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-flush h-lg-100">
                <div class="card-header border-0 pt-5 pb-0">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M4 6C4 4.89543 4.89543 4 6 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H10L6 20V16C4.89543 16 4 15.1046 4 14V6Z" fill="currentColor"/>
                                <path d="M8 8C8 7.44772 8.44772 7 9 7H15C15.5523 7 16 7.44772 16 8C16 8.55228 15.5523 9 15 9H9C8.44772 9 8 8.55228 8 8ZM8 11.5C8 10.9477 8.44772 10.5 9 10.5H13C13.5523 10.5 14 10.9477 14 11.5C14 12.0523 13.5523 12.5 13 12.5H9C8.44772 12.5 8 12.0523 8 11.5Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="fw-bolder mb-0">Chat interna</h3>
                            <div class="fs-7 fw-semibold text-gray-400">Monitoraggio conversazioni</div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="bg-light-primary rounded p-4 mb-4">
                        <div class="text-muted fw-semibold fs-8 mb-1">Messaggi non letti</div>
                        <div class="fs-2 fw-bolder text-primary">{{ number_format((int) $chatDashboard['messaggi_non_letti']) }}</div>
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
@push('customScript')
    <script>
        $(function () {
            $('#mese').on('select2:select', function (e) {
                location.href = location.pathname + '?mese=' + $(this).val();
            });

            var target = document.getElementById('kt_card_widget_17_chart');
            if (!target || typeof ApexCharts === 'undefined') return;

            var datiTortaEsiti = @json($datiTortaEsiti);
            target.innerHTML = '';

            new ApexCharts(target, {
                chart: {
                    type: 'donut',
                    height: 150,
                    toolbar: {show: false}
                },
                series: datiTortaEsiti['data'] || [],
                labels: datiTortaEsiti['labels'] || [],
                colors: datiTortaEsiti['backgroundColor'] || [],
                dataLabels: {enabled: false},
                legend: {show: false},
                stroke: {width: 0},
                plotOptions: {
                    pie: {
                        donut: {
                            size: '88%'
                        }
                    }
                },
                noData: {text: 'Nessun dato'}
            }).render();
        });
    </script>
@endpush
