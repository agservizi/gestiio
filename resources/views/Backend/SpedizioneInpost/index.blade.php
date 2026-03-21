@extends('Backend._layout._main')
@section('toolbar')
    <div class="inpost-toolbar">
        <div class="inpost-toolbar-search" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{$testoCerca}}">
            <span class="inpost-toolbar-search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M14.2929 16.7071C13.9024 16.3166 13.9024 15.6834 14.2929 15.2929C14.6834 14.9024 15.3166 14.9024 15.7071 15.2929L19.7071 19.2929C20.0976 19.6834 20.0976 20.3166 19.7071 20.7071C19.3166 21.0976 18.6834 21.0976 18.2929 20.7071L14.2929 16.7071Z" fill="currentColor" opacity="0.35"/>
                    <path d="M11 16C13.7614 16 16 13.7614 16 11C16 8.23858 13.7614 6 11 6C8.23858 6 6 8.23858 6 11C6 13.7614 8.23858 16 11 16ZM11 18C7.13401 18 4 14.866 4 11C4 7.13401 7.13401 4 11 4C14.866 4 18 7.13401 18 11C18 14.866 14.866 18 11 18Z" fill="currentColor"/>
                </svg>
            </span>
            <input type="text" id="filter_search" class="form-control form-control-solid inpost-toolbar-input" placeholder="Ricerca per destinatario, mittente o tracking"/>
        </div>
        <div class="inpost-toolbar-actions">
            <button class="btn btn-sm inpost-btn-secondary disabled" id="tracking-refresh-bulk"
                    data-url="{{action([$controller,'trackingRefreshBulk'])}}" type="button">Aggiorna tracking selezionati
            </button>
            <a class="btn btn-sm inpost-btn-primary" href="{{action([$controller,'create'])}}">{{$testoNuovo}}</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $collection = collect($records->items());
        $pointCount = $collection->where('delivery_type', 'point')->count();
        $addressCount = $collection->where('delivery_type', 'address')->count();
        $trackingCount = $collection->filter(fn($record) => filled($record->tracking_number))->count();
        $createdCount = $collection->filter(fn($record) => strtoupper((string) $record->esito) === 'CREATED')->count();
    @endphp
    <div class="inpost-index-page">
        <section class="inpost-hero-card mb-8">
            <div class="inpost-hero-copy">
                <div class="inpost-hero-kicker">InPost Shipping Workspace</div>
                <h1 class="inpost-hero-title">Spedizioni, punti di ritiro e tracking in un unico flusso operativo.</h1>
                <p class="inpost-hero-text">Interfaccia pensata per gestire punti di ritiro, creazione spedizioni e monitoraggio tracking in un unico flusso operativo.</p>
                <div class="inpost-hero-badges">
                    <span class="inpost-doc-badge">Punti InPost</span>
                    <span class="inpost-doc-badge">Creazione spedizioni</span>
                    <span class="inpost-doc-badge">Tracking spedizioni</span>
                </div>
            </div>
            <div class="inpost-kpi-grid">
                <div class="inpost-kpi-card">
                    <span class="inpost-kpi-label">Spedizioni pagina</span>
                    <span class="inpost-kpi-value">{{$collection->count()}}</span>
                </div>
                <div class="inpost-kpi-card">
                    <span class="inpost-kpi-label">Address to Point</span>
                    <span class="inpost-kpi-value">{{$pointCount}}</span>
                </div>
                <div class="inpost-kpi-card">
                    <span class="inpost-kpi-label">Address to Address</span>
                    <span class="inpost-kpi-value">{{$addressCount}}</span>
                </div>
                <div class="inpost-kpi-card">
                    <span class="inpost-kpi-label">Con tracking</span>
                    <span class="inpost-kpi-value">{{$trackingCount}}</span>
                </div>
                <div class="inpost-kpi-card">
                    <span class="inpost-kpi-label">Esito created</span>
                    <span class="inpost-kpi-value">{{$createdCount}}</span>
                </div>
            </div>
        </section>

        <div class="inpost-table-shell">
            <div class="inpost-table-shell-head">
                <div>
                    <h2 class="inpost-table-title">Queue spedizioni</h2>
                    <p class="inpost-table-text">Monitora esito API, tracking e tipologia di consegna con aggiornamenti massivi.</p>
                </div>
            </div>
            <div class="card-body pt-0 pb-5 fs-6" id="tabella">
                @include('Backend.SpedizioneInpost.tabella')
            </div>
        </div>
    </div>
@endsection

@push('customCss')
    <style>
        .inpost-index-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .inpost-hero-card {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(320px, 1fr);
            gap: 1.5rem;
            padding: 2rem;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(255, 199, 0, 0.24), transparent 28%),
                linear-gradient(135deg, #191d24 0%, #232933 58%, #2d3541 100%);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.08);
            overflow: hidden;
        }

        .inpost-hero-kicker {
            display: inline-flex;
            margin-bottom: .85rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            color: #ffd84d;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .inpost-hero-title {
            max-width: 14ch;
            margin-bottom: .85rem;
            font-size: clamp(2rem, 3vw, 3.2rem);
            line-height: 1.05;
            font-weight: 800;
            color: #fff;
        }

        .inpost-hero-text {
            max-width: 60ch;
            margin-bottom: 1.15rem;
            color: rgba(255,255,255,0.76);
            font-size: 1.02rem;
        }

        .inpost-hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }

        .inpost-doc-badge {
            padding: .55rem .8rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: #fff;
            font-size: .9rem;
            font-weight: 600;
        }

        .inpost-kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem;
            align-content: start;
        }

        .inpost-kpi-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 118px;
            padding: 1rem 1.1rem;
            border-radius: 18px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
        }

        .inpost-kpi-label {
            color: rgba(255,255,255,0.68);
            font-size: .88rem;
            font-weight: 600;
        }

        .inpost-kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            color: #fff;
        }

        .inpost-table-shell {
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfd 100%);
            border-radius: 22px;
            border: 1px solid #e3e6ec;
            box-shadow: 0 20px 60px rgba(22, 28, 45, 0.06);
            overflow: hidden;
        }

        .inpost-table-responsive {
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

        #tabella-elenco tbody tr {
            transition: background-color .18s ease;
        }

        #tabella-elenco tbody tr:hover {
            background: #fffdf2;
        }

        .inpost-recipient-name,
        .inpost-destination-city,
        .inpost-metric-value,
        .inpost-tracking-code {
            font-weight: 700;
            color: #1d2430;
        }

        .inpost-recipient-meta,
        .inpost-destination-meta,
        .inpost-status-caption,
        .inpost-updated-label {
            color: #6d7585;
            font-size: .88rem;
        }

        .inpost-recipient-cell,
        .inpost-destination-cell,
        .inpost-status-stack,
        .inpost-tracking-stack {
            display: flex;
            flex-direction: column;
            gap: .18rem;
        }

        .inpost-type-pill,
        .inpost-metric-pill,
        .inpost-agent-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: .35rem .75rem;
            border-radius: 999px;
            font-size: .84rem;
            font-weight: 700;
        }

        .inpost-type-pill.is-point {
            background: #fff7d1;
            color: #5a4400;
        }

        .inpost-type-pill.is-address {
            background: #eef2f8;
            color: #374154;
        }

        .inpost-metric-pill {
            background: #f5f7fb;
            color: #1f2735;
            min-width: 42px;
        }

        .inpost-agent-pill {
            background: #f2f4f8;
            color: #475164;
        }

        .inpost-action-group {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .2rem;
            border-radius: 999px;
            background: #f6f8fb;
            border: 1px solid #e7ebf1;
        }

        .inpost-table-shell-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 1.75rem .75rem;
        }

        .inpost-table-title {
            margin: 0 0 .25rem;
            font-size: 1.6rem;
            font-weight: 800;
            color: #1f2430;
        }

        .inpost-table-text {
            margin: 0;
            color: #697181;
        }

        .inpost-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            width: 100%;
        }

        .inpost-toolbar-search {
            position: relative;
            flex: 1 1 auto;
            max-width: 460px;
        }

        .inpost-toolbar-search-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: #596171;
            z-index: 2;
        }

        .inpost-toolbar-input {
            min-height: 46px;
            padding-left: 3rem !important;
            border-radius: 999px;
            border: 1px solid #d9dde6;
            background: #fff !important;
            font-weight: 600;
        }

        .inpost-toolbar-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .inpost-btn-primary,
        .inpost-btn-secondary {
            min-height: 44px;
            padding: .7rem 1rem;
            border-radius: 999px;
            font-weight: 700;
        }

        .inpost-btn-primary {
            background: #11151c;
            color: #fff;
            border: 1px solid #11151c;
        }

        .inpost-btn-primary:hover {
            color: #fff;
            background: #1d232c;
            border-color: #1d232c;
        }

        .inpost-btn-secondary {
            background: #fff7d1;
            color: #4c3d00;
            border: 1px solid #f4dc77;
        }

        @media (max-width: 991px) {
            .inpost-hero-card {
                grid-template-columns: 1fr;
            }

            .inpost-table-responsive {
                padding: 0 1rem 1rem;
            }

            .inpost-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .inpost-toolbar-search {
                max-width: none;
            }

            .inpost-toolbar-actions {
                justify-content: stretch;
            }

            .inpost-toolbar-actions > * {
                flex: 1 1 auto;
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
                    Swal.fire({toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true, icon: icon, title: title, text: text || ''});
                    return;
                }
                console.log(title + (text ? ': ' + text : ''));
            }

            $(document).on('change', '.sel', aggiornaSelezione);
            $(document).on('click', '#tutti', function () {
                $('.sel').prop('checked', $(this).is(':checked'));
                aggiornaSelezione();
            });

            $(document).on('click', '.tracking-refresh-row', function () {
                var button = $(this);
                var row = button.closest('tr');
                $.post(button.data('url'), {_token: csrfToken})
                    .done(function (res) {
                        if (!res || !res.success) {
                            notify('error', 'Errore', res && res.message ? res.message : 'Errore tracking');
                            return;
                        }
                        row.find('.tracking-cell').html(res.trackingHtml || '-');
                        row.find('.tracking-status-cell').html(res.trackingStatusHtml || '<span class="badge badge-light">-</span>');
                        row.find('.tracking-updated-cell').text(res.trackingUpdatedAt || '-');
                    })
                    .fail(function () {
                        notify('error', 'Errore', 'Errore tracking');
                    });
            });

            $('#tracking-refresh-bulk').click(function () {
                if (!array.length) {
                    return;
                }

                var button = $(this);
                $.post(button.data('url'), {_token: csrfToken, ids: array})
                    .done(function (res) {
                        if (!res || !res.success) {
                            notify('error', 'Errore', res && res.message ? res.message : 'Errore tracking massivo');
                            return;
                        }
                        if (res.rows) {
                            Object.keys(res.rows).forEach(function (id) {
                                var row = $('tr[data-id="' + id + '"]');
                                var rowData = res.rows[id] || {};
                                row.find('.tracking-cell').html(rowData.trackingHtml || '-');
                                row.find('.tracking-status-cell').html(rowData.trackingStatusHtml || '<span class="badge badge-light">-</span>');
                                row.find('.tracking-updated-cell').text(rowData.trackingUpdatedAt || '-');
                            });
                        }
                    });
            });

            function aggiornaSelezione() {
                array = [];
                $('.sel:checked').each(function () {
                    array.push($(this).val());
                });

                if (array.length) {
                    $('#tracking-refresh-bulk').removeClass('disabled').prop('disabled', false);
                } else {
                    $('#tracking-refresh-bulk').addClass('disabled').prop('disabled', true);
                }
            }
        });
    </script>
@endpush
