@php
    $container = 'container-xxl';
@endphp
@extends('Backend._layout._main')

@section('toolbar', '')
@section('content')
    @php
        $kpiAgente = $kpiAgente ?? [
            'miei_ticket_aperti' => 0,
            'miei_ticket_oggi' => 0,
            'ticket_aperti_totali' => 0,
        ];
        $ticketDaGestire = $ticketDaGestire ?? collect();
    @endphp
    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        @can('servizio_contratti_telefonia')
            <!--begin::Col-->
            <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-6 mb-md-5 mb-xl-5">
                <!--begin::Card widget 20-->
                <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-md-100 mb-5 mb-xl-5"
                     style="background-color: #F1416C;">
                    <!--begin::Header-->
                    <div class="card-header pt-5">
                        @php
                            $percentuale = \App\percentuale($produzioneMese?->conteggio_ordini_in_lavorazione, $produzioneMese?->conteggio_ordini);
                        @endphp

                        <!--begin::Title-->
                        <div class="card-title d-flex flex-column">
                            <!--begin::Amount-->
                            <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{$produzioneMese?->conteggio_ordini}}</span>
                            <!--end::Amount-->

                            <!--begin::Subtitle-->
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Contratti telefonia</span>
                            <!--end::Subtitle-->
                        </div>
                        <!--end::Title-->
                    </div>
                    <!--end::Header-->
                    <!--begin::Card body-->
                    <div class="card-body d-flex align-items-end pt-0">
                        <!--begin::Progress-->
                        <div class="d-flex align-items-center flex-column mt-3 w-100">
                            <div class="d-flex justify-content-between fw-bold fs-6 text-white opacity-75 w-100 mt-auto mb-2">
                                <span>{{$produzioneMese?->conteggio_ordini_in_lavorazione}} in lavorazione</span>
                                <span>{{$percentuale}}%</span>
                            </div>

                            <div class="h-8px mx-3 w-100 bg-white bg-opacity-50 rounded">
                                <div class="bg-white rounded h-8px" role="progressbar" style="width: {{$percentuale}}%;"
                                     aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <!--end::Progress-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card widget 20-->
            </div>

            <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-5">
                <!--begin::Card widget 7-->
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <!--begin::Card body-->
                    <a class="card-body pt-5 text-center" href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/contratti_telefonia.png" class="img w-75 rounded">
                        </div>
                        <div class="overlay-layer bg-dark bg-opacity-25 d-flex flex-column">
                        </div>
                        <h4 class="mt-4">Contratti telefonia</h4>
                    </a>
                    <!--end::Card body-->
                </div>
                <!--end::Card widget 7-->
            </div>
        @endcan
        @can('servizio_contratti_energia')
            <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-5">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <a class="card-body pt-5 text-center" href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/contr_luce_gas.png" class="img w-75 rounded">
                        </div>
                        <div class="overlay-layer bg-dark bg-opacity-25 d-flex flex-column">
                        </div>
                        <h4 class="mt-4">Contratti Luce e Gas</h4>
                    </a>
                </div>
            </div>
        @endcan
        @can('servizio_visure')
            <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-5">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <a class="card-body pt-5 text-center" href="{{action([\App\Http\Controllers\Backend\VisuraController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/visure.png" class="img w-75 rounded">
                        </div>
                        <div class="overlay-layer bg-dark bg-opacity-25 d-flex flex-column">
                        </div>
                        <h4 class="mt-4">Visure</h4>
                    </a>
                </div>
            </div>
        @endcan
        @can('servizio_caf_patronato')
            <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-5">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <a class="card-body pt-5 text-center" href="{{action([\App\Http\Controllers\Backend\CafPatronatoController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/patronato.png" class="img w-75 rounded">
                        </div>
                        <div class="overlay-layer bg-dark bg-opacity-25 d-flex flex-column">
                        </div>
                        <h4 class="mt-4">Servizi Caf Patronato</h4>
                    </a>
                </div>
            </div>
        @endcan
        @can('servizio_ticket')
            <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-5">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    @php
                        $daLeggere = \App\Http\MieClassiCache\CacheConteggioTicketsDaLeggere::get(Auth::id());
                    @endphp
                    <a class="card-body pt-5 text-center @if($daLeggere) ribbon ribbon-top @endif" href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'])}}">
                        @if($daLeggere)
                            <div class="ribbon-label bg-danger">
                                {{\App\singolareOplurale($daLeggere,'nuovo','nuovi')}}
                            </div>
                        @endif
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/ticket.png" class="img w-75 rounded">
                        </div>
                        <div class="overlay-layer bg-dark bg-opacity-25 d-flex flex-column">
                        </div>
                        <h4 class="mt-4">Ticket assistenza</h4>
                    </a>
                </div>
            </div>
        @endcan
        @can('servizio_spedizioni')
            <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-5">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <a class="card-body pt-5 text-center" href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/spedizioni.png" class="img w-75 rounded">
                        </div>
                        <div class="overlay-layer bg-dark bg-opacity-25 d-flex flex-column">
                        </div>
                        <h4 class="mt-4">Spedizioni</h4>
                    </a>
                </div>
            </div>
        @endcan
        @can('servizio_documentazione')
            <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-5">
                <div class="card card-flush h-md-100 overlay overflow-hidden">
                    <a class="card-body pt-5 text-center"
                       href="{{action([\App\Http\Controllers\Backend\CartellaFilesController::class,'index'])}}">
                        <div class="overlay-wrapper">
                            <img src="/icone_dash/icons8-cartella-64.png" class="img w-75 rounded">
                        </div>
                        <div class="overlay-layer bg-dark bg-opacity-25 d-flex flex-column">
                        </div>
                        <h4 class="mt-4">Documentazione</h4>
                    </a>
                </div>
            </div>
        @endcan
        <div class="col-md-6 col-lg-6 col-xl-3 col-xxl-3 mb-md-5 mb-xl-5">
            <div class="card card-flush h-md-100 overlay overflow-hidden">
                <a class="card-body pt-5 text-center"
                   href="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'index'])}}">
                    <div class="overlay-wrapper">
                        <img src="/icone_dash/portafoglio.jpg" class="img w-75 rounded">
                    </div>
                    <div class="overlay-layer bg-dark bg-opacity-25 d-flex flex-column">
                    </div>
                    <h4 class="mt-4">Portafoglio</h4>
                </a>
            </div>
        </div>
    </div>

    @can('servizio_ticket')
        <div class="row g-5 g-xl-10">
            <div class="col-xl-4 mb-5">
                <div class="card card-flush h-100">
                    <div class="card-header border-0 pt-5 pb-2">
                        <h3 class="card-title">Operatività ticket</h3>
                    </div>
                    <div class="card-body pt-0 px-4 pb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Aperti assegnati a me</span>
                            <span class="font-size-h4 font-weight-bolder">{{ number_format($kpiAgente['miei_ticket_aperti']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Creati oggi da me</span>
                            <span class="font-weight-bolder">{{ number_format($kpiAgente['miei_ticket_oggi']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Aperti totali</span>
                            <span class="font-weight-bolder">{{ number_format($kpiAgente['ticket_aperti_totali']) }}</span>
                        </div>
                        <div class="mt-4">
                            <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'])}}" class="btn btn-light-primary btn-sm">Apri ticket</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 mb-5">
                <div class="card card-flush h-100">
                    <div class="card-header border-0 pt-5 pb-2">
                        <h3 class="card-title">Priorità personali</h3>
                    </div>
                    <div class="card-body pt-0">
                        @if($ticketDaGestire->isEmpty())
                            <div class="text-muted">Nessun ticket aperto assegnato al tuo utente.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-head-custom table-head-bg table-vertical-center">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Causale</th>
                                        <th>Stato</th>
                                        <th>Creato il</th>
                                        <th class="text-right">Azione</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($ticketDaGestire as $ticket)
                                        <tr>
                                            <td>{{ $ticket->uidTicket() }}</td>
                                            <td>{{ $ticket->causaleTicket?->nome ?? 'N/D' }}</td>
                                            <td>{!! $ticket->labelStatoTicket() !!}</td>
                                            <td>{{ $ticket->created_at?->format('d/m/Y H:i') }}</td>
                                            <td class="text-right">
                                                <a href="{{ action([\App\Http\Controllers\Backend\TicketsController::class, 'show'], $ticket->id) }}" class="btn btn-light-primary btn-sm">Apri</a>
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
    @endcan
@endsection
@push('customCss')
@endpush
@push('customScript')
    <script>
        $(function () {

        });
    </script>
@endpush
