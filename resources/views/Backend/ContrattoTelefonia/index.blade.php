@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        @include('Backend._components.ricercaIndex')
        @if(Auth::user()->hasAnyPermission(['admin','agente','operatore','supervisore']))
            <!--begin::Filtri-->
            <div class="me-4">
                <!--begin::Menu-->
                <a href="#"
                   class="btn btn-sm {{$conFiltro?'btn-success':'bg-body'}} btn-flex btn-light btn-active-primary fw-bolder"
                   data-kt-menu-trigger="click"
                   data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                    <!--begin::Svg Icon | path: icons/duotune/general/gen031.svg-->
                    <span class="svg-icon svg-icon-6 svg-icon-muted me-1">
												<svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                     xmlns="http://www.w3.org/2000/svg">
													<path d="M19.0759 3H4.72777C3.95892 3 3.47768 3.83148 3.86067 4.49814L8.56967 12.6949C9.17923 13.7559 9.5 14.9582 9.5 16.1819V19.5072C9.5 20.2189 10.2223 20.7028 10.8805 20.432L13.8805 19.1977C14.2553 19.0435 14.5 18.6783 14.5 18.273V13.8372C14.5 12.8089 14.8171 11.8056 15.408 10.964L19.8943 4.57465C20.3596 3.912 19.8856 3 19.0759 3Z"
                                                          fill="currentColor"></path>
												</svg>
											</span>
                    <!--end::Svg Icon-->Filtri</a>

                <div class="menu menu-sub menu-sub-dropdown w-250px w-md-350px" data-kt-menu="true" id="filtri-drop">
                    @include('Backend.ContrattoTelefonia.indexFiltri')
                </div>
            </div>
            <!--end::Filtri-->
            @isset($ordinamenti)
                <div class="me-4 d-none d-md-block">
                    <button class="btn btn-sm btn-icon bg-body btn-color-gray-700 btn-active-primary"
                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
                            data-kt-menu-flip="top-end">
                        <i class="bi bi-sort-down fs-3"></i>
                    </button>
                    <!--begin::Menu 3-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-bold w-200px py-3"
                         data-kt-menu="true">
                        <!--begin::Heading-->
                        <div class="menu-item px-3">
                            <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Ordinamento</div>
                        </div>
                        @foreach($ordinamenti as $key=>$ordinamento)
                            <div class="menu-item px-3">
                                <a href="{{Request::url()}}?orderBy={{$key}}"
                                   class="menu-link flex-stack px-3">{{$ordinamento['testo']}}
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
                <a class="btn btn-sm btn-primary fw-bold"
                   href="{{action([$controller,'create'])}}"><span
                            class="d-md-none">+</span><span
                            class="d-none d-md-block">{{$testoNuovo}}</span></a>
            @endisset
        @endif
    </div>
@endsection
@section('content')
    @php
        $contrattiOverview = $contrattiOverview ?? ['totali' => 0, 'mese' => 0, 'bozze' => 0, 'da_gestire' => 0];
    @endphp
    <div class="contracts-page">
        <section class="contracts-hero mb-6">
            <div class="contracts-hero-copy">
                <span>Control room telefonia</span>
                <h1>Contratti, code e segnali in una vista sola.</h1>
                <p>Una pagina fatta per capire subito dove intervenire: nuovi ordini, bozze ferme, pratiche da gestire e storico operativo.</p>
            </div>
            <div class="contracts-hero-actions">
                @if($puoCreare)
                    <a href="{{action([$controller,'create'])}}" class="btn btn-primary">Nuovo contratto</a>
                @endif
                <a href="{{ request()->fullUrlWithQuery(['solo_fermi' => 1, 'giorni_fermo' => $giorniFermo]) }}" class="btn btn-light-warning">Contratti fermi</a>
            </div>
        </section>

        <section class="contracts-kpis mb-6">
            <div class="contracts-kpi contracts-kpi-dark">
                <span>Contratti filtrati</span>
                <strong>{{number_format((int)$contrattiOverview['totali'])}}</strong>
            </div>
            <div class="contracts-kpi">
                <span>Mese corrente</span>
                <strong>{{number_format((int)$contrattiOverview['mese'])}}</strong>
            </div>
            <div class="contracts-kpi">
                <span>Bozze</span>
                <strong>{{number_format((int)$contrattiOverview['bozze'])}}</strong>
            </div>
            <div class="contracts-kpi contracts-kpi-warn">
                <span>Da gestire</span>
                <strong>{{number_format((int)$contrattiOverview['da_gestire'])}}</strong>
            </div>
        </section>

        @if(($contrattiFermiCount ?? 0) > 0)
            <div class="contracts-alert mb-6">
                <div>
                    <strong>{{ number_format($contrattiFermiCount) }} contratti fermi da almeno {{ $giorniFermo }} giorni</strong>
                    <span>Stato bozza o da gestire.</span>
                </div>
                <a href="{{ request()->fullUrlWithQuery(['solo_fermi' => 1, 'giorni_fermo' => $giorniFermo]) }}" class="btn btn-sm btn-warning">Vedi solo fermi</a>
            </div>
        @endif

        <div class="contracts-board">
            <div class="contracts-board-head">
                <div>
                    <span>Registro ordini</span>
                    <h2>Pipeline telefonia</h2>
                </div>
                <div class="contracts-board-meta">
                    <span>{{number_format($records->total())}} movimenti</span>
                    @if($conFiltro)
                        <a href="{{action([$controller,'index'])}}" class="btn btn-sm btn-light">Rimuovi filtri</a>
                    @endif
                </div>
            </div>
            <div class="contracts-table-wrap fs-6" id="tabella">
                @include('Backend.ContrattoTelefonia.tabella')
            </div>
        </div>
    </div>
@endsection
@push('customCss')
    <style>
        .contracts-page {
            --ct-text: #162033;
            --ct-muted: #6c7890;
            --ct-line: #dfe8f3;
            --ct-blue: #0b8fe8;
            --ct-green: #42b883;
            --ct-amber: #f2a93b;
            --ct-ink: #101828;
        }

        .contracts-hero {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 1rem;
            min-height: 210px;
            padding: 1.75rem;
            border: 1px solid var(--ct-line);
            border-radius: 8px;
            background:
                linear-gradient(135deg, rgba(11, 143, 232, .13), rgba(66, 184, 131, .09)),
                repeating-linear-gradient(90deg, rgba(22, 32, 51, .055) 0 1px, transparent 1px 30px),
                #fff;
            overflow: hidden;
        }

        .contracts-hero-copy {
            max-width: 760px;
        }

        .contracts-hero-copy span,
        .contracts-board-head > div > span,
        .contracts-kpi span {
            display: block;
            color: var(--ct-blue);
            font-size: .75rem;
            font-weight: 850;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .contracts-hero-copy h1 {
            max-width: 720px;
            margin: .65rem 0 .85rem;
            color: var(--ct-text);
            font-size: clamp(2.1rem, 3.4vw, 4rem);
            line-height: .98;
            font-weight: 900;
            letter-spacing: 0;
        }

        .contracts-hero-copy p {
            max-width: 620px;
            margin: 0;
            color: var(--ct-muted);
            font-size: 1.05rem;
            line-height: 1.55;
            font-weight: 600;
        }

        .contracts-hero-actions {
            display: flex;
            flex: 0 0 auto;
            align-items: flex-end;
            gap: .65rem;
            white-space: nowrap;
        }

        .contracts-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .85rem;
        }

        .contracts-kpi {
            min-height: 104px;
            padding: 1.1rem 1.2rem;
            border: 1px solid var(--ct-line);
            border-radius: 8px;
            background: #fff;
        }

        .contracts-kpi strong {
            display: block;
            margin-top: .35rem;
            color: var(--ct-text);
            font-size: 2rem;
            line-height: 1;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
        }

        .contracts-kpi-dark {
            background: var(--ct-ink);
            border-color: var(--ct-ink);
        }

        .contracts-kpi-dark span,
        .contracts-kpi-dark strong {
            color: #fff;
        }

        .contracts-kpi-warn {
            border-color: #f7dfaf;
            background: #fffaf0;
        }

        .contracts-alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border: 1px solid #f1d79d;
            border-radius: 8px;
            background: #fff8e8;
        }

        .contracts-alert strong,
        .contracts-alert span {
            display: block;
        }

        .contracts-alert strong {
            color: #7a4d00;
            font-weight: 850;
        }

        .contracts-alert span {
            color: #9b6b16;
            margin-top: .1rem;
            font-weight: 600;
        }

        .contracts-board {
            border: 1px solid var(--ct-line);
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }

        .contracts-board-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.2rem 1.35rem;
            border-bottom: 1px solid var(--ct-line);
            background: #fbfdff;
        }

        .contracts-board-head h2 {
            margin: .2rem 0 0;
            color: var(--ct-text);
            font-size: 1.25rem;
            font-weight: 900;
        }

        .contracts-board-meta {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: var(--ct-muted);
            font-weight: 750;
            white-space: nowrap;
        }

        .contracts-table-wrap {
            padding: .35rem .75rem .95rem;
        }

        .contracts-table-wrap .table {
            margin-bottom: 0;
        }

        .contracts-table-wrap thead th {
            color: #6c7890;
            background: #fbfdff;
            border-bottom: 1px solid var(--ct-line);
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .contracts-table-wrap tbody td {
            border-color: #edf2f7;
            vertical-align: middle;
        }

        @media (max-width: 1199.98px) {
            .contracts-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .contracts-hero,
            .contracts-alert,
            .contracts-board-head {
                align-items: stretch;
                flex-direction: column;
            }

            .contracts-hero-actions,
            .contracts-board-meta {
                align-items: stretch;
                flex-direction: column;
                white-space: normal;
            }

            .contracts-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';

        $(function () {
            searchHandler();
            select2UniversaleBackend('agente', 'un agente', 1, 'agente_id');
            select2UniversaleBackend('gestore', 'un gestore', 1, 'gestore_id');
            $(document).on("click", ".duplica", function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                Swal.fire({
                    title: "Sei sicuro?",
                    text: 'Sei sicuro di voler duplicare questo contratto?',
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Si!",
                    cancelButtonText: "No",
                    reverseButtons: true,
                    customClass: {
                        confirmButton: "btn btn-success",
                        cancelButton: "btn btn-danger"
                    }
                }).then(function (result) {
                    if (result.value) {

                        location.href = url;

                        return true;


                    } else if (result.dismiss === "cancel") {

                    }
                });
            });
        });
    </script>
@endpush
