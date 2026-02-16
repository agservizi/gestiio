@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        @include('Backend._components.ricercaIndex')
        <div class="me-4">
            <a href="#" class="btn btn-sm {{$conFiltro?'btn-success':'bg-body'}} btn-flex btn-light btn-active-primary fw-bolder" data-kt-menu-trigger="click"
               data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                <span class="svg-icon svg-icon-6 svg-icon-muted me-1">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.0759 3H4.72777C3.95892 3 3.47768 3.83148 3.86067 4.49814L8.56967 12.6949C9.17923 13.7559 9.5 14.9582 9.5 16.1819V19.5072C9.5 20.2189 10.2223 20.7028 10.8805 20.432L13.8805 19.1977C14.2553 19.0435 14.5 18.6783 14.5 18.273V13.8372C14.5 12.8089 14.8171 11.8056 15.408 10.964L19.8943 4.57465C20.3596 3.912 19.8856 3 19.0759 3Z" fill="currentColor"></path>
                    </svg>
                </span>
                Filtri
            </a>
            <div class="menu menu-sub menu-sub-dropdown w-350px p-6" data-kt-menu="true" id="filtri-visura-drop">
                <div class="mb-4">
                    <label class="form-label fw-bold">Stato pratica</label>
                    <select id="filtro_esito" class="form-select form-select-sm form-select-solid">
                        <option value="">Tutti</option>
                        @foreach(\App\Models\EsitoVisura::orderBy('nome')->get() as $esito)
                            <option value="{{$esito->id}}">{{$esito->nome}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Tipo visura</label>
                    <select id="filtro_tipo_visura" class="form-select form-select-sm form-select-solid">
                        <option value="">Tutti</option>
                        @foreach(\App\Models\TipoVisura::orderBy('nome')->get() as $tipo)
                            <option value="{{$tipo->id}}">{{$tipo->nome}}</option>
                        @endforeach
                    </select>
                </div>
                @if($agentiFiltro->count())
                    <div class="mb-4">
                        <label class="form-label fw-bold">Agente</label>
                        <select id="filtro_agente" class="form-select form-select-sm form-select-solid">
                            <option value="">Tutti</option>
                            @foreach($agentiFiltro as $agente)
                                <option value="{{$agente->id}}">{{$agente->cognome}} {{$agente->nome}}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="row g-4 mb-4">
                    <div class="col-6">
                        <label class="form-label fw-bold">Data da</label>
                        <input type="date" id="filtro_data_da" class="form-control form-control-sm form-control-solid">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">Data a</label>
                        <input type="date" id="filtro_data_a" class="form-control form-control-sm form-control-solid">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Attenzione pratica</label>
                    <select id="filtro_sla" class="form-select form-select-sm form-select-solid">
                        <option value="">Tutte</option>
                        <option value="attenzione">Solo pratiche aperte da oltre 3 giorni</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold">Allegati</label>
                    <select id="filtro_allegati" class="form-select form-select-sm form-select-solid">
                        <option value="">Tutti</option>
                        <option value="1">Con allegati</option>
                        <option value="0">Senza allegati</option>
                    </select>
                </div>
            </div>
        </div>
        <button class="btn btn-sm btn-light" id="reset-filtri-visura">Reset</button>
        @isset($ordinamenti)
            <div class="me-4 d-none d-md-block">
                <button class="btn btn-sm btn-icon bg-body btn-color-gray-700 btn-active-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
                        data-kt-menu-flip="top-end">
                    <i class="bi bi-sort-down fs-3"></i>
                </button>
                <!--begin::Menu 3-->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-200px py-3" data-kt-menu="true">
                    <!--begin::Heading-->
                    <div class="menu-item px-3">
                        <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Ordinamento</div>
                    </div>
                    @foreach($ordinamenti as $key=>$ordinamento)
                        <div class="menu-item px-3">
                            <a href="{{Request::url()}}?orderBy={{$key}}" class="menu-link flex-stack px-3">{{$ordinamento['testo']}}
                                @if($key==$orderBy)
                                    <i class="fas fa-check ms-2 fs-7"></i>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endisset
        @isset($testoNuovo)
            <a class="btn btn-sm btn-primary fw-bold" data-target="kt_modal" data-toggle="modal-ajax" href="{{action([$controller,'create'])}}"><span class="d-md-none">+</span><span
                        class="d-none d-md-block">{{$testoNuovo}}</span></a>
        @endisset
    </div>
@endsection
@section('content')
    <div id="kpi-visura">
        @include('Backend.Visura._kpi')
    </div>
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            @include('Backend.Visura.tabella')
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';

        $(function () {
            const leggiFiltri = function () {
                return {
                    cerca: $('#filter_search').val(),
                    esito_id: $('#filtro_esito').val(),
                    tipo_visura_id: $('#filtro_tipo_visura').val(),
                    agente_id: $('#filtro_agente').length ? $('#filtro_agente').val() : '',
                    data_da: $('#filtro_data_da').val(),
                    data_a: $('#filtro_data_a').val(),
                    filtro_sla: $('#filtro_sla').val(),
                    con_allegati: $('#filtro_allegati').val(),
                    orderBy: new URLSearchParams(window.location.search).get('orderBy') || '{{$orderBy}}'
                };
            };

            const serializzaFiltri = function (payload) {
                const query = new URLSearchParams();
                Object.entries(payload).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        query.set(key, value);
                    }
                });
                return query.toString();
            };

            const applicaFiltriDaUrl = function () {
                const query = new URLSearchParams(window.location.search);
                $('#filter_search').val(query.get('cerca') || '');
                $('#filtro_esito').val(query.get('esito_id') || '');
                $('#filtro_tipo_visura').val(query.get('tipo_visura_id') || '');
                $('#filtro_agente').val(query.get('agente_id') || '');
                $('#filtro_data_da').val(query.get('data_da') || '');
                $('#filtro_data_a').val(query.get('data_a') || '');
                $('#filtro_sla').val(query.get('filtro_sla') || '');
                $('#filtro_allegati').val(query.get('con_allegati') || '');
            };

            const caricaElenco = function (page = null, syncUrl = true) {
                const payload = leggiFiltri();
                if (page) {
                    payload.page = page;
                }

                if (syncUrl) {
                    const queryString = serializzaFiltri(payload);
                    const nextUrl = queryString ? (indexUrl + '?' + queryString) : indexUrl;
                    window.history.replaceState({}, '', nextUrl);
                }

                $.ajax({
                    url: indexUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: payload,
                    success: function (response) {
                        $('#tabella').html(base64_decode(response.html));
                        if (response.kpi) {
                            $('#kpi-visura').html(base64_decode(response.kpi));
                        }
                    }
                });
            };

            applicaFiltriDaUrl();

            $('#filter_search').on('keyup', function () {
                caricaElenco();
            });

            $('#filtro_esito, #filtro_tipo_visura, #filtro_agente, #filtro_data_da, #filtro_data_a, #filtro_sla, #filtro_allegati').on('change', function () {
                caricaElenco();
            });

            $('#reset-filtri-visura').on('click', function () {
                $('#filter_search').val('');
                $('#filtro_esito').val('');
                $('#filtro_tipo_visura').val('');
                $('#filtro_agente').val('');
                $('#filtro_data_da').val('');
                $('#filtro_data_a').val('');
                $('#filtro_sla').val('');
                $('#filtro_allegati').val('');
                caricaElenco();
            });

            $(document).on('click', '#tabella .pagination a', function (e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (!href) {
                    return;
                }
                const page = new URL(href, window.location.origin).searchParams.get('page');
                caricaElenco(page || null);
            });
        });
    </script>
@endpush
