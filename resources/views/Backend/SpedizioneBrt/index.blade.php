@extends('Backend._layout._main')
@section('toolbar')
    @php($trackingRefreshBulkAvailable = method_exists($controller, 'trackingRefreshBulk'))
    <div class="brt-toolbar">
        <div class="brt-toolbar-left">
        <a class="btn btn-sm brt-btn-secondary"
           href="{{action([\App\Http\Controllers\Backend\ContattoBrtController::class,'index'])}}">Preferiti</a>
        @isset($testoCerca)
            <div class="brt-toolbar-search" data-bs-toggle="tooltip"
                 data-bs-placement="bottom"
                 title="{{$testoCerca}}">
            <span class="brt-toolbar-search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px"
                     height="24px" viewBox="0 0 24 24"
                     version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <rect x="0" y="0" width="24" height="24"/>
                        <path
                                d="M14.2928932,16.7071068 C13.9023689,16.3165825 13.9023689,15.6834175 14.2928932,15.2928932 C14.6834175,14.9023689 15.3165825,14.9023689 15.7071068,15.2928932 L19.7071068,19.2928932 C20.0976311,19.6834175 20.0976311,20.3165825 19.7071068,20.7071068 C19.3165825,21.0976311 18.6834175,21.0976311 18.2928932,20.7071068 L14.2928932,16.7071068 Z"
                                fill="#000000" fill-rule="nonzero" opacity="0.3"/>
                        <path
                                d="M11,16 C13.7614237,16 16,13.7614237 16,11 C16,8.23857625 13.7614237,6 11,6 C8.23857625,6 6,8.23857625 6,11 C6,13.7614237 8.23857625,16 11,16 Z M11,18 C7.13400675,18 4,14.8659932 4,11 C4,7.13400675 7.13400675,4 11,4 C14.8659932,4 18,7.13400675 18,11 C18,14.8659932 14.8659932,18 11,18 Z"
                                fill="#000000" fill-rule="nonzero"/>
                    </g>
                </svg>
            </span>
                <input type="text" id="filter_search"
                       class="form-control form-control-sm form-control-solid brt-toolbar-input"
                       placeholder="Ricerca"/>
            </div>
        @endisset
        </div>
        <div class="brt-toolbar-actions">
        @isset($ordinamenti)
            <div class="d-none d-md-block">
                <button class="btn btn-sm btn-icon bg-body btn-color-gray-700 btn-active-primary brt-sort-button"
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
        <a class="btn btn-sm brt-btn-secondary disabled" data-targetZ="kt_modal" data-toggleZ="modal-ajax"
           href="{{action([$controller,'bordero'])}}" id="bordero">Crea borderò</a>
        @if($trackingRefreshBulkAvailable)
            <button class="btn btn-sm brt-btn-secondary disabled" id="tracking-refresh-bulk"
                data-url="{{action([$controller,'trackingRefreshBulk'])}}" type="button">Aggiorna tracking selezionati
            </button>
        @endif
        @can('agente')
            <a class="btn btn-sm brt-btn-secondary"
               href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'create'],['servizio_type'=>'spedizione-brt'])}}">Apri
                ticket</a>
        @endif
        <a class="btn btn-sm brt-btn-primary" data-target="kt_modal" data-toggle="modal-ajax"
           href="{{action([$controller,'create'])}}"><span class="d-md-none">+</span><span
                    class="d-none d-md-block">{{$testoNuovo}}</span></a>
        </div>
    </div>
@endsection
@section('content')
    @php
        $collection = collect($records->items());
        $withTracking = $collection->filter(fn($record) => filled($record->tracking_number))->count();
        $withBordero = $collection->filter(fn($record) => filled($record->bordero_id))->count();
        $errorCount = $collection->filter(fn($record) => strtoupper((string) $record->esito) === 'ERROR')->count();
    @endphp
    <div class="brt-index-page">
        <section class="brt-hero-card mb-8">
            <div class="brt-hero-copy">
                <div class="brt-hero-kicker">Spedizioni BRT</div>
                <h1 class="brt-hero-title">Gestisci spedizioni, bordero e tracking da un'unica schermata.</h1>
                <p class="brt-hero-text">Consulta l'elenco delle spedizioni, aggiorna il tracking e prepara nuove spedizioni mantenendo il flusso operativo gia presente.</p>
                <div class="brt-hero-badges">
                    <span class="brt-doc-badge">Preferiti</span>
                    <span class="brt-doc-badge">Bordero</span>
                    <span class="brt-doc-badge">Tracking</span>
                </div>
            </div>
            <div class="brt-kpi-grid">
                <div class="brt-kpi-card">
                    <span class="brt-kpi-label">Spedizioni pagina</span>
                    <span class="brt-kpi-value">{{$collection->count()}}</span>
                </div>
                <div class="brt-kpi-card">
                    <span class="brt-kpi-label">Con tracking</span>
                    <span class="brt-kpi-value">{{$withTracking}}</span>
                </div>
                <div class="brt-kpi-card">
                    <span class="brt-kpi-label">Con bordero</span>
                    <span class="brt-kpi-value">{{$withBordero}}</span>
                </div>
                <div class="brt-kpi-card">
                    <span class="brt-kpi-label">Con errore</span>
                    <span class="brt-kpi-value">{{$errorCount}}</span>
                </div>
            </div>
        </section>

        <div class="brt-table-shell">
            <div class="brt-table-shell-head">
                <div>
                    <h2 class="brt-table-title">Elenco spedizioni</h2>
                    <p class="brt-table-text">Controlla destinatari, prezzi, bordero e stato tracking in un unico elenco.</p>
                </div>
            </div>
            <div class="card-body pt-0 pb-5 fs-6" id="tabella">
                @include('Backend.SpedizioneBrt.tabella')
            </div>
        </div>
    </div>
@endsection
@push('customCss')
    <style>
        .brt-index-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .brt-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            width: 100%;
        }

        .brt-toolbar-left,
        .brt-toolbar-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .brt-toolbar-search {
            position: relative;
            min-width: 240px;
        }

        .brt-toolbar-search-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            z-index: 2;
        }

        .brt-toolbar-input {
            min-height: 46px;
            padding-left: 3rem !important;
            border-radius: 999px;
            border: 1px solid #d8dce6;
            background: #fff !important;
            font-weight: 700;
        }

        .brt-btn-primary,
        .brt-btn-secondary {
            min-height: 44px;
            padding: .72rem 1rem;
            border-radius: 999px;
            font-weight: 700;
        }

        .brt-btn-primary {
            color: #fff;
            background: #0f1b3d;
            border: 1px solid #0f1b3d;
        }

        .brt-btn-primary:hover {
            color: #fff;
            background: #1d2d61;
            border-color: #1d2d61;
        }

        .brt-btn-secondary {
            color: #0f1b3d;
            background: #edf2ff;
            border: 1px solid #cad7ff;
        }

        .brt-hero-card {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(320px, 1fr);
            gap: 1.5rem;
            padding: 2rem;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(99, 129, 255, 0.28), transparent 28%),
                linear-gradient(135deg, #0f1b3d 0%, #162654 58%, #1b2f67 100%);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .brt-hero-kicker {
            display: inline-flex;
            margin-bottom: .85rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            color: #b9cbff;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .brt-hero-title {
            max-width: 14ch;
            margin-bottom: .85rem;
            font-size: clamp(2rem, 3vw, 3.1rem);
            line-height: 1.05;
            font-weight: 800;
            color: #fff;
        }

        .brt-hero-text {
            max-width: 60ch;
            margin-bottom: 1.15rem;
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
        }

        .brt-hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }

        .brt-doc-badge {
            padding: .55rem .8rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: #fff;
            font-size: .9rem;
            font-weight: 600;
        }

        .brt-kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem;
            align-content: start;
        }

        .brt-kpi-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 118px;
            padding: 1rem 1.1rem;
            border-radius: 18px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .brt-kpi-label {
            color: rgba(255,255,255,0.72);
            font-size: .88rem;
            font-weight: 600;
        }

        .brt-kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            color: #fff;
        }

        .brt-table-shell {
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfd 100%);
            border-radius: 22px;
            border: 1px solid #e3e6ec;
            box-shadow: 0 20px 60px rgba(22, 28, 45, 0.06);
            overflow: hidden;
        }

        .brt-table-shell-head {
            padding: 1.5rem 1.75rem .75rem;
        }

        .brt-table-title {
            margin: 0 0 .25rem;
            font-size: 1.6rem;
            font-weight: 800;
            color: #1f2430;
        }

        .brt-table-text {
            margin: 0;
            color: #697181;
        }

        .brt-table-responsive {
            padding: 0 1.75rem 1.5rem;
        }

        #tabella-elenco thead th {
            padding: .95rem .85rem 1rem;
            border-bottom: 1px solid #e9edf3;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        #tabella-elenco tbody td {
            padding: 1rem .85rem;
            border-bottom: 1px solid #edf1f6;
            vertical-align: middle;
        }

        #tabella-elenco tbody tr:hover {
            background: #f8faff;
        }

        .brt-cell-title,
        .brt-price-value,
        .brt-tracking-code {
            font-weight: 700;
            color: #1d2430;
        }

        .brt-cell-subtitle,
        .brt-updated-label {
            color: #6d7585;
            font-size: .88rem;
        }

        .brt-cell-stack,
        .brt-tracking-stack {
            display: flex;
            flex-direction: column;
            gap: .18rem;
        }

        .brt-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: .35rem .75rem;
            border-radius: 999px;
            font-size: .84rem;
            font-weight: 700;
            background: #eef2ff;
            color: #28408b;
        }

        .brt-agent-pill {
            background: #f2f4f8;
            color: #475164;
        }

        .brt-action-group {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .2rem;
            border-radius: 999px;
            background: #f6f8fb;
            border: 1px solid #e7ebf1;
        }

        @media (max-width: 991px) {
            .brt-toolbar,
            .brt-toolbar-left,
            .brt-toolbar-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .brt-hero-card {
                grid-template-columns: 1fr;
            }

            .brt-toolbar-search {
                width: 100%;
            }

            .brt-table-responsive {
                padding: 0 1rem 1rem;
            }
        }
    </style>
@endpush
@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';
        var array = [];
        var csrfToken = '{{csrf_token()}}';

        $(function () {
            searchHandler();

            function notify(icon, title, text) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                        icon: icon,
                        title: title,
                        text: text || ''
                    });
                    return;
                }

                if (text) {
                    console.log(title + ': ' + text);
                } else {
                    console.log(title);
                }
            }

            $(document).on('change', '.sel', function () {
                aggiornaSelezione();
            });

            $('#bordero').click(function (e) {
                e.preventDefault();
                location.href = $(this).attr('href') + '?id=' + array.join(',');
            });

            $(document).on('click', '#tutti', function () {
                const checked = $(this).is(':checked');
                $('.sel').prop('checked', checked);
                aggiornaSelezione();

            });

            $(document).on('click', '.tracking-refresh-row', function () {
                var button = $(this);
                var row = button.closest('tr');
                var url = button.data('url');
                if (!url) {
                    return;
                }

                button.prop('disabled', true).addClass('disabled');

                $.post(url, {_token: csrfToken})
                    .done(function (res) {
                        if (!res || !res.success) {
                            notify('error', 'Errore', (res && res.message) ? res.message : 'Errore durante aggiornamento tracking');
                            return;
                        }

                        row.find('.tracking-cell').html(res.trackingHtml || '-');
                        row.find('.tracking-status-cell').html(res.trackingStatusHtml || '<span class="badge badge-light">-</span>');
                        row.find('.tracking-updated-cell').text(res.trackingUpdatedAt || '-');
                        notify('success', 'Tracking aggiornato', res.message || 'Aggiornamento completato');
                    })
                    .fail(function () {
                        notify('error', 'Errore', 'Errore durante aggiornamento tracking');
                    })
                    .always(function () {
                        button.prop('disabled', false).removeClass('disabled');
                    });
            });

            $('#tracking-refresh-bulk').click(function () {
                if (!array.length) {
                    return;
                }

                var button = $(this);
                button.prop('disabled', true).addClass('disabled');

                $.post(button.data('url'), {
                    _token: csrfToken,
                    ids: array
                }).done(function (res) {
                    if (!res || !res.success) {
                        notify('error', 'Errore', (res && res.message) ? res.message : 'Errore durante aggiornamento tracking massivo');
                        return;
                    }

                    if (res.rows) {
                        Object.keys(res.rows).forEach(function (id) {
                            var rowData = res.rows[id] || {};
                            var row = $('tr[data-id="' + id + '"]');
                            if (!row.length) {
                                return;
                            }
                            row.find('.tracking-cell').html(rowData.trackingHtml || '-');
                            row.find('.tracking-status-cell').html(rowData.trackingStatusHtml || '<span class="badge badge-light">-</span>');
                            row.find('.tracking-updated-cell').text(rowData.trackingUpdatedAt || '-');
                        });
                    }

                    notify('success', 'Tracking aggiornato', res.message || 'Aggiornamento tracking completato');
                }).fail(function () {
                    notify('error', 'Errore', 'Errore durante aggiornamento tracking massivo');
                }).always(function () {
                    button.prop('disabled', false).removeClass('disabled');
                });
            });

            aggiornaSelezione();


            function aggiornaSelezione() {
                array = []
                $('.sel:checked').each(function () {
                    array.push($(this).val());
                });

                var quantio = array.length;

                if (quantio) {
                    $('#bordero').removeClass('disabled');
                    if ($('#tracking-refresh-bulk').length) {
                        $('#tracking-refresh-bulk').removeClass('disabled').prop('disabled', false);
                    }
                } else {
                    $('#bordero').addClass('disabled');
                    if ($('#tracking-refresh-bulk').length) {
                        $('#tracking-refresh-bulk').addClass('disabled').prop('disabled', true);
                    }
                }
            }
        });
    </script>
@endpush
