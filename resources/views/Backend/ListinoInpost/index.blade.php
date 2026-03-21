@extends('Backend._layout._main')
@section('toolbar')
    <div class="inpost-listino-toolbar">
        @isset($testoNuovo)
            <a class="btn btn-sm inpost-listino-btn" href="{{action([$controller,'create'])}}">
                <span class="d-none d-md-block">{{$testoNuovo}}</span>
            </a>
        @endisset
    </div>
@endsection
@section('content')
    @php
        $recordItems = $records instanceof \Illuminate\Contracts\Pagination\Paginator ? collect($records->items()) : collect($records);
        $configuredPoint = $recordItems->filter(fn($record) => filled($record->locker_point))->count();
        $configuredAddress = $recordItems->filter(fn($record) => filled($record->home_delivery))->count();
    @endphp
    <div class="inpost-listino-page">
        <section class="inpost-listino-hero mb-8">
            <div>
                <div class="inpost-listino-kicker">Listino InPost</div>
                <h1 class="inpost-listino-title">Configura i prezzi dei package standard InPost.</h1>
                <p class="inpost-listino-text">Ogni package puo avere un prezzo per locker o punto di ritiro e un prezzo per consegna a indirizzo, collegati al plafond spedizioni.</p>
            </div>
            <div class="inpost-listino-kpis">
                <div class="inpost-listino-kpi">
                    <span class="inpost-listino-kpi-label">Package</span>
                    <span class="inpost-listino-kpi-value">{{$recordItems->count()}}</span>
                </div>
                <div class="inpost-listino-kpi">
                    <span class="inpost-listino-kpi-label">Prezzi punto</span>
                    <span class="inpost-listino-kpi-value">{{$configuredPoint}}</span>
                </div>
                <div class="inpost-listino-kpi">
                    <span class="inpost-listino-kpi-label">Prezzi indirizzo</span>
                    <span class="inpost-listino-kpi-value">{{$configuredAddress}}</span>
                </div>
            </div>
        </section>

        <div class="inpost-listino-shell">
            <div class="inpost-listino-shell-head">
                <div>
                    <h2 class="inpost-listino-shell-title">Tariffe package</h2>
                    <p class="inpost-listino-shell-text">Modifica rapidamente i tre package standard e i relativi prezzi di spedizione.</p>
                </div>
            </div>
            <div class="card-body pt-0 pb-5 fs-6 inpost-listino-shell-body" id="tabella">
                @include('Backend.ListinoInpost.tabella')
            </div>
        </div>
    </div>
@endsection
@push('customCss')
    <style>
        .inpost-listino-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .inpost-listino-toolbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            width: 100%;
        }

        .inpost-listino-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: .72rem 1rem;
            border-radius: 999px;
            font-weight: 700;
            color: #fff;
            background: #11151c;
            border: 1px solid #11151c;
        }

        .inpost-listino-btn:hover {
            color: #fff;
            background: #1d232c;
            border-color: #1d232c;
        }

        .inpost-listino-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(280px, 1fr);
            gap: 1.5rem;
            padding: 2rem;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(255, 199, 0, 0.24), transparent 28%),
                linear-gradient(135deg, #191d24 0%, #232933 58%, #2d3541 100%);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .inpost-listino-kicker {
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

        .inpost-listino-title {
            max-width: 14ch;
            margin-bottom: .75rem;
            font-size: clamp(2rem, 3vw, 3rem);
            line-height: 1.05;
            font-weight: 800;
            color: #fff;
        }

        .inpost-listino-text {
            max-width: 60ch;
            margin-bottom: 0;
            color: rgba(255,255,255,0.78);
            font-size: 1rem;
        }

        .inpost-listino-kpis {
            display: grid;
            grid-template-columns: 1fr;
            gap: .9rem;
        }

        .inpost-listino-kpi {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            min-height: 96px;
            padding: 1rem 1.1rem;
            border-radius: 18px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .inpost-listino-kpi-label {
            color: rgba(255,255,255,0.72);
            font-size: .88rem;
            font-weight: 600;
        }

        .inpost-listino-kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            color: #fff;
        }

        .inpost-listino-shell {
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfd 100%);
            border-radius: 22px;
            border: 1px solid #e3e6ec;
            box-shadow: 0 20px 60px rgba(22, 28, 45, 0.06);
            overflow: hidden;
        }

        .inpost-listino-shell-head {
            padding: 1.5rem 1.75rem .75rem;
        }

        .inpost-listino-shell-body {
            padding-left: 1.75rem;
            padding-right: 1.75rem;
        }

        .inpost-listino-table {
            margin-bottom: 0;
        }

        .inpost-listino-table thead th {
            padding: 1rem 0 1.1rem;
            border-bottom: 1px solid #e8ebf1;
            color: #98a1b3 !important;
            font-size: .78rem;
            letter-spacing: .04em;
        }

        .inpost-listino-table tbody td {
            padding: 1.15rem 0;
            border-bottom: 1px solid #eef1f5;
            vertical-align: middle;
        }

        .inpost-listino-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .inpost-listino-table tbody td:first-child,
        .inpost-listino-table thead th:first-child {
            padding-right: 1.5rem;
        }

        .inpost-listino-table tbody td:not(:first-child),
        .inpost-listino-table thead th:not(:first-child) {
            padding-left: 1.25rem;
        }

        .inpost-listino-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 92px;
            padding: .55rem .8rem;
            border-radius: 999px;
            font-weight: 700;
        }

        .inpost-listino-action {
            min-width: 92px;
            border-radius: 12px;
        }

        .inpost-listino-shell-title {
            margin: 0 0 .25rem;
            font-size: 1.6rem;
            font-weight: 800;
            color: #1f2430;
        }

        .inpost-listino-shell-text {
            margin: 0;
            color: #697181;
        }

        @media (max-width: 991px) {
            .inpost-listino-hero {
                grid-template-columns: 1fr;
            }

            .inpost-listino-shell-head {
                padding-left: 1.25rem;
                padding-right: 1.25rem;
            }

            .inpost-listino-shell-body {
                padding-left: 1.25rem;
                padding-right: 1.25rem;
            }

            .inpost-listino-table thead th,
            .inpost-listino-table tbody td {
                padding-top: .95rem;
                padding-bottom: .95rem;
            }

            .inpost-listino-table tbody td:first-child,
            .inpost-listino-table thead th:first-child,
            .inpost-listino-table tbody td:not(:first-child),
            .inpost-listino-table thead th:not(:first-child) {
                padding-left: 0;
                padding-right: 0;
            }
        }
    </style>
@endpush
@push('customScript')
    <script>
        $(function () {
            searchHandler();
        });
    </script>
@endpush
