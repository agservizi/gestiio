@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        @include('Backend._components.ricercaIndex')
        <div class="me-4">
            <a href="#" class="btn btn-sm {{ $conFiltro ? 'btn-success' : 'bg-body' }} btn-flex btn-light btn-active-primary fw-bolder" data-kt-menu-trigger="click"
               data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                <span class="svg-icon svg-icon-6 svg-icon-muted me-1">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.0759 3H4.72777C3.95892 3 3.47768 3.83148 3.86067 4.49814L8.56967 12.6949C9.17923 13.7559 9.5 14.9582 9.5 16.1819V19.5072C9.5 20.2189 10.2223 20.7028 10.8805 20.432L13.8805 19.1977C14.2553 19.0435 14.5 18.6783 14.5 18.273V13.8372C14.5 12.8089 14.8171 11.8056 15.408 10.964L19.8943 4.57465C20.3596 3.912 19.8856 3 19.0759 3Z"
                              fill="currentColor"></path>
                    </svg>
                </span>
                Filtri
            </a>
            <div class="menu menu-sub menu-sub-dropdown w-300px p-6" data-kt-menu="true">
                <div class="mb-4">
                    <label class="form-label fw-bold">Ruolo</label>
                    <select id="filtro_ruolo" class="form-select form-select-sm form-select-solid">
                        <option value="">Tutti</option>
                        <option value="agente">Agente</option>
                        <option value="supervisore">Supervisore</option>
                        <option value="operatore">Operatore</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Stato utenza</label>
                    <select id="filtro_stato_utenza" class="form-select form-select-sm form-select-solid">
                        <option value="">Tutti</option>
                        <option value="attivo">Attivo</option>
                        <option value="sospeso">Sospeso</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">2FA</label>
                    <select id="filtro_2fa" class="form-select form-select-sm form-select-solid">
                        <option value="">Tutti</option>
                        <option value="attivo">Attivo</option>
                        <option value="non_attivo">Non attivo</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-bold">Ultimo accesso</label>
                    <select id="filtro_ultimo_accesso" class="form-select form-select-sm form-select-solid">
                        <option value="">Tutti</option>
                        <option value="7gg">Ultimi 7 giorni</option>
                        <option value="30gg">Ultimi 30 giorni</option>
                        <option value="mai">Mai</option>
                    </select>
                </div>
            </div>
        </div>
        <button class="btn btn-sm btn-light" id="reset-filtri-agenti">Reset</button>
        @isset($ordinamenti)
            <div class="me-4 d-none d-md-block">
                <button class="btn btn-sm btn-icon bg-body btn-color-gray-700 btn-active-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
                        data-kt-menu-flip="top-end">
                    <i class="bi bi-sort-down fs-3"></i>
                </button>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-200px py-3" data-kt-menu="true">
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
            <a class="btn btn-sm btn-primary fw-bold" href="{{action([$controller,'create'])}}"><span class="d-md-none">+</span><span
                        class="d-none d-md-block">{{$testoNuovo}}</span></a>
        @endisset
    </div>
@endsection
@section('content')
    <div id="kpi-agenti">
        @include('Backend.Agente._kpi')
    </div>
    <div class="card pt-4">
        <div class="card-body pt-0 pb-5 fs-6" id="tabella">
            @include('Backend.Agente.tabella')
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
                    ruolo: $('#filtro_ruolo').val(),
                    stato_utenza: $('#filtro_stato_utenza').val(),
                    filtro_2fa: $('#filtro_2fa').val(),
                    ultimo_accesso: $('#filtro_ultimo_accesso').val(),
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
                $('#filtro_ruolo').val(query.get('ruolo') || '');
                $('#filtro_stato_utenza').val(query.get('stato_utenza') || '');
                $('#filtro_2fa').val(query.get('filtro_2fa') || '');
                $('#filtro_ultimo_accesso').val(query.get('ultimo_accesso') || '');
            };

            const caricaTabella = function (page = null, syncUrl = true) {
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
                            $('#kpi-agenti').html(base64_decode(response.kpi));
                        }
                    }
                });
            };

            applicaFiltriDaUrl();

            $('#filter_search').on('keyup', function () {
                caricaTabella();
            });

            $('#filtro_ruolo, #filtro_stato_utenza, #filtro_2fa, #filtro_ultimo_accesso').on('change', function () {
                caricaTabella();
            });

            $('#reset-filtri-agenti').on('click', function () {
                $('#filter_search').val('');
                $('#filtro_ruolo').val('');
                $('#filtro_stato_utenza').val('');
                $('#filtro_2fa').val('');
                $('#filtro_ultimo_accesso').val('');
                caricaTabella();
            });

            $(document).on('click', '#tabella .pagination a', function (e) {
                e.preventDefault();
                const href = $(this).attr('href');
                if (!href) {
                    return;
                }
                const page = new URL(href, window.location.origin).searchParams.get('page');
                caricaTabella(page || null);
            });
        });
    </script>
@endpush
