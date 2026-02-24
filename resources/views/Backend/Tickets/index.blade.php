@extends('Backend._layout._main')

@section('toolbar')
    <div class="ui-toolbar-actions py-1">
        <div class="me-2">
            <a href="#" class="btn btn-sm {{$conFiltro?'btn-success':'bg-body'}} btn-flex btn-light btn-active-primary fw-bolder"
               data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                <span class="svg-icon svg-icon-6 svg-icon-muted me-1">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.0759 3H4.72777C3.95892 3 3.47768 3.83148 3.86067 4.49814L8.56967 12.6949C9.17923 13.7559 9.5 14.9582 9.5 16.1819V19.5072C9.5 20.2189 10.2223 20.7028 10.8805 20.432L13.8805 19.1977C14.2553 19.0435 14.5 18.6783 14.5 18.273V13.8372C14.5 12.8089 14.8171 11.8056 15.408 10.964L19.8943 4.57465C20.3596 3.912 19.8856 3 19.0759 3Z" fill="currentColor"></path>
                    </svg>
                </span>
                Filtri
            </a>
            <div class="menu menu-sub menu-sub-dropdown w-250px w-md-350px" data-kt-menu="true" id="filtri-drop">
                @include('Backend.Tickets.indexFiltri')
            </div>
        </div>
        <a class="btn btn-sm btn-primary" href="{{action([$controller,'create'])}}">Nuovo {{\App\Models\Ticket::NOME_SINGOLARE}}</a>
        @if($conFiltro)
            <a class="btn btn-sm btn-light-success" href="{{action([$controller,'index'])}}">Reset Filtri</a>
        @endif
    </div>
@endsection

@section('content')
    @php($collection = $records->getCollection())
    @php($totale = $collection->count())
    @php($nonAssegnati = $collection->whereNull('agente_id')->count())
    @php($inCaricoAMe = $collection->filter(fn($r) => (int)($r->agente_id ?? 0) === (int)Auth::id())->count())
    @php($nuoviDaLeggere = $collection->filter(fn($r) => (int)($r->lettura?->messaggio_letto ?? 1) === 0)->count())

    @php($statiConfig = \App\Models\Ticket::STATI_TICKETS)
    @php($statusCounts = collect($statiConfig)->mapWithKeys(function ($v, $k) use ($collection) { return [$k => $collection->where('stato', $k)->count()]; }))
    @php($statusLabels = collect($statiConfig)->map(fn($v) => $v['testo'])->values())
    @php($statusValues = collect($statiConfig)->keys()->map(fn($k) => $statusCounts[$k] ?? 0)->values())

    @php($assigneeCounts = $collection
        ->groupBy(fn($r) => $r->assegnatario?->nominativo() ?? 'Non assegnato')
        ->map(fn($items) => $items->count())
        ->sortDesc()
        ->take(8))

    @php($trend = $collection
        ->groupBy(fn($r) => $r->created_at->format('d/m'))
        ->map(fn($items) => $items->count())
        ->sortKeys())

    <div class="card card-flush ui-card-surface mb-6">
        <div class="card-body py-4">
            <div class="ticket-ui-toolbar">
                <div class="ticket-ui-quick">
                    <span class="badge badge-light-primary">Pagina: {{$totale}} ticket</span>
                    @if($conFiltro)
                        <span class="badge badge-light-success">Filtri attivi</span>
                    @endif
                    @if(request()->filled('stato'))
                        <span class="badge badge-light-info">Stato: {{\App\Models\Ticket::STATI_TICKETS[request()->input('stato')]['testo'] ?? request()->input('stato')}}</span>
                    @endif
                    @if(request()->filled('assegnatario_id'))
                        <span class="badge badge-light-warning">Assegnazione filtrata</span>
                    @endif
                </div>
                <div class="w-300px mw-100">
                    <div class="position-relative">
                        <i class="ki-duotone ki-magnifier fs-2 position-absolute top-50 translate-middle-y ms-4"><span class="path1"></span><span class="path2"></span></i>
                        <input id="js-ticket-local-search" type="text" class="form-control form-control-solid ps-12" placeholder="Cerca in questa pagina ticket...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-6">
        <div class="col-md-6 col-xl-3">
            <div class="card card-flush ui-card-surface ticket-ui-kpi is-primary h-100">
                <div class="card-body d-flex flex-column">
                    <span class="text-muted fw-semibold fs-7">Ticket in pagina</span>
                    <span class="fs-2hx fw-bold text-gray-900 mt-2">{{$totale}}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card card-flush ui-card-surface ticket-ui-kpi is-danger h-100">
                <div class="card-body d-flex flex-column">
                    <span class="text-muted fw-semibold fs-7">Nuovi da leggere</span>
                    <span class="fs-2hx fw-bold text-danger mt-2">{{$nuoviDaLeggere}}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card card-flush ui-card-surface ticket-ui-kpi is-success h-100">
                <div class="card-body d-flex flex-column">
                    <span class="text-muted fw-semibold fs-7">In carico a me</span>
                    <span class="fs-2hx fw-bold text-success mt-2">{{$inCaricoAMe}}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card card-flush ui-card-surface ticket-ui-kpi is-warning h-100">
                <div class="card-body d-flex flex-column">
                    <span class="text-muted fw-semibold fs-7">Non assegnati</span>
                    <span class="fs-2hx fw-bold text-warning mt-2">{{$nonAssegnati}}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mb-6">
        <div class="col-xl-4">
            <div class="card card-flush ui-card-surface h-100">
                <div class="card-header pt-6"><h3 class="card-title fw-bold ui-card-title">Distribuzione Stati</h3></div>
                <div class="card-body"><div id="ticket_status_chart" class="ticket-ui-chart"></div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush ui-card-surface h-100">
                <div class="card-header pt-6"><h3 class="card-title fw-bold ui-card-title">Ticket per Assegnatario</h3></div>
                <div class="card-body"><div id="ticket_assignee_chart" class="ticket-ui-chart"></div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-flush ui-card-surface h-100">
                <div class="card-header pt-6"><h3 class="card-title fw-bold ui-card-title">Trend Aperture</h3></div>
                <div class="card-body"><div id="ticket_trend_chart" class="ticket-ui-chart"></div></div>
            </div>
        </div>
    </div>

    <div class="card card-flush ui-card-surface ticket-ui-shell">
        <div id="ticket-loading-overlay" class="ticket-ui-loading">
            <div class="d-flex align-items-center gap-3 px-6 py-4 bg-body rounded">
                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                <span class="fw-semibold text-gray-700">Operazione in corso...</span>
            </div>
        </div>
        <div class="card-header py-5">
            <h3 class="card-title fw-bold ui-card-title">Lista Ticket</h3>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive ui-table-wrap">
                <table id="js-ticket-table" class="table table-row-dashed align-middle gy-4 ui-data-table">
                    <thead>
                    <tr class="fw-bolder fs-7 text-uppercase text-muted">
                        <th>#</th>
                        <th>Data</th>
                        <th>Oggetto</th>
                        <th>Da</th>
                        <th>Assegnato a</th>
                        <th>Ultimo update</th>
                        <th>Stato</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td class="fw-bold">{{$record->uidTicket()}}</td>
                            <td>{{$record->created_at->format('d/m/Y H:i')}}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span>{{$record->oggetto}}</span>
                                    @if($record->causaleTicket)
                                        {!! $record->causaleTicket->labelCausaleTicket() !!}
                                    @else
                                        <span class="badge badge-light">Senza causale</span>
                                    @endif
                                    @if($record->lettura?->messaggio_letto===0)
                                        <span class="badge badge-danger">Nuovo</span>
                                    @endif
                                </div>
                                <div class="text-muted fs-8 mt-1">{{$record->classeServizio()}}</div>
                            </td>
                            <td>{{$record->utente?->nominativo() ?? '-'}}</td>
                            <td>
                                {{$record->assegnatario?->nominativo() ?? '-'}}
                                @if(!$record->agente_id)
                                    <span class="badge badge-light-warning ms-1">Non assegnato</span>
                                @elseif((int)$record->agente_id === (int)Auth::id())
                                    <span class="badge badge-light-success ms-1">In carico a me</span>
                                @endif
                            </td>
                            <td>{{$record->updated_at->format('d/m/Y H:i')}}</td>
                            <td>{!! $record->labelStatoTicket() !!}</td>
                            <td class="text-end" style="white-space: nowrap;">
                                <a class="btn btn-sm btn-light btn-active-light-success" href="{{action([$controller,'show'],$record->id)}}">Vedi</a>
                                @if(Auth::user()?->hasAnyPermission(['admin','supervisore']) && $assegnatariFiltro->count())
                                    <form method="POST" action="{{action([$controller,'update'],$record->id)}}" class="d-inline-flex align-items-center ms-1 js-ticket-assign-form">
                                        @csrf
                                        @method('PATCH')
                                        <select name="agente_id" class="form-select form-select-sm form-select-solid me-1" style="width: 170px;" required>
                                            <option value="">Assegna a...</option>
                                            @foreach($assegnatariFiltro as $assegnatario)
                                                <option value="{{$assegnatario->id}}" {{(int)($record->agente_id ?? 0) === (int)$assegnatario->id ? 'selected' : ''}}>
                                                    {{$assegnatario->cognome}} {{$assegnatario->nome}}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-light-primary">Assegna</button>
                                    </form>
                                @endif
                                @if(Auth::user()?->hasAnyPermission(['admin','supervisore']) && (int)($record->agente_id ?? 0) !== (int)Auth::id())
                                    <form method="POST" action="{{action([$controller,'update'],$record->id)}}" class="d-inline-block ms-1 js-ticket-takeover-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="agente_id" value="{{Auth::id()}}">
                                        <button type="submit" class="btn btn-sm btn-light-warning">Prendi in carico</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10">
                                <div class="ticket-ui-empty p-8 text-center">
                                    <i class="ki-duotone ki-information-4 fs-2tx text-gray-400 mb-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    <div class="fw-bold fs-5 text-gray-700 mb-1">Nessun ticket trovato</div>
                                    <div class="text-muted mb-4">Prova a rimuovere i filtri o crea un nuovo ticket.</div>
                                    <a class="btn btn-sm btn-primary" href="{{action([$controller,'create'])}}">Nuovo Ticket</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-4">{{$records->links()}}</div>
        </div>
    </div>
@endsection

@push('customScript')
    <script>
        $(function () {
            const statusLabels = @json($statusLabels);
            const statusValues = @json($statusValues);
            const assigneeLabels = @json($assigneeCounts->keys()->values());
            const assigneeValues = @json($assigneeCounts->values());
            const trendLabels = @json($trend->keys()->values());
            const trendValues = @json($trend->values());
            const overlay = $('#ticket-loading-overlay');

            function uiPalette() {
                const root = getComputedStyle(document.documentElement);
                return {
                    primary: root.getPropertyValue('--kt-primary').trim() || '#009ef7',
                    success: root.getPropertyValue('--kt-success').trim() || '#50cd89',
                    warning: root.getPropertyValue('--kt-warning').trim() || '#ffc700',
                    danger: root.getPropertyValue('--kt-danger').trim() || '#f1416c',
                    info: root.getPropertyValue('--kt-info').trim() || '#7239ea',
                    gray500: root.getPropertyValue('--kt-gray-500').trim() || '#a1a5b7',
                    gray300: root.getPropertyValue('--kt-gray-300').trim() || '#e4e6ef'
                };
            }

            function renderCharts() {
                if (typeof ApexCharts === 'undefined') {
                    return;
                }
                const palette = uiPalette();

                const statusEl = document.querySelector('#ticket_status_chart');
                if (statusEl) {
                    statusEl.innerHTML = '';
                    new ApexCharts(statusEl, {
                        chart: {type: 'donut', height: 280},
                        series: statusValues,
                        labels: statusLabels,
                        legend: {position: 'bottom'},
                        dataLabels: {enabled: false},
                        colors: [palette.primary, palette.success, palette.warning, palette.danger, palette.info, palette.gray500],
                        noData: {text: 'Nessun dato'}
                    }).render();
                }

                const assigneeEl = document.querySelector('#ticket_assignee_chart');
                if (assigneeEl) {
                    assigneeEl.innerHTML = '';
                    new ApexCharts(assigneeEl, {
                        chart: {type: 'bar', height: 280, toolbar: {show: false}},
                        series: [{name: 'Ticket', data: assigneeValues}],
                        xaxis: {categories: assigneeLabels},
                        plotOptions: {bar: {borderRadius: 4, horizontal: false}},
                        dataLabels: {enabled: false},
                        colors: [palette.primary],
                        grid: {borderColor: palette.gray300},
                        noData: {text: 'Nessun dato'}
                    }).render();
                }

                const trendEl = document.querySelector('#ticket_trend_chart');
                if (trendEl) {
                    trendEl.innerHTML = '';
                    new ApexCharts(trendEl, {
                        chart: {type: 'area', height: 280, toolbar: {show: false}},
                        series: [{name: 'Aperture', data: trendValues}],
                        xaxis: {categories: trendLabels},
                        stroke: {curve: 'smooth', width: 2},
                        dataLabels: {enabled: false},
                        colors: [palette.success],
                        fill: {type: 'gradient', gradient: {opacityFrom: 0.45, opacityTo: 0.05}},
                        grid: {borderColor: palette.gray300},
                        noData: {text: 'Nessun dato'}
                    }).render();
                }
            }

            renderCharts();

            $('#js-ticket-local-search').on('keyup', function () {
                const value = ($(this).val() || '').toLowerCase();
                $('#js-ticket-table tbody tr').each(function () {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(value) !== -1);
                });
            });

            $(document).on('submit', '.js-ticket-assign-form, .js-ticket-takeover-form, .js-ticket-filter-form', function () {
                overlay.css('display', 'flex');
            });

            $(document).on('submit', '.js-ticket-assign-form', function (event) {
                event.preventDefault();
                const form = this;

                Swal.fire({
                    text: 'Confermi la riassegnazione di questo ticket?',
                    icon: 'question',
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: 'Sì, assegna',
                    cancelButtonText: 'Annulla',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-light'
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    } else {
                        overlay.hide();
                    }
                });
            });

            $(document).on('submit', '.js-ticket-takeover-form', function (event) {
                event.preventDefault();
                const form = this;

                Swal.fire({
                    text: 'Confermi la presa in carico di questo ticket?',
                    icon: 'question',
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: 'Sì, conferma',
                    cancelButtonText: 'Annulla',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-light'
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    } else {
                        overlay.hide();
                    }
                });
            });
        });
    </script>
@endpush
