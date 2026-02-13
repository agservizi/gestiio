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
        $canTelefonia = $canTelefonia ?? auth()->user()?->can('servizio_contratti_telefonia');
        $canEnergia = $canEnergia ?? auth()->user()?->can('servizio_contratti_energia');
        $canCafPatronato = $canCafPatronato ?? auth()->user()?->can('servizio_caf_patronato');
        $canTicket = $canTicket ?? auth()->user()?->can('servizio_ticket');

        $kpiSupervisore = $kpiSupervisore ?? [
            'contratti_telefonia_mese' => 0,
            'contratti_energia_mese' => 0,
            'pratiche_caf_mese' => 0,
            'ticket_aperti' => 0,
            'pratiche_ferme' => 0,
        ];
        $alertSupervisore = $alertSupervisore ?? [
            'caf_bloccate' => 0,
            'ticket_aperti_oltre_48h' => 0,
        ];
        $ticketRecenti = $ticketRecenti ?? collect();

        $showPriorita = $canCafPatronato || $canTicket;
        $prioritaColClass = $canTicket ? 'col-xl-8' : 'col-xl-12';
        $showContrattiPanel = $canTelefonia;
        $showCafPanel = $canCafPatronato;
        $tablesColClass = ($showContrattiPanel && $showCafPanel) ? 'col-xxl-6' : 'col-xxl-12';

        $serviceCards = collect([
            [
                'enabled' => $canTelefonia,
                'kpi_title' => 'Telefonia mese',
                'kpi_value' => $kpiSupervisore['contratti_telefonia_mese'],
                'kpi_text' => 'Contratti inseriti nel periodo selezionato',
                'service_title' => 'Contratti telefonia',
                'service_url' => action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class, 'index']),
                'service_img' => '/icone_dash/contratti_telefonia.png',
            ],
            [
                'enabled' => $canEnergia,
                'kpi_title' => 'Energia mese',
                'kpi_value' => $kpiSupervisore['contratti_energia_mese'],
                'kpi_text' => 'Pratiche luce/gas inserite nel periodo',
                'service_title' => 'Contratti luce e gas',
                'service_url' => action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'index']),
                'service_img' => '/icone_dash/contr_luce_gas.png',
            ],
            [
                'enabled' => $canCafPatronato,
                'kpi_title' => 'Caf/Patronato mese',
                'kpi_value' => $kpiSupervisore['pratiche_caf_mese'],
                'kpi_text' => 'Pratiche inserite nel periodo',
                'service_title' => 'Servizi Caf Patronato',
                'service_url' => action([\App\Http\Controllers\Backend\CafPatronatoController::class, 'index']),
                'service_img' => '/icone_dash/patronato.png',
            ],
            [
                'enabled' => $canTicket,
                'kpi_title' => 'Ticket aperti',
                'kpi_value' => $kpiSupervisore['ticket_aperti'],
                'kpi_text' => 'In lavorazione complessiva',
                'service_title' => 'Ticket assistenza',
                'service_url' => action([\App\Http\Controllers\Backend\TicketsController::class, 'index']),
                'service_img' => '/icone_dash/ticket.png',
            ],
        ])->where('enabled', true)->values();

        $isSingleService = $serviceCards->count() === 1;
        $singleService = $isSingleService ? $serviceCards->first() : null;
    @endphp

    @if($isSingleService)
        <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
            <div class="col-md-6 col-xl-4">
                <div class="card card-flush" style="height:170px;">
                    <div class="card-header border-0 pt-5 pb-2">
                        <h3 class="card-title">{{ $singleService['kpi_title'] }}</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="fs-2hx fw-bold">{{ number_format($singleService['kpi_value']) }}</div>
                        <div class="text-muted">{{ $singleService['kpi_text'] }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card card-flush" style="height:170px;">
                    <div class="card-body d-flex align-items-center justify-content-between py-4 px-5">
                        <div class="d-flex flex-column gap-1 pe-3">
                            <h4 class="mb-0">{{ $singleService['service_title'] }}</h4>
                            <span class="text-muted fs-7">Accesso rapido al servizio attivo</span>
                            <div class="mt-2">
                                <a href="{{ $singleService['service_url'] }}" class="btn btn-light-primary btn-sm">Apri</a>
                            </div>
                        </div>
                        <div class="overlay-wrapper">
                            <img src="{{ $singleService['service_img'] }}" class="img rounded" style="width:64px;height:64px;object-fit:contain;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-xl-4">
                <div class="card card-flush" style="height:170px;">
                    <div class="card-header border-0 pt-5 pb-2">
                        <h3 class="card-title">Priorità supervisione</h3>
                    </div>
                    <div class="card-body pt-0">
                        @if($canCafPatronato || $canTicket)
                            <div class="d-flex flex-wrap align-items-center gap-8">
                                @if($canCafPatronato)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">Pratiche CAF bloccate</span>
                                        <span class="fw-bolder text-danger fs-4">{{ number_format($alertSupervisore['caf_bloccate']) }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">Pratiche ferme (7+ giorni)</span>
                                        <span class="fw-bolder fs-4">{{ number_format($kpiSupervisore['pratiche_ferme']) }}</span>
                                    </div>
                                @endif
                                @if($canTicket)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">Ticket aperti da oltre 48h</span>
                                        <span class="fw-bolder text-warning fs-4">{{ number_format($alertSupervisore['ticket_aperti_oltre_48h']) }}</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-muted">Nessuna priorità disponibile per il servizio attivo.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        @if($canTelefonia)
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush h-md-100">
                    <div class="card-header border-0 pt-5 pb-2">
                        <h3 class="card-title">Telefonia mese</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="fs-2hx fw-bold">{{ number_format($kpiSupervisore['contratti_telefonia_mese']) }}</div>
                        <div class="text-muted">Contratti inseriti nel periodo selezionato</div>
                    </div>
                </div>
            </div>
        @endif

        @if($canEnergia)
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush h-md-100">
                    <div class="card-header border-0 pt-5 pb-2">
                        <h3 class="card-title">Energia mese</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="fs-2hx fw-bold">{{ number_format($kpiSupervisore['contratti_energia_mese']) }}</div>
                        <div class="text-muted">Pratiche luce/gas inserite nel periodo</div>
                    </div>
                </div>
            </div>
        @endif

        @if($canCafPatronato)
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush h-md-100">
                    <div class="card-header border-0 pt-5 pb-2">
                        <h3 class="card-title">Caf/Patronato mese</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="fs-2hx fw-bold">{{ number_format($kpiSupervisore['pratiche_caf_mese']) }}</div>
                        <div class="text-muted">Pratiche inserite nel periodo</div>
                    </div>
                </div>
            </div>
        @endif

        @if($canTicket)
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush h-md-100">
                    <div class="card-header border-0 pt-5 pb-2">
                        <h3 class="card-title">Ticket aperti</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="fs-2hx fw-bold">{{ number_format($kpiSupervisore['ticket_aperti']) }}</div>
                        <div class="text-muted mb-3">In lavorazione complessiva</div>
                        <a href="{{ action([\App\Http\Controllers\Backend\TicketsController::class, 'index']) }}" class="btn btn-light-primary btn-sm">Apri ticket</a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        @can('servizio_contratti_telefonia')
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <a class="card-body pt-5 text-center" href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/contratti_telefonia.png" class="img w-75 rounded">
                        </div>
                        <h4 class="mt-4">Contratti telefonia</h4>
                    </a>
                </div>
            </div>
        @endcan

        @can('servizio_contratti_energia')
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <a class="card-body pt-5 text-center" href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/contr_luce_gas.png" class="img w-75 rounded">
                        </div>
                        <h4 class="mt-4">Contratti luce e gas</h4>
                    </a>
                </div>
            </div>
        @endcan

        @can('servizio_caf_patronato')
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <a class="card-body pt-5 text-center" href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/patronato.png" class="img w-75 rounded">
                        </div>
                        <h4 class="mt-4">Servizi Caf Patronato</h4>
                    </a>
                </div>
            </div>
        @endcan

        @can('servizio_ticket')
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <a class="card-body pt-5 text-center" href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/ticket.png" class="img w-75 rounded">
                        </div>
                        <h4 class="mt-4">Ticket assistenza</h4>
                    </a>
                </div>
            </div>
        @endcan
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        @if($showPriorita)
            <div class="{{ $prioritaColClass }}">
                <div class="card card-flush h-100">
                    <div class="card-header border-0 pt-5 pb-2">
                        <h3 class="card-title">Priorità supervisione</h3>
                    </div>
                    <div class="card-body pt-0">
                        @if($canCafPatronato)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Pratiche CAF bloccate</span>
                                <span class="fw-bolder text-danger">{{ number_format($alertSupervisore['caf_bloccate']) }}</span>
                            </div>
                        @endif
                        @if($canTicket)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Ticket aperti da oltre 48h</span>
                                <span class="fw-bolder text-warning">{{ number_format($alertSupervisore['ticket_aperti_oltre_48h']) }}</span>
                            </div>
                        @endif
                        @if($canCafPatronato)
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Pratiche ferme (7+ giorni)</span>
                                <span class="fw-bolder">{{ number_format($kpiSupervisore['pratiche_ferme']) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        @if($canTicket)
            <div class="col-xl-4">
                @include('Backend.Dashboard.admin.ticket', ['records' => $ticketRecenti])
            </div>
        @endif
    </div>
    @endif

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        @if($showContrattiPanel)
            <div class="{{ $tablesColClass }}">
                <div class="card card-flush h-md-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Contratti recenti</span>
                        </h3>
                        <div class="card-toolbar">
                            <a class="btn btn-sm btn-light-primary fw-bold" href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}">Vedi tutti</a>
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
        @endif

        @if($showCafPanel)
            <div class="{{ $tablesColClass }}">
                <div class="card card-flush h-md-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Caf / Patronato recenti</span>
                        </h3>
                        <div class="card-toolbar">
                            <a class="btn btn-sm btn-light-primary fw-bold" href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}">Vedi tutti</a>
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
        @endif
    </div>
@endsection

@push('customScript')
    <script>
        $(function () {
            $('#mese').on('select2:select', function () {
                location.href = location.pathname + '?mese=' + $(this).val();
            });
        });
    </script>
@endpush
