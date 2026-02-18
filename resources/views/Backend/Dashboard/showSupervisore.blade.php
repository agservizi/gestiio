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
        $canVisure = $canVisure ?? auth()->user()?->can('servizio_visure');
        $canSpedizioni = $canSpedizioni ?? auth()->user()?->can('servizio_spedizioni');
        $canDocumentazione = $canDocumentazione ?? auth()->user()?->can('servizio_documentazione');

        $kpiSupervisore = $kpiSupervisore ?? [
            'contratti_telefonia_mese' => 0,
            'contratti_energia_mese' => 0,
            'pratiche_caf_mese' => 0,
            'ticket_aperti' => 0,
            'pratiche_ferme' => 0,
            'visure_mese' => 0,
            'spedizioni_mese' => 0,
            'documenti_mese' => 0,
        ];

        $alertSupervisore = $alertSupervisore ?? [
            'caf_bloccate' => 0,
            'ticket_aperti_oltre_48h' => 0,
            'visure_senza_esito' => 0,
        ];

        $contrattiTelefonia = $contrattiTelefonia ?? collect();
        $contrattiEnergia = $contrattiEnergia ?? collect();
        $serviziCafPatronato = $serviziCafPatronato ?? collect();
        $ticketRecenti = $ticketRecenti ?? collect();
        $serviziAbilitati = $serviziAbilitati ?? collect();

        $showPriorita = $canCafPatronato || $canTicket || $canVisure;
    @endphp

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-12">
            <div class="card card-flush h-md-100">
                <div class="card-header border-0 pt-5 pb-0">
                    <div class="card-title d-flex align-items-center gap-2">
                        <span class="svg-icon svg-icon-2 text-primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.3" d="M4 19C4 17.8954 4.89543 17 6 17H20V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19Z" fill="currentColor"/>
                                <path d="M6 3C4.89543 3 4 3.89543 4 5V15H20V5C20 3.89543 19.1046 3 18 3H6ZM7 12L10.2 8.8L12.4 11L16.5 6.9L18 8.4L12.4 14L10.2 11.8L8.5 13.5L7 12Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="card-title m-0">Controllo supervisione</h3>
                            <div class="text-muted fs-7">Dashboard dinamica in base ai servizi abilitati</div>
                        </div>
                    </div>
                </div>
                <div class="card-body py-6 pt-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <span class="badge badge-light-primary fs-7 fw-bold">Mese: {{data_get($elencoMesi, $mese, $mese)}}</span>
                    </div>

                    @if($serviziAbilitati->isEmpty())
                        <div class="alert alert-warning mb-0">Nessun servizio abilitato per il tuo profilo supervisore.</div>
                    @else
                        <div class="row g-4">
                            @foreach($serviziAbilitati as $servizio)
                                <div class="col-md-6 col-xl-4">
                                    <div class="card card-flush h-md-100">
                                        <div class="card-body p-5 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center gap-3 mb-3">
                                                    <span class="symbol symbol-40px">
                                                        <span class="symbol-label bg-light-primary text-primary fw-bold">{{\Illuminate\Support\Str::substr($servizio['titolo'], 0, 1)}}</span>
                                                    </span>
                                                    <div>
                                                        <div class="fw-bold fs-5">{{$servizio['titolo']}}</div>
                                                        <div class="text-muted fs-7">{{$servizio['descrizione']}}</div>
                                                    </div>
                                                </div>
                                                <div class="bg-light-primary rounded p-4 mb-3">
                                                    <div class="text-muted fw-semibold fs-8 mb-1">{{$servizio['kpi_testo'] ?? 'Volume mese'}}</div>
                                                    <div class="fs-2 fw-bolder text-primary">{{number_format((int)($servizio['kpi_valore'] ?? 0))}}</div>
                                                </div>
                                            </div>
                                            <div>
                                                <a href="{{$servizio['url']}}" class="btn btn-sm btn-light-primary fw-bold">{{$servizio['cta'] ?? 'Apri servizio'}}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        @if($showPriorita)
            <div class="{{$canTicket ? 'col-xl-8' : 'col-xl-12'}}">
                <div class="card card-flush h-100">
                    <div class="card-header border-0 pt-5 pb-2">
                        <div class="card-title d-flex align-items-center gap-2">
                            <span class="svg-icon svg-icon-2 text-primary">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3" d="M12 3L2 21H22L12 3Z" fill="currentColor"/>
                                    <path d="M12 8V14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="12" cy="17" r="1" fill="currentColor"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="card-title m-0">Priorità supervisione</h3>
                                <div class="text-muted fs-7">Anomalie e carichi operativi da presidiare</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-4">
                            @if($canCafPatronato)
                                <div class="col-md-4">
                                    <div class="border border-dashed border-gray-300 rounded p-4 h-100">
                                        <div class="text-muted fs-7">Pratiche CAF bloccate</div>
                                        <div class="fs-2 fw-bolder text-danger">{{number_format((int)$alertSupervisore['caf_bloccate'])}}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border border-dashed border-gray-300 rounded p-4 h-100">
                                        <div class="text-muted fs-7">Pratiche ferme &gt; 7 giorni</div>
                                        <div class="fs-2 fw-bolder">{{number_format((int)$kpiSupervisore['pratiche_ferme'])}}</div>
                                    </div>
                                </div>
                            @endif

                            @if($canTicket)
                                <div class="col-md-4">
                                    <div class="border border-dashed border-gray-300 rounded p-4 h-100">
                                        <div class="text-muted fs-7">Ticket aperti &gt; 48h</div>
                                        <div class="fs-2 fw-bolder text-warning">{{number_format((int)$alertSupervisore['ticket_aperti_oltre_48h'])}}</div>
                                    </div>
                                </div>
                            @endif

                            @if($canVisure)
                                <div class="col-md-4">
                                    <div class="border border-dashed border-gray-300 rounded p-4 h-100">
                                        <div class="text-muted fs-7">Visure senza esito</div>
                                        <div class="fs-2 fw-bolder text-primary">{{number_format((int)$alertSupervisore['visure_senza_esito'])}}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
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

    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        @if($canTelefonia)
            <div class="{{($canEnergia || $canCafPatronato) ? 'col-xxl-6' : 'col-xxl-12'}}">
                <div class="card card-flush h-md-100">
                    <div class="card-header border-0 pt-5">
                        <div class="card-title d-flex align-items-center gap-2">
                            <span class="svg-icon svg-icon-2 text-primary">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="3" width="18" height="18" rx="4" fill="currentColor" opacity="0.3"/>
                                    <rect x="7" y="7" width="10" height="2" rx="1" fill="currentColor"/>
                                    <rect x="7" y="11" width="10" height="2" rx="1" fill="currentColor"/>
                                </svg>
                            </span>
                            <div>
                                <span class="card-label fw-bold fs-3 mb-1 d-block">Contratti telefonia recenti</span>
                                <span class="text-muted fs-7">Ultimi inserimenti monitorati</span>
                            </div>
                        </div>
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
                                @include('Backend.Dashboard.admin.contratti',['records'=>$contrattiTelefonia])
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($canEnergia)
            <div class="{{($canTelefonia || $canCafPatronato) ? 'col-xxl-6' : 'col-xxl-12'}}">
                <div class="card card-flush h-md-100">
                    <div class="card-header border-0 pt-5">
                        <div class="card-title d-flex align-items-center gap-2">
                            <span class="svg-icon svg-icon-2 text-primary">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3" d="M6 3H18C19.1046 3 20 3.89543 20 5V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V5C4 3.89543 4.89543 3 6 3Z" fill="currentColor"/>
                                    <path d="M13 6L8 13H12L11 18L16 11H12L13 6Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <div>
                                <span class="card-label fw-bold fs-3 mb-1 d-block">Contratti energia recenti</span>
                                <span class="text-muted fs-7">Ultime pratiche luce e gas</span>
                            </div>
                        </div>
                        <div class="card-toolbar">
                            <a class="btn btn-sm btn-light-primary fw-bold" href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'index'])}}">Vedi tutti</a>
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
                                @forelse($contrattiEnergia as $record)
                                    <tr id="tr_{{$record->id}}">
                                        <td class="text-dark fw-bold">{{$record->data?->format('d/m/Y')}}</td>
                                        <td>
                                            <div class="d-flex justify-content-start flex-column">
                                                <span class="text-dark fw-bold fs-6">{{$record->agente?->nominativo()}}</span>
                                                <span class="text-muted fw-semibold d-block fs-7">{{$record->nominativo()}}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-dark fw-bold d-block fs-6">{{$record->tipoContratto?->gestore?->nome}}</span>
                                            <span class="text-muted fw-semibold d-block fs-7">{{$record->tipoContratto?->nome}}</span>
                                        </td>
                                        <td class="text-center">{!! $record->esito?->labelStato() !!}</td>
                                        <td class="text-end">
                                            <a href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'edit'],$record->id)}}" class="btn btn-sm btn-light-primary">Apri</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">Nessun contratto energia recente.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($canCafPatronato)
            <div class="{{($canTelefonia || $canEnergia) ? 'col-xxl-6' : 'col-xxl-12'}}">
                <div class="card card-flush h-md-100">
                    <div class="card-header border-0 pt-5">
                        <div class="card-title d-flex align-items-center gap-2">
                            <span class="svg-icon svg-icon-2 text-primary">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.3" d="M4 5C4 3.89543 4.89543 3 6 3H18C19.1046 3 20 3.89543 20 5V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V5Z" fill="currentColor"/>
                                    <path d="M8 8H16V10H8V8ZM8 12H16V14H8V12Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <div>
                                <span class="card-label fw-bold fs-3 mb-1 d-block">Caf / Patronato recenti</span>
                                <span class="text-muted fs-7">Ultime pratiche da supervisionare</span>
                            </div>
                        </div>
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
                                @include('Backend.Dashboard.admin.cafPatronato',['records' => $serviziCafPatronato,'puoModificareEsito'=>\App\Models\CafPatronato::puoModificareEsito(),'puoModificare'=>\App\Models\CafPatronato::puoModificare(),'controller'=>\App\Http\Controllers\Backend\CafPatronatoController::class])
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if(!$canTelefonia && !$canEnergia && !$canCafPatronato && !$canTicket && !$canVisure && !$canSpedizioni && !$canDocumentazione)
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">Nessun modulo operativo da mostrare per il tuo profilo supervisore.</div>
            </div>
        </div>
    @endif
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
