@extends('Backend._layout._main')
@section('toolbar')
    @if($record->id)
        <div class="me-0">
            <a href="#" class="btn btn-sm btn-flex bg-body btn-color-gray-700 btn-active-color-primary fw-bold"
               data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"
               data-kt-menu-flip="top-end">Azioni
                <!--begin::Svg Icon | path: icons/duotone/Navigation/Angle-down.svg-->
                <span class="svg-icon svg-icon-5 m-0">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px"
                     height="24px" viewBox="0 0 24 24" version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <polygon points="0 0 24 0 24 24 0 24"/>
                        <path
                                d="M6.70710678,15.7071068 C6.31658249,16.0976311 5.68341751,16.0976311 5.29289322,15.7071068 C4.90236893,15.3165825 4.90236893,14.6834175 5.29289322,14.2928932 L11.2928932,8.29289322 C11.6714722,7.91431428 12.2810586,7.90106866 12.6757246,8.26284586 L18.6757246,13.7628459 C19.0828436,14.1360383 19.1103465,14.7686056 18.7371541,15.1757246 C18.3639617,15.5828436 17.7313944,15.6103465 17.3242754,15.2371541 L12.0300757,10.3841378 L6.70710678,15.7071068 Z"
                                fill="#000000" fill-rule="nonzero"
                                transform="translate(12.000003, 11.999999) rotate(-180.000000) translate(-12.000003, -11.999999)"/>
                    </g>
                </svg>
            </span>
                <!--end::Svg Icon-->
            </a>
            <!--begin::Menu-->
            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-bold fs-7 w-200px py-4"
                 data-kt-menu="true">
                <div class="menu-item px-3">
                          <a href="{{action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class,'create'],['duplica'=>$record->id])}}"
                       class="menu-link px-3">Duplica</a>
                </div>
                @if(Auth::user()->hasAnyPermission(['admin','supervisore']))
                    <div class="menu-item px-3">
                        <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'create'],['servizio_id'=>$record->id,'servizio_type'=>'contratto-energia'])}}"
                           data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                           class="menu-link px-3">Nuovo ticket</a>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection

@section('content')
    @php
        $vecchio = $record->id;
        $isBackoffice = Auth::user()->hasAnyPermission(['admin','operatore','supervisore']);
        $gestoreEnergia = \App\Models\GestoreContrattoEnergia::find($record->gestore_id);
        $categoriaCorrente = old('categoria_pratica', $categoriaPratica ?? 'consumer');
    @endphp
    <div class="energy-form-page">
        <div class="energy-form-hero">
            <div>
                <div class="energy-eyebrow">Contratti energia</div>
                <h1>{{ $vecchio ? 'Modifica contratto' : 'Crea contratto energia' }}</h1>
                <p>{{ $gestoreEnergia?->nome }}: compila dati pratica, intestatario, fornitura e allegati in un flusso guidato.</p>
            </div>
            <div class="energy-form-summary">
                <span>Gestore selezionato</span>
                <strong>{{ $gestoreEnergia?->nome }}</strong>
                <div class="energy-summary-prices">
                    <div>
                        <small>Categoria</small>
                        <b>{{ ucfirst($categoriaCorrente) }}</b>
                    </div>
                    <div>
                        <small>Form</small>
                        <b>{{ $tipoProdotto ? 'Guidato' : 'Base' }}</b>
                    </div>
                </div>
            </div>
        </div>

        @include('Backend._components.alertErrori')

        <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}" class="energy-form-shell">
            @csrf
            @method($record->id?'PATCH':'POST')
            @php
                $uid = old('uid', $record->uid);
            @endphp
            <input type="hidden" name="uid" id="uid" value="{{$uid}}">
            <input type="hidden" id="gestore_id" name="gestore_id" value="{{old('gestore_id',$record->gestore_id)}}">
            <input type="hidden" id="tipo_prodotto" name="tipo_prodotto" value="{{old('tipo_prodotto',$tipoProdotto)}}">
            <input type="hidden" name="categoria_pratica" id="categoria_pratica" value="{{ $categoriaCorrente }}">

            <div class="energy-form-grid">
                <main class="energy-form-main">
                    <section class="energy-section">
                        <div class="energy-section-head">
                            <span class="energy-step">01</span>
                            <div>
                                <h2>Dati pratica</h2>
                                <p>Gestore, assegnazione agente e data di apertura del contratto.</p>
                            </div>
                        </div>
                        <div class="energy-section-body">
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_contratto_id','testo'=>'Tipo contratto','valore'=>$gestoreEnergia?->nome])
                                </div>
                                <div class="col-md-6"></div>
                            </div>

                            @if($isBackoffice)
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                                    </div>
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputTextDataMask',['campo'=>'data','testo'=>'Data','required'=>true])
                                    </div>
                                </div>
                            @else
                                <input type="hidden" id="agente_id" name="agente_id" value="{{old('agente_id',$record->agente_id)}}">
                                <input type="hidden" name="data" value="{{old('data',$record->data->format('d/m/Y'))}}">
                            @endif
                        </div>
                    </section>

                    @if($recordProdotto)
                        <section class="energy-section">
                            <div class="energy-section-head">
                                <span class="energy-step">02</span>
                                <div>
                                    <h2>Categoria pratica</h2>
                                    <p>Seleziona consumer o business quando il gestore ha entrambe le configurazioni disponibili.</p>
                                </div>
                            </div>
                            <div class="energy-section-body">
                                @php
                                    $categoriaDaProdotto = null;
                                    if (str_contains(strtolower((string)$tipoProdotto), 'business')) {
                                        $categoriaDaProdotto = 'business';
                                    } elseif (str_contains(strtolower((string)$tipoProdotto), 'consumer')) {
                                        $categoriaDaProdotto = 'consumer';
                                    }
                                    $switchConsumerUrl = $categoriaSwitchUrls['consumer'] ?? null;
                                    $switchBusinessUrl = $categoriaSwitchUrls['business'] ?? null;
                                    $switchAbilitato = !empty($switchConsumerUrl) && !empty($switchBusinessUrl);
                                @endphp

                                <div class="energy-category-picker" id="categoria-pratica-tabs">
                                    <button type="button" class="btn btn-sm btn-light-primary js-categoria-tab"
                                            data-categoria="consumer"
                                            data-switch-url="{{ $switchConsumerUrl }}"
                                            @if(!$switchAbilitato) disabled @endif>
                                        <i class="bi bi-person-circle me-1"></i>Consumer
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light-primary js-categoria-tab"
                                            data-categoria="business"
                                            data-switch-url="{{ $switchBusinessUrl }}"
                                            @if(!$switchAbilitato) disabled @endif>
                                        <i class="bi bi-building me-1"></i>Business
                                    </button>
                                </div>

                                @if(!$switchAbilitato && !empty($categoriaDaProdotto))
                                    <div class="energy-inline-hint mt-3">
                                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                                        <span>Categoria fissata dal prodotto selezionato: <strong>{{ ucfirst($categoriaDaProdotto) }}</strong>.</span>
                                    </div>
                                @elseif($switchAbilitato)
                                    <div class="energy-inline-hint mt-3">
                                        <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                                        <span>Cambiando categoria apri direttamente il form prodotto corrispondente.</span>
                                    </div>
                                @endif
                                @error('categoria_pratica')
                                    <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </section>

                        <section class="energy-section">
                            <div class="energy-section-head">
                                <span class="energy-step">03</span>
                                <div>
                                    <h2>Dettagli contratto</h2>
                                    <p>Campi specifici del gestore, dati cliente, indirizzo fornitura e codici tecnici.</p>
                                </div>
                            </div>
                            <div class="energy-section-body energy-dynamic-fields">
                                <div class="energy-inline-hint">
                                    <i class="bi bi-magic" aria-hidden="true"></i>
                                    <span>Inserisci il codice fiscale: se il cliente esiste gia, Gestiio completa i dati disponibili e verifica eventuali blocchi.</span>
                                </div>
                                @include("Backend.ContrattoEnergia.Prodotti.{$tipoProdotto}Edit",['record'=>$recordProdotto,'codiceFiscale'=>$record->codice_fiscale,'email'=>$record->email,'telefono'=>$record->telefono,'denominazione'=>$record->denominazione])
                            </div>
                        </section>
                    @endif

                    <section class="energy-section">
                        <div class="energy-section-head">
                            <span class="energy-step">{{ $recordProdotto ? '04' : '02' }}</span>
                            <div>
                                <h2>Note e allegati</h2>
                                <p>Annotazioni interne e documenti collegati al contratto energia.</p>
                            </div>
                        </div>
                        <div class="energy-section-body">
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputTextAreaCol',['campo'=>'note','testo'=>'Note interne','col'=>2])
                                </div>
                                <div class="col-md-6">
                                    <div class="dropzone energy-dropzone gestiio-dropzone" id="kt_dropzonejs_example_1">
                                        <div class="dz-message needsclick">
                                            <span class="energy-upload-icon gestiio-dropzone-icon">
                                                <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                                            </span>
                                            <div>
                                                <h3>Trascina i file qui o clicca per selezionarli</h3>
                                                <span>Documenti identita, bollette, deleghe e allegati relativi al contratto.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <aside class="energy-form-aside">
                    <div class="energy-sticky-card">
                        <div class="energy-aside-title">Riepilogo</div>
                        <div class="energy-aside-row">
                            <span>Stato</span>
                            <strong>{{ $vecchio ? 'Modifica' : 'Nuovo contratto' }}</strong>
                        </div>
                        <div class="energy-aside-row">
                            <span>Gestore</span>
                            <strong>{{ $gestoreEnergia?->nome }}</strong>
                        </div>
                        <div class="energy-aside-row">
                            <span>Categoria</span>
                            <strong>{{ ucfirst($categoriaCorrente) }}</strong>
                        </div>
                        <div class="energy-aside-note">
                            Salva in bozza se mancano allegati o dati tecnici. Conferma quando la pratica e pronta per la lavorazione.
                        </div>

                        @if($creaContratto)
                            <button class="btn btn-primary w-100" type="submit" id="submit">
                                {{ $vecchio ? 'Salva modifiche' : 'Crea '.\App\Models\ContrattoEnergia::NOME_SINGOLARE }}
                            </button>
                        @endif
                        @if(!$vecchio || $record->esito_id=='bozza')
                            <button class="btn btn-light-warning w-100 mt-2" type="submit" id="submit-bozza" name="bozza" value="bozza">
                                {{ $vecchio ? 'Salva bozza' : 'Crea bozza' }}
                            </button>
                        @endif

                        @if($vecchio)
                            <div class="mt-3">
                                @if($eliminabile===true)
                                    <a class="btn btn-light-danger w-100" id="elimina" href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                                @elseif(is_string($eliminabile))
                                    <span data-bs-toggle="tooltip" title="{{$eliminabile}}">
                                        <a class="btn btn-light-danger disabled w-100" href="javascript:void(0)">Elimina</a>
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </form>
    </div>
@endsection

@push('customCss')
    <style>
        .energy-form-page {
            --energy-bg: #f8fafc;
            --energy-surface: #ffffff;
            --energy-text: #020617;
            --energy-muted: #64748b;
            --energy-border: #e2e8f0;
            --energy-primary: #0ea5e9;
            --energy-primary-dark: #0369a1;
            color: var(--energy-text);
        }

        .energy-form-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 380px);
            gap: 24px;
            align-items: stretch;
            padding: 28px;
            margin-bottom: 18px;
            border: 1px solid var(--energy-border);
            border-radius: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
            box-shadow: 0 12px 36px rgba(15, 23, 42, .05);
        }

        .energy-eyebrow {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            margin-bottom: 12px;
            border-radius: 999px;
            background: #e0f2fe;
            color: var(--energy-primary-dark);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .energy-form-hero h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .energy-form-hero p {
            max-width: 760px;
            margin: 10px 0 0;
            color: var(--energy-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .energy-form-summary,
        .energy-sticky-card {
            border: 1px solid #cfe8ff;
            border-radius: 8px;
            background: rgba(255,255,255,.9);
        }

        .energy-form-summary {
            padding: 20px;
        }

        .energy-form-summary span,
        .energy-form-summary small,
        .energy-aside-row span {
            color: var(--energy-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .energy-form-summary strong {
            display: block;
            margin-top: 6px;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.35;
        }

        .energy-summary-prices {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 16px;
        }

        .energy-summary-prices div {
            padding: 12px;
            border-radius: 8px;
            background: var(--energy-bg);
        }

        .energy-summary-prices b {
            display: block;
            margin-top: 4px;
            font-size: 16px;
        }

        .energy-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 20px;
            align-items: start;
        }

        .energy-section {
            margin-bottom: 16px;
            border: 1px solid var(--energy-border);
            border-radius: 8px;
            background: var(--energy-surface);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .energy-section-head {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 18px 20px;
            border-bottom: 1px solid var(--energy-border);
            background: #fbfdff;
        }

        .energy-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 8px;
            background: #e0f2fe;
            color: var(--energy-primary-dark);
            font-size: 12px;
            font-weight: 900;
        }

        .energy-section-head h2 {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 800;
        }

        .energy-section-head p {
            margin: 0;
            color: var(--energy-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .energy-section-body {
            padding: 22px 20px 8px;
        }

        .energy-section-body .row.mb-6 {
            margin-bottom: 16px !important;
        }

        .energy-section-body .col-form-label {
            padding-top: 11px;
        }

        .energy-section-body .form-control,
        .energy-section-body .form-select,
        .energy-section-body .select2-container--bootstrap5 .select2-selection {
            min-height: 46px;
            border-radius: 8px;
        }

        .energy-inline-hint {
            display: flex;
            gap: 10px;
            align-items: center;
            min-height: 44px;
            padding: 10px 12px;
            margin-bottom: 18px;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 13px;
            font-weight: 600;
        }

        .energy-inline-hint i {
            color: var(--energy-primary-dark);
        }

        .energy-category-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .energy-category-picker .btn {
            min-width: 130px;
        }

        .energy-dynamic-fields > .row {
            max-width: 100%;
        }

        .energy-dynamic-fields > .row > [class*="offset-md"] {
            margin-left: 0 !important;
        }

        .energy-dynamic-fields h4,
        .energy-dynamic-fields > strong {
            display: block;
            margin: 18px 0 14px;
            color: var(--energy-text);
            font-size: 15px;
            font-weight: 900;
        }

        .energy-dynamic-fields h4:first-child,
        .energy-dynamic-fields > strong:first-child {
            margin-top: 0;
        }

        .energy-dynamic-fields ul {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            padding: 0;
            margin: 12px 0 18px;
            list-style: none;
        }

        .energy-dynamic-fields li {
            min-height: 44px;
            padding: 11px 12px 11px 34px;
            border: 1px solid var(--energy-border);
            border-radius: 8px;
            background: var(--energy-bg);
            color: #334155;
            font-size: 13px;
            line-height: 1.45;
            position: relative;
        }

        .energy-dynamic-fields li::before {
            content: "";
            position: absolute;
            left: 12px;
            top: 15px;
            width: 10px;
            height: 10px;
            border: 2px solid var(--energy-primary);
            border-radius: 3px;
            background: #fff;
        }

        .energy-dropzone {
            min-height: 170px;
            border: 1px dashed #93c5fd;
            border-radius: 8px;
            background: #f8fbff;
        }

        .energy-dropzone .dz-message {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            min-height: 140px;
            margin: 0;
        }

        .energy-upload-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            border-radius: 8px;
            background: #e0f2fe;
            color: var(--energy-primary-dark);
            font-size: 25px;
        }

        .energy-dropzone h3 {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 800;
        }

        .energy-dropzone span {
            color: var(--energy-muted);
            font-size: 13px;
        }

        .energy-sticky-card {
            position: sticky;
            top: 90px;
            padding: 18px;
            box-shadow: 0 12px 36px rgba(15, 23, 42, .05);
        }

        .energy-aside-title {
            margin-bottom: 14px;
            font-size: 16px;
            font-weight: 900;
        }

        .energy-aside-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-top: 1px solid var(--energy-border);
        }

        .energy-aside-row strong {
            max-width: 170px;
            text-align: right;
            font-size: 14px;
            font-weight: 900;
        }

        .energy-aside-note {
            padding: 14px;
            margin: 14px 0;
            border-radius: 8px;
            background: var(--energy-bg);
            color: #475569;
            font-size: 12px;
            line-height: 1.65;
        }

        @media (max-width: 1199.98px) {
            .energy-form-grid {
                grid-template-columns: 1fr;
            }

            .energy-sticky-card {
                position: static;
            }
        }

        @media (max-width: 991.98px) {
            .energy-form-hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .energy-form-hero,
            .energy-section-head,
            .energy-section-body {
                padding-left: 16px;
                padding-right: 16px;
            }

            .energy-form-hero h1 {
                font-size: 24px;
            }

            .energy-dynamic-fields ul,
            .energy-summary-prices {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@include('Backend._components.dropzoneUx')

@push('customScript')
    @php
        $contrattoEnergiaConfig = [
            'cfRiskCheckUrl' => action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'verificaCodiceFiscaleRischio']),
            'initialCfRiskBlock' => session('cf_risk_block'),
            'recordId' => $record->id ? (int) $record->id : null,
            'switchCategoriaUrl' => $record->id ? action([\App\Http\Controllers\Backend\ContrattoEnergiaController::class, 'switchCategoria'], [$record->id]) : null,
            'defaultCategoria' => old('categoria_pratica', $categoriaPratica ?? 'consumer'),
            'csrfToken' => csrf_token(),
            'uploadAllegatoUrl' => action([$controller, 'uploadAllegato']),
            'contrattoId' => $record->id ?? -1,
            'allegatiEsistenti' => \App\Models\AllegatoContrattoEnergia::perBlade($uid, $record->id),
            'deleteAllegatoUrl' => action([$controller, 'deleteAllegato']),
            'clienteCfUrl' => action([\App\Http\Controllers\Backend\AjaxController::class, 'post'], 'cliente-cf'),
        ];
    @endphp
    <script id="contratto-energia-edit-config" type="application/json">{!! json_encode($contrattoEnergiaConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>


        $(function () {
            var configElement = document.getElementById('contratto-energia-edit-config');
            var config = {};
            try {
                config = JSON.parse((configElement && configElement.textContent) ? configElement.textContent : '{}');
            } catch (error) {
                config = {};
            }

            var csrfToken = config.csrfToken || $('meta[name="csrf-token"]').attr('content') || $('meta[name="_token"]').attr('content') || '';
            var cfRiskCheckUrl = config.cfRiskCheckUrl || '';
            var initialCfRiskBlock = config.initialCfRiskBlock || null;
            var cfRiskLocked = false;

            function setContractButtonsLocked(locked) {
                $('#submit, #submit-bozza').prop('disabled', locked);
            }

            function showCfRiskModal(payload) {
                var labels = (payload && payload.labels && payload.labels.length) ? payload.labels : ['Morosita / Blacklist / Credit check'];
                var cf = (payload && payload.codice_fiscale) ? payload.codice_fiscale : ($('#codice_fiscale').val() || '');
                var items = labels.map(function (label) {
                    return '<li class="mb-1"><span class="badge badge-danger me-2">●</span>' + label + '</li>';
                }).join('');

                Swal.fire({
                    icon: 'error',
                    title: 'Semaforo rosso',
                    html: '<div class="text-start">Codice fiscale <b>' + cf + '</b> bloccato.<br><ul class="mt-3 ps-4">' + items + '</ul><div class="mt-2">Impossibile caricare il contratto.</div></div>',
                    confirmButtonText: 'OK'
                });
            }

            function applyCfRiskState(payload, showModal) {
                cfRiskLocked = !!(payload && payload.blocked);
                setContractButtonsLocked(cfRiskLocked);
                if (cfRiskLocked && showModal) {
                    showCfRiskModal(payload);
                }
                return payload;
            }

            function requestCfRisk(showModal) {
                var cf = ($('#codice_fiscale').val() || '').trim();
                if (cf === '') {
                    return $.Deferred().resolve(applyCfRiskState({blocked: false}, false)).promise();
                }

                return $.ajax({
                    url: cfRiskCheckUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: csrfToken,
                        codice_fiscale: cf,
                        gestore_id: $('#gestore_id').val() || null
                    }
                }).done(function (resp) {
                    applyCfRiskState(resp, showModal);
                });
            }

            if (initialCfRiskBlock && initialCfRiskBlock.blocked) {
                applyCfRiskState(initialCfRiskBlock, true);
            }

            var recordId = typeof config.recordId === 'number' ? config.recordId : null;
            var switchCategoriaUrl = config.switchCategoriaUrl || null;

            eliminaHandler('Questa voce verrà eliminata definitivamente');
            if ($('#agente_id').is("select")) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }

            function toggleCategoriaPratica(categoria) {
                var isBusiness = categoria === 'business';

                $('.js-categoria-tab').removeClass('btn-primary').addClass('btn-light-primary');
                $('.js-categoria-tab[data-categoria="' + categoria + '"]').removeClass('btn-light-primary').addClass('btn-primary');
                $('#categoria_pratica').val(categoria);

                $('#denominazione, #partita_iva').prop('required', isBusiness);
                $('#nome, #cognome').prop('required', !isBusiness);
            }

            toggleCategoriaPratica($('#categoria_pratica').val() || (config.defaultCategoria || 'consumer'));

            $('.js-categoria-tab:not([disabled])').on('click', function () {
                var categoria = $(this).data('categoria');
                var switchUrl = $(this).data('switch-url');
                var categoriaCorrente = $('#categoria_pratica').val();

                if (categoria === categoriaCorrente) {
                    return;
                }

                toggleCategoriaPratica(categoria);

                if (recordId && switchCategoriaUrl) {
                    Swal.fire({
                        title: 'Confermi lo switch categoria?',
                        text: 'I campi specifici del prodotto corrente verranno reimpostati.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sì, continua',
                        cancelButtonText: 'Annulla'
                    }).then(function (result) {
                        if (!result.isConfirmed) {
                            toggleCategoriaPratica(categoriaCorrente);
                            return;
                        }

                        $.ajax({
                            url: switchCategoriaUrl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                _token: csrfToken,
                                categoria_pratica: categoria
                            },
                            success: function (resp) {
                                if (resp && resp.redirect) {
                                    window.location.href = resp.redirect;
                                    return;
                                }
                                window.location.reload();
                            },
                            error: function (xhr) {
                                var msg = 'Impossibile eseguire lo switch categoria.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }
                                toggleCategoriaPratica(categoriaCorrente);
                                Swal.fire('Errore', msg, 'warning');
                            }
                        });
                    });
                    return;
                }

                if (switchUrl && window.location.href !== switchUrl) {
                    var redirectUrl = new URL(switchUrl, window.location.origin);
                    var campiPrefill = [
                        'agente_id',
                        'codice_fiscale',
                        'email',
                        'telefono',
                        'cellulare',
                        'denominazione',
                        'nome',
                        'cognome',
                        'partita_iva',
                        'forma_giuridica',
                        'indirizzo',
                        'citta',
                        'cap',
                        'scala',
                        'interno',
                        'indirizzo_sede',
                        'comune_sede',
                        'cap_sede',
                        'nr_sede',
                        'nome_cognome_referente',
                        'codice_fiscale_referente',
                        'telefono_referente',
                        'codice_destinatario',
                        'indirizzo_pec',
                        'pod',
                        'pdr',
                        'iban'
                    ];

                    campiPrefill.forEach(function (campo) {
                        var $field = $('[name="' + campo + '"]');
                        if (!$field.length) {
                            return;
                        }
                        var valore = $field.val();
                        if (Array.isArray(valore)) {
                            valore = valore.length ? valore[0] : '';
                        }
                        if (valore === null || valore === '') {
                            return;
                        }
                        redirectUrl.searchParams.set(campo, valore);
                    });

                    window.location.href = redirectUrl.toString();
                }
            });

            initGestiioDropzone("#kt_dropzonejs_example_1", {
                uploadUrl: config.uploadAllegatoUrl,
                deleteUrl: config.deleteAllegatoUrl,
                csrfToken: csrfToken,
                existingFiles: config.allegatiEsistenti || [],
                sendingData: {
                    uid: function () { return $('#uid').val(); },
                    contratto_energia_id: config.contrattoId
                }
            });

            $('#codice_fiscale').on('blur change', function () {
                requestCfRisk(true);
            });

            var bypassCfRiskSubmit = false;
            $('form').on('submit.cfRisk', function (event) {
                var form = this;

                if (bypassCfRiskSubmit) {
                    return;
                }

                if (cfRiskLocked) {
                    event.preventDefault();
                    showCfRiskModal(initialCfRiskBlock || {codice_fiscale: $('#codice_fiscale').val(), labels: []});
                    return;
                }

                event.preventDefault();
                requestCfRisk(false).done(function (resp) {
                    if (resp && resp.blocked) {
                        showCfRiskModal(resp);
                        return;
                    }
                    bypassCfRiskSubmit = true;
                    form.submit();
                }).fail(function () {
                    // Se il check CF non è raggiungibile, non bloccare il salvataggio del contratto.
                    bypassCfRiskSubmit = true;
                    form.submit();
                });
            });


            $('#codice_fiscale').blur(function (e) {
                if (cfRiskLocked) {
                    return;
                }

                var codice_fiscale = $(this).val();
                if (codice_fiscale === "") {
                    return;
                }
                if ($('#cognome').val() !== '') {
                    return;
                }
                var url = config.clienteCfUrl;
                $.ajax({
                    url: url,
                    type: 'post',
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        codice_fiscale: codice_fiscale
                    },
                    success: function (resp) {
                        if (resp.success) {
                            if (resp.cliente) {
                                $('#nome').val(resp.cliente.nome);
                                $('#cognome').val(resp.cliente.cognome);
                                $('#ragione_sociale').val(resp.cliente.ragione_sociale);
                                $('#email').val(resp.cliente.email);
                                $('#telefono').val(resp.cliente.telefono);
                                $('#indirizzo').val(resp.cliente.indirizzo);
                                $('#cap').val(resp.cliente.cap);
                                $('#partita_iva').val(resp.cliente.partita_iva);
                                if (resp.cliente.comune) {
                                    var newState = new Option(resp.cliente.comune.comune + '(' + resp.cliente.comune.targa + ')', resp.cliente.comune.id, true, true);
                                    $("#citta").append(newState).trigger('change');

                                }

                                Swal.fire(
                                    "Cliente trovato",
                                    'I dati cliente sono stati inseriti',
                                    "info"
                                )

                            } else {
                                return;
                            }


                        } else {
                            Swal.fire(
                                "Errore",
                                resp.message,
                                "warning"
                            )

                        }

                    }
                });

            });

            $('#citta').select2({
                placeholder: 'Seleziona una citta',
                minimumInputLength: 1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: "/backend/select2?citta",
                    dataType: 'json',
                    data: function (term, page) {
                        return {
                            term: term.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    }
                }
            }).on('select2:select', function (e) {
                // Access to full data
                $("#cap").val(e.params.data.cap);
            });

            function setValidationState($field, isValid) {
                $field.removeClass('is-valid is-invalid');
                if (isValid === null) {
                    return;
                }
                $field.addClass(isValid ? 'is-valid' : 'is-invalid');
            }

            function normalizePod(raw) {
                return (raw || '').toString().trim().replace(/\s+/g, '').toUpperCase();
            }

            function normalizePdr(raw) {
                return (raw || '').toString().trim().replace(/\s+/g, '');
            }

            function isValidPod(value) {
                return /^IT\d{3}E[A-Z0-9]{8}$/.test(value);
            }

            function isValidPdr(value) {
                return /^\d{14}$/.test(value);
            }

            var $pod = $('#pod');
            if ($pod.length) {
                var validatePod = function () {
                    var normalized = normalizePod($pod.val());
                    if (normalized === '') {
                        setValidationState($pod, null);
                        return;
                    }
                    setValidationState($pod, isValidPod(normalized));
                };

                $pod.on('input', validatePod);
                $pod.on('blur', function () {
                    $pod.val(normalizePod($pod.val()));
                    validatePod();
                });
                validatePod();
            }

            var $pdr = $('#pdr');
            if ($pdr.length) {
                var validatePdr = function () {
                    var normalized = normalizePdr($pdr.val());
                    if (normalized === '') {
                        setValidationState($pdr, null);
                        return;
                    }
                    setValidationState($pdr, isValidPdr(normalized));
                };

                $pdr.on('input', validatePdr);
                $pdr.on('blur', function () {
                    $pdr.val(normalizePdr($pdr.val()));
                    validatePdr();
                });
                validatePdr();
            }

        });
        select2Universale('comune_rilascio', 'un comune', 3, 'citta');


    </script>
@endpush
