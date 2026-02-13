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
    @endphp

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
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
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header border-0 pt-5 pb-2">
                    <h3 class="card-title">Priorità supervisione</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Pratiche CAF bloccate</span>
                        <span class="fw-bolder text-danger">{{ number_format($alertSupervisore['caf_bloccate']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Ticket aperti da oltre 48h</span>
                        <span class="fw-bolder text-warning">{{ number_format($alertSupervisore['ticket_aperti_oltre_48h']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Pratiche ferme (7+ giorni)</span>
                        <span class="fw-bolder">{{ number_format($kpiSupervisore['pratiche_ferme']) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            @include('Backend.Dashboard.admin.ticket', ['records' => $ticketRecenti])
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        @can('servizio_contratti_telefonia')
            <div class="col-xxl-6">
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
        @endcan

        @can('servizio_caf_patronato')
            <div class="col-xxl-6">
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
        @endcan
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
