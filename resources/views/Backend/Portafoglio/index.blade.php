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
        $isAdmin = \Illuminate\Support\Facades\Auth::user()->hasPermissionTo('admin');
        $agente = \Illuminate\Support\Facades\Auth::user()->agente ?? null;
        $wallets = [
            ['label' => 'Servizi', 'value' => (float)($agente->portafoglio_servizi ?? 0), 'class' => 'wallet-servizi'],
            ['label' => 'Spedizioni', 'value' => (float)($agente->portafoglio_spedizioni ?? 0), 'class' => 'wallet-spedizioni'],
            ['label' => 'Visure', 'value' => (float)($agente->portafoglio_visure ?? 0), 'class' => 'wallet-visure'],
        ];
        $totalePlafond = array_sum(array_column($wallets, 'value'));
    @endphp
    <div class="wallet-page">
        @if(session('message'))
            <div class="alert alert-success" role="alert">{{ session('message') }}</div>
        @endif
        @if($isAdmin && ($richiesteSpostamento ?? collect())->count())
            <div class="wallet-requests mb-8">
                <div class="wallet-requests-head">
                    <div>
                        <h4>Richieste spostamento in attesa</h4>
                        <p>Applica le richieste inviate dagli agenti dopo la verifica del saldo.</p>
                    </div>
                    <span class="badge badge-light-warning">{{($richiesteSpostamento ?? collect())->count()}}</span>
                </div>
                <div class="wallet-request-list">
                    @foreach($richiesteSpostamento as $richiesta)
                        <div class="wallet-request-row">
                            <div>
                                <strong>{{$richiesta->agente?->aliasAgente() ?? $richiesta->agente?->nominativo() ?? 'Agente'}}</strong>
                                <span>
                                    {!! importo($richiesta->importo, true) !!}
                                    da {{$richiesta->portafoglioDaTesto()}} a {{$richiesta->portafoglioATesto()}}
                                </span>
                                <em>{{$richiesta->descrizione}}</em>
                            </div>
                            <form method="POST" action="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'applicaRichiestaSpostamento'],$richiesta->id)}}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">Applica</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
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

        @if($isAdmin)
            <div class="wallet-transfer mb-8">
                <div class="wallet-transfer-head">
                    <div>
                        <h4>Sposta plafond agente</h4>
                        <p>Storna un importo da un portafoglio e lo accredita su un altro, con doppio movimento nello storico.</p>
                    </div>
                </div>
                <form method="POST" action="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'sposta'])}}">
                    @csrf
                    <div class="row g-6">
                        <div class="col-xl-6">
                            @include('Backend._inputs.inputSelect2',[
                                'campo'=>'agente_id',
                                'testo'=>'Agente',
                                'required'=>true,
                                'selected'=>\App\Models\User::selected(old('agente_id'))
                            ])
                        </div>
                        <div class="col-xl-6">
                            <div class="row mb-6" id="div_importo_spostamento">
                                <div class="col-lg-4 col-form-label text-lg-end">
                                    <label class="fw-bold fs-6 required" for="importo_spostamento">Importo</label>
                                </div>
                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                    <input type="text" id="importo_spostamento" name="importo"
                                           class="form-control form-control-solid autonumericImporto"
                                           value="{{old('importo')}}" required>
                                    <div class="fv-plugins-message-container invalid-feedback">
                                        @error('importo') {{$message}} @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="row mb-6" id="div_portafoglio_da">
                                <div class="col-lg-4 col-form-label text-lg-end">
                                    <label class="fw-bold fs-6 required" for="portafoglio_da">Da portafoglio</label>
                                </div>
                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                    <select id="portafoglio_da" name="portafoglio_da" class="form-select form-select-solid" required
                                            data-kt-select2="true" data-placeholder="Seleziona"
                                            data-minimum-results-for-search="Infinity">
                                        <option value="">Seleziona</option>
                                        @foreach(\App\Enums\TipiPortafoglioEnum::cases() as $item)
                                            <option value="{{$item->value}}" @selected(old('portafoglio_da') === $item->value)>{{$item->testo()}}</option>
                                        @endforeach
                                    </select>
                                    <div class="fv-plugins-message-container invalid-feedback">
                                        @error('portafoglio_da') {{$message}} @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="row mb-6" id="div_portafoglio_a">
                                <div class="col-lg-4 col-form-label text-lg-end">
                                    <label class="fw-bold fs-6 required" for="portafoglio_a">A portafoglio</label>
                                </div>
                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                    <select id="portafoglio_a" name="portafoglio_a" class="form-select form-select-solid" required
                                            data-kt-select2="true" data-placeholder="Seleziona"
                                            data-minimum-results-for-search="Infinity">
                                        <option value="">Seleziona</option>
                                        @foreach(\App\Enums\TipiPortafoglioEnum::cases() as $item)
                                            <option value="{{$item->value}}" @selected(old('portafoglio_a') === $item->value)>{{$item->testo()}}</option>
                                        @endforeach
                                    </select>
                                    <div class="fv-plugins-message-container invalid-feedback">
                                        @error('portafoglio_a') {{$message}} @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row mb-6" id="div_descrizione_spostamento">
                                <div class="col-lg-2 col-form-label text-lg-end">
                                    <label class="fw-bold fs-6 required" for="descrizione_spostamento">Motivo</label>
                                </div>
                                <div class="col-lg-10 fv-row fv-plugins-icon-container">
                                    <input type="text" id="descrizione_spostamento" name="descrizione"
                                           class="form-control form-control-solid"
                                           value="{{old('descrizione')}}" required maxlength="255">
                                    <div class="fv-plugins-message-container invalid-feedback">
                                        @error('descrizione') {{$message}} @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wallet-transfer-actions">
                        <button type="submit" class="btn btn-primary">Sposta plafond</button>
                    </div>
                </form>
            </div>

            <div class="wallet-transfer wallet-transfer-danger mb-8">
                <div class="wallet-transfer-head">
                    <div>
                        <h4>Storna plafond agente</h4>
                        <p>Registra un movimento negativo sul portafoglio selezionato, ad esempio uno storno di 30 euro viene salvato come -30 euro.</p>
                    </div>
                </div>
                <form method="POST" action="{{action([\App\Http\Controllers\Backend\PortafoglioController::class,'storna'])}}">
                    @csrf
                    <div class="row g-6">
                        <div class="col-xl-6">
                            @include('Backend._inputs.inputSelect2',[
                                'campo'=>'storno_agente_id',
                                'testo'=>'Agente',
                                'required'=>true,
                                'selected'=>\App\Models\User::selected(old('storno_agente_id'))
                            ])
                        </div>
                        <div class="col-xl-6">
                            <div class="row mb-6" id="div_storno_importo">
                                <div class="col-lg-4 col-form-label text-lg-end">
                                    <label class="fw-bold fs-6 required" for="storno_importo">Importo da stornare</label>
                                </div>
                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                    <input type="text" id="storno_importo" name="storno_importo"
                                           class="form-control form-control-solid autonumericImporto"
                                           value="{{old('storno_importo')}}" required>
                                    <div class="fv-plugins-message-container invalid-feedback">
                                        @error('storno_importo') {{$message}} @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="row mb-6" id="div_storno_portafoglio">
                                <div class="col-lg-4 col-form-label text-lg-end">
                                    <label class="fw-bold fs-6 required" for="storno_portafoglio">Portafoglio</label>
                                </div>
                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                    <select id="storno_portafoglio" name="storno_portafoglio" class="form-select form-select-solid" required
                                            data-kt-select2="true" data-placeholder="Seleziona"
                                            data-minimum-results-for-search="Infinity">
                                        <option value="">Seleziona</option>
                                        @foreach(\App\Enums\TipiPortafoglioEnum::cases() as $item)
                                            <option value="{{$item->value}}" @selected(old('storno_portafoglio') === $item->value)>{{$item->testo()}}</option>
                                        @endforeach
                                    </select>
                                    <div class="fv-plugins-message-container invalid-feedback">
                                        @error('storno_portafoglio') {{$message}} @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="row mb-6" id="div_storno_descrizione">
                                <div class="col-lg-4 col-form-label text-lg-end">
                                    <label class="fw-bold fs-6 required" for="storno_descrizione">Motivo</label>
                                </div>
                                <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                    <input type="text" id="storno_descrizione" name="storno_descrizione"
                                           class="form-control form-control-solid"
                                           value="{{old('storno_descrizione')}}" required maxlength="255">
                                    <div class="fv-plugins-message-container invalid-feedback">
                                        @error('storno_descrizione') {{$message}} @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wallet-transfer-actions">
                        <button type="submit" class="btn btn-danger">Storna plafond</button>
                    </div>
                </form>
            </div>
        @endif

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
        .wallet-requests,
        .wallet-transfer,
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

        .wallet-requests {
            overflow: hidden;
        }

        .wallet-requests-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.35rem 1.75rem;
            border-bottom: 1px solid var(--wallet-border);
            background: #fffaf0;
        }

        .wallet-requests-head h4 {
            margin: 0 0 .35rem;
            color: var(--wallet-text);
            font-weight: 800;
        }

        .wallet-requests-head p {
            margin: 0;
            color: var(--wallet-muted);
        }

        .wallet-request-list {
            display: grid;
            gap: .75rem;
            padding: 1rem 1.25rem 1.25rem;
        }

        .wallet-request-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            border: 1px solid #f3e7c5;
            border-radius: 8px;
            background: #fff;
        }

        .wallet-request-row strong,
        .wallet-request-row span,
        .wallet-request-row em {
            display: block;
        }

        .wallet-request-row strong {
            color: var(--wallet-text);
            font-weight: 800;
        }

        .wallet-request-row span {
            color: #3b4663;
            margin-top: .15rem;
        }

        .wallet-request-row em {
            color: var(--wallet-muted);
            font-style: normal;
            margin-top: .2rem;
        }

        .wallet-transfer {
            padding: 1.5rem 1.75rem;
        }

        .wallet-transfer-danger {
            border-color: #f3d6d6;
        }

        .wallet-transfer-danger .wallet-transfer-head {
            border-bottom-color: #f3d6d6;
        }

        .wallet-transfer-head {
            padding-bottom: 1.25rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--wallet-border);
        }

        .wallet-transfer-head h4 {
            margin: 0 0 .35rem;
            color: var(--wallet-text);
            font-weight: 800;
        }

        .wallet-transfer-head p {
            margin: 0;
            color: var(--wallet-muted);
        }

        .wallet-transfer .row.mb-6 {
            margin-bottom: 0 !important;
        }

        .wallet-transfer-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 1rem;
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

            .wallet-transfer {
                padding: 1.25rem;
            }

            .wallet-transfer-actions .btn {
                width: 100%;
            }

            .wallet-request-row {
                grid-template-columns: 1fr;
            }

            .wallet-request-row .btn {
                width: 100%;
            }
        }
    </style>
@endpush
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script>
        var indexUrl = '{{action([$controller,'index'])}}';

        $(function () {
            searchHandler();
            select2UniversaleBackend('agente_id', 'un agente', 1);
            select2UniversaleBackend('storno_agente_id', 'un agente', 1, 'agente_id');
            autonumericImporto('autonumericImporto');
        });
    </script>
@endpush
