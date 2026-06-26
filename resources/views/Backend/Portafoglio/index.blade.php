@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        @isset($testoNuovo)
            <a class="btn btn-sm btn-primary fw-bold"
               href="{{action([$controller,'create'])}}">
                <span class="d-md-none">+</span>
                <span class="d-none d-md-block">Ricarica plafond</span>
            </a>
        @endisset
    </div>
@endsection
@section('content')
    @php
        $agente = \Illuminate\Support\Facades\Auth::user()->agente ?? null;
        $wallets = [
            ['label' => 'Servizi', 'value' => (float)($agente->portafoglio_servizi ?? 0), 'class' => 'wallet-servizi'],
            ['label' => 'Spedizioni', 'value' => (float)($agente->portafoglio_spedizioni ?? 0), 'class' => 'wallet-spedizioni'],
            ['label' => 'Visure', 'value' => (float)($agente->portafoglio_visure ?? 0), 'class' => 'wallet-visure'],
        ];
        $totalePlafond = array_sum(array_column($wallets, 'value'));
    @endphp
    <div class="wallet-page">
        <div class="wallet-hero mb-8">
            <div>
                <div class="text-uppercase fw-semibold text-primary fs-8 mb-2">Portafoglio operativo</div>
                <h3 class="mb-1">Movimenti plafond</h3>
                <div class="text-muted fs-6">Controlla saldi, ricariche e storico degli accrediti.</div>
            </div>
            <div class="wallet-total">
                <span>Plafond totale</span>
                <strong>{{importo($totalePlafond, true)}}</strong>
            </div>
        </div>

        <div class="row g-6 mb-8">
            @foreach($wallets as $wallet)
                <div class="col-md-4">
                    <div class="wallet-balance {{$wallet['class']}}">
                        <span>{{$wallet['label']}}</span>
                        <strong>{{importo($wallet['value'], true)}}</strong>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="wallet-panel">
            <div class="wallet-panel-head">
                <div>
                    <h4>Storico movimenti</h4>
                    <p>Ogni ricarica o consumo aggiorna il saldo del relativo portafoglio.</p>
                </div>
                <div class="wallet-actions">
                    @isset($testoCerca)
                        <div class="wallet-search" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{$testoCerca}}">
                            <span class="svg-icon svg-icon-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="currentColor" opacity=".35" d="m15.6 14.2l4.1 4.1a1 1 0 0 1-1.4 1.4l-4.1-4.1z"/>
                                    <path fill="currentColor" d="M11 17a6 6 0 1 1 0-12a6 6 0 0 1 0 12m0-2a4 4 0 1 0 0-8a4 4 0 0 0 0 8"/>
                                </svg>
                            </span>
                            <input type="text" id="filter_search" class="form-control form-control-sm form-control-solid"
                                   placeholder="Cerca movimento">
                        </div>
                    @endisset
                    <a class="btn btn-primary btn-sm" href="{{action([$controller,'create'])}}">Ricarica</a>
                </div>
            </div>
            <div id="tabella">
                @include('Backend.Portafoglio.tabella')
            </div>
        </div>
    </div>
@endsection
@push('customCss')
    <style>
        .wallet-page {
            --wallet-border: #e7eef7;
            --wallet-text: #162033;
            --wallet-muted: #7e8aa6;
            --wallet-soft: #f6f9fc;
        }

        .wallet-hero,
        .wallet-panel {
            border: 1px solid var(--wallet-border);
            border-radius: 8px;
            background: #fff;
        }

        .wallet-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.5rem 1.75rem;
        }

        .wallet-total {
            min-width: 190px;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            background: #f7fbff;
            text-align: right;
            border: 1px solid #dceefa;
        }

        .wallet-total span,
        .wallet-balance span {
            display: block;
            color: var(--wallet-muted);
            font-size: .85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .wallet-total strong,
        .wallet-balance strong {
            display: block;
            color: var(--wallet-text);
            font-size: 1.35rem;
            font-weight: 800;
            margin-top: .25rem;
        }

        .wallet-balance {
            position: relative;
            min-height: 104px;
            padding: 1.25rem;
            border: 1px solid var(--wallet-border);
            border-radius: 8px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            overflow: hidden;
        }

        .wallet-balance:before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: #009ef7;
        }

        .wallet-spedizioni:before {
            background: #50cd89;
        }

        .wallet-visure:before {
            background: #7239ea;
        }

        .wallet-panel {
            overflow: hidden;
        }

        .wallet-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid var(--wallet-border);
        }

        .wallet-panel-head h4 {
            margin: 0 0 .35rem;
            color: var(--wallet-text);
            font-weight: 800;
        }

        .wallet-panel-head p {
            margin: 0;
            color: var(--wallet-muted);
        }

        .wallet-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .wallet-search {
            position: relative;
            width: 240px;
        }

        .wallet-search .svg-icon {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #99a6bd;
            z-index: 1;
        }

        .wallet-search input {
            min-height: 40px;
            border-radius: 6px;
            padding-left: 2.6rem;
            background: var(--wallet-soft);
            border-color: transparent;
        }

        .wallet-table {
            margin: 0;
        }

        .wallet-panel #tabella {
            padding: 0 .75rem;
        }

        .wallet-table thead th {
            padding: 1rem;
            color: #7e8aa6;
            background: #fbfcfe;
            border-bottom: 1px solid var(--wallet-border);
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: 0;
        }

        .wallet-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            color: #3b4663;
            border-color: #eef3f8;
        }

        .wallet-date {
            color: #6f7b95;
            font-weight: 600;
            white-space: nowrap;
        }

        .wallet-amount {
            color: #162033;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .wallet-description {
            color: #3b4663;
            line-height: 1.45;
        }

        .wallet-empty {
            padding: 3rem 1.75rem;
            text-align: center;
            color: var(--wallet-muted);
        }

        .wallet-pagination {
            padding: 1.25rem 1.75rem 1.5rem;
            border-top: 1px solid var(--wallet-border);
        }

        @media (max-width: 767.98px) {
            .wallet-hero,
            .wallet-panel-head {
                align-items: stretch;
                flex-direction: column;
            }

            .wallet-total {
                min-width: 0;
                text-align: left;
            }

            .wallet-actions,
            .wallet-search {
                width: 100%;
            }

            .wallet-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush
@push('customScript')
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';

        $(function () {
            searchHandler();
        });
    </script>
@endpush
