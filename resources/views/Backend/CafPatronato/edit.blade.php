@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php
        $vecchio = $record->id;
        $isBackoffice = Auth::user()->hasAnyPermission(['admin','operatore','supervisore']);
    @endphp

    <div class="caf-form-page">
        <div class="caf-form-hero">
            <div>
                <div class="caf-eyebrow">CAF / Patronato</div>
                <h1>{{ $vecchio ? 'Modifica pratica' : 'Crea pratica' }}</h1>
                <p>{{ $tipoCafPatronato->nome }}: inserisci i dati cliente, completa i dettagli richiesti e carica i documenti in modo ordinato.</p>
            </div>
            <div class="caf-form-summary">
                <span>Servizio selezionato</span>
                <strong>{{ $tipoCafPatronato->nome }}</strong>
                <div class="caf-summary-prices">
                    <div>
                        <small>Prezzo cliente</small>
                        <b>{{ importo($tipoCafPatronato->prezzo_cliente) }}</b>
                    </div>
                    <div>
                        <small>Costo agente</small>
                        <b>{{ importo($tipoCafPatronato->prezzo_agente) }}</b>
                    </div>
                </div>
            </div>
        </div>

        @include('Backend._components.alertErrori')

        <form method="POST" action="{{ action([$controller,'update'], $record->id ?? '') }}" class="caf-form-shell">
            @csrf
            @method($record->id ? 'PATCH' : 'POST')
            @php
                $uid = old('uid', $record->uid);
            @endphp
            <input type="hidden" name="uid" id="uid" value="{{ $uid }}">
            <input type="hidden" name="tipo_servizio" value="{{ old('tipo_servizio', $tipoCafPatronato->id) }}">

            <div class="caf-form-grid">
                <main class="caf-form-main">
                    @if($isBackoffice)
                        <section class="caf-section">
                            <div class="caf-section-head">
                                <span class="caf-step">01</span>
                                <div>
                                    <h2>Assegnazione pratica</h2>
                                    <p>Seleziona agente e data di apertura per la gestione interna.</p>
                                </div>
                            </div>
                            <div class="caf-section-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                                    </div>
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputTextDataMask',['campo'=>'data','testo'=>'Data','required'=>true])
                                    </div>
                                </div>
                            </div>
                        </section>
                    @else
                        <input type="hidden" id="agente_id" name="agente_id" value="{{ old('agente_id', $record->agente_id) }}">
                        <input type="hidden" name="data" value="{{ old('data', $record->data->format('d/m/Y')) }}">
                    @endif

                    <section class="caf-section">
                        <div class="caf-section-head">
                            <span class="caf-step">{{ $isBackoffice ? '02' : '01' }}</span>
                            <div>
                                <h2>Dati cliente</h2>
                                <p>Anagrafica, recapiti e indirizzo della persona intestataria della pratica.</p>
                            </div>
                        </div>
                        <div class="caf-section-body">
                            <div class="caf-inline-hint">
                                <i class="bi bi-magic" aria-hidden="true"></i>
                                <span>Inserisci il codice fiscale: se il cliente esiste gia, Gestiio completa i dati disponibili.</span>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','required'=>true,'autocomplete'=>'off','classe'=>'uppercase'])
                                </div>
                                <div class="col-md-6"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','required'=>true,'autocomplete'=>'off'])
                                </div>
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'cognome','testo'=>'Cognome','required'=>true,'autocomplete'=>'off'])
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'email','testo'=>'Email','required'=>false,'autocomplete'=>'off','help' => 'Per l\'invio del documento finale al cliente è necessario inserire l\'indirizzo email'])
                                </div>
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'cellulare','testo'=>'Cellulare','required'=>true,'autocomplete'=>'off'])
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'indirizzo','testo'=>'Indirizzo','autocomplete'=>'off'])
                                </div>
                                <div class="col-md-6"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputSelect2',['campo'=>'citta','testo'=>'Citta','selected'=>\App\Models\Comune::selected(old('citta',$record->citta))])
                                </div>
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'cap','testo'=>'Cap','autocomplete'=>'off'])
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    @include('Backend._inputs.inputTextAreaCol',['campo'=>'note','testo'=>'Note','col'=>2])
                                </div>
                            </div>
                        </div>
                    </section>

                    <details class="caf-section caf-accordion-section" @if($vecchio) open @endif>
                        <summary class="caf-section-head caf-accordion-summary">
                            <span class="caf-step">{{ $isBackoffice ? '03' : '02' }}</span>
                            <div>
                                <h2>Dettagli servizio</h2>
                                <p>Completa solo i campi specifici richiesti per {{ $tipoCafPatronato->nome }}.</p>
                            </div>
                            <span class="caf-accordion-toggle" aria-hidden="true">
                                <span class="caf-accordion-open">Apri</span>
                                <span class="caf-accordion-close">Chiudi</span>
                                <i class="bi bi-chevron-down"></i>
                            </span>
                        </summary>
                        <div class="caf-section-body caf-dynamic-fields">
                            @if($tipoCafPatronato->html)
                                {!! $tipoCafPatronato->html !!}
                            @else
                                @includeWhen($tipoCafPatronato->view, "Backend.CafPatronato.Prodotti.$tipoCafPatronato->view")
                            @endif
                        </div>
                    </details>

                    <section class="caf-section">
                        <div class="caf-section-head">
                            <span class="caf-step">{{ $isBackoffice ? '04' : '03' }}</span>
                            <div>
                                <h2>Documenti allegati</h2>
                                <p>Carica documenti leggibili e coerenti con il servizio scelto. Puoi aggiungere fino a 10 file.</p>
                            </div>
                        </div>
                        <div class="caf-section-body">
                            <div class="dropzone caf-dropzone gestiio-dropzone" id="kt_dropzonejs_example_1">
                                <div class="dz-message needsclick">
                                    <span class="caf-upload-icon gestiio-dropzone-icon">
                                        <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <h3>Trascina i file qui o clicca per selezionarli</h3>
                                        <span>Documenti identita, moduli, deleghe e allegati utili alla lavorazione.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <aside class="caf-form-aside">
                    <div class="caf-sticky-card">
                        <div class="caf-aside-title">Riepilogo</div>
                        <div class="caf-aside-row">
                            <span>Stato</span>
                            <strong>{{ $vecchio ? 'Modifica' : 'Nuova pratica' }}</strong>
                        </div>
                        <div class="caf-aside-row">
                            <span>Servizio</span>
                            <strong>{{ $tipoCafPatronato->nome }}</strong>
                        </div>
                        <div class="caf-aside-row">
                            <span>Costo portafoglio</span>
                            <strong>{{ importo($tipoCafPatronato->prezzo_agente) }}</strong>
                        </div>
                        <div class="caf-aside-note">
                            Il salvataggio scala il costo dal portafoglio servizi e apre la pratica nella coda CAF/Patronato.
                        </div>
                        <button class="btn btn-primary w-100" type="submit" id="submit">
                            {{ $vecchio ? 'Salva modifiche' : 'Crea '.$tipoCafPatronato->nome }}
                        </button>

                        @if($vecchio)
                            <div class="mt-3">
                                @if($eliminabile===true)
                                    <a class="btn btn-light-danger w-100" id="elimina" href="{{ action([$controller,'destroy'], $record->id) }}">Elimina</a>
                                @elseif(is_string($eliminabile))
                                    <span data-bs-toggle="tooltip" title="{{ $eliminabile }}">
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
        .caf-form-page {
            --caf-bg: #f8fafc;
            --caf-surface: #ffffff;
            --caf-text: #020617;
            --caf-muted: #64748b;
            --caf-border: #e2e8f0;
            --caf-soft: #eef6ff;
            --caf-primary: #0ea5e9;
            --caf-primary-dark: #0369a1;
            color: var(--caf-text);
        }

        .caf-form-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 380px);
            gap: 24px;
            align-items: stretch;
            padding: 28px;
            margin-bottom: 18px;
            border: 1px solid var(--caf-border);
            border-radius: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
            box-shadow: 0 12px 36px rgba(15, 23, 42, .05);
        }

        .caf-eyebrow {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            margin-bottom: 12px;
            border-radius: 999px;
            background: #e0f2fe;
            color: var(--caf-primary-dark);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .caf-form-hero h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .caf-form-hero p {
            max-width: 760px;
            margin: 10px 0 0;
            color: var(--caf-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .caf-form-summary,
        .caf-sticky-card {
            border: 1px solid #cfe8ff;
            border-radius: 8px;
            background: rgba(255,255,255,.9);
        }

        .caf-form-summary {
            padding: 20px;
        }

        .caf-form-summary span,
        .caf-form-summary small,
        .caf-aside-row span {
            color: var(--caf-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .caf-form-summary strong {
            display: block;
            margin-top: 6px;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.35;
        }

        .caf-summary-prices {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 16px;
        }

        .caf-summary-prices div {
            padding: 12px;
            border-radius: 8px;
            background: var(--caf-bg);
        }

        .caf-summary-prices b {
            display: block;
            margin-top: 4px;
            font-size: 16px;
        }

        .caf-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 20px;
            align-items: start;
        }

        .caf-section {
            margin-bottom: 16px;
            border: 1px solid var(--caf-border);
            border-radius: 8px;
            background: var(--caf-surface);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .caf-section-head {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 18px 20px;
            border-bottom: 1px solid var(--caf-border);
            background: #fbfdff;
        }

        .caf-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 8px;
            background: #e0f2fe;
            color: var(--caf-primary-dark);
            font-size: 12px;
            font-weight: 900;
        }

        .caf-section-head h2 {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 800;
        }

        .caf-section-head p {
            margin: 0;
            color: var(--caf-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .caf-accordion-summary {
            cursor: pointer;
            list-style: none;
        }

        .caf-accordion-summary::-webkit-details-marker {
            display: none;
        }

        .caf-accordion-section:not([open]) .caf-accordion-summary {
            border-bottom: 0;
        }

        .caf-accordion-section .caf-accordion-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 86px;
            min-height: 36px;
            padding: 0 12px;
            margin-left: auto;
            border-radius: 8px;
            background: #e0f2fe;
            color: var(--caf-primary-dark);
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .caf-accordion-section .caf-accordion-toggle i {
            transition: transform .18s ease;
        }

        .caf-accordion-section[open] .caf-accordion-toggle i {
            transform: rotate(180deg);
        }

        .caf-accordion-section .caf-accordion-close {
            display: none;
        }

        .caf-accordion-section[open] .caf-accordion-open {
            display: none;
        }

        .caf-accordion-section[open] .caf-accordion-close {
            display: inline;
        }

        .caf-section-body {
            padding: 22px 20px 8px;
        }

        .caf-section-body .row.mb-6 {
            margin-bottom: 16px !important;
        }

        .caf-section-body .col-form-label {
            padding-top: 11px;
        }

        .caf-section-body .form-control,
        .caf-section-body .form-select,
        .caf-section-body .select2-container--bootstrap5 .select2-selection {
            min-height: 46px;
            border-radius: 8px;
        }

        .caf-inline-hint {
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

        .caf-inline-hint i {
            color: var(--caf-primary-dark);
        }

        .caf-dynamic-fields > .row {
            max-width: 100%;
        }

        .caf-dynamic-fields > .row > [class*="offset-md"] {
            margin-left: 0 !important;
        }

        .caf-dynamic-fields > .row > [class*="col-md"] {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .caf-dynamic-fields h4,
        .caf-dynamic-fields > strong {
            display: block;
            margin: 0 0 14px;
            color: var(--caf-text);
            font-size: 15px;
            font-weight: 900;
        }

        .caf-dynamic-fields h4:not(:first-child),
        .caf-dynamic-fields strong:not(:first-child) {
            margin-top: 18px;
        }

        .caf-dynamic-fields ul {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            padding: 0;
            margin: 12px 0 18px;
            list-style: none;
        }

        .caf-dynamic-fields li {
            min-height: 44px;
            padding: 11px 12px 11px 34px;
            border: 1px solid var(--caf-border);
            border-radius: 8px;
            background: var(--caf-bg);
            color: #334155;
            font-size: 13px;
            line-height: 1.45;
            position: relative;
        }

        .caf-dynamic-fields li::before {
            content: "";
            position: absolute;
            left: 12px;
            top: 15px;
            width: 10px;
            height: 10px;
            border: 2px solid var(--caf-primary);
            border-radius: 3px;
            background: #fff;
        }

        .caf-dynamic-fields .row .row {
            row-gap: 14px;
        }

        .caf-dynamic-fields .row .row > [class*="col-md"] {
            padding: 16px;
            border: 1px solid var(--caf-border);
            border-radius: 8px;
            background: #fbfdff;
            color: #334155;
            font-size: 13px;
            line-height: 1.7;
        }

        .caf-dynamic-fields .row .row > [class*="col-md"] strong {
            display: inline-block;
            margin-top: 10px;
            color: var(--caf-text);
            font-weight: 900;
        }

        .caf-dropzone {
            min-height: 170px;
            border: 1px dashed #93c5fd;
            border-radius: 8px;
            background: #f8fbff;
        }

        .caf-dropzone .dz-message {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            min-height: 140px;
            margin: 0;
        }

        .caf-upload-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            border-radius: 8px;
            background: #e0f2fe;
            color: var(--caf-primary-dark);
            font-size: 25px;
        }

        .caf-dropzone h3 {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 800;
        }

        .caf-dropzone span {
            color: var(--caf-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .caf-form-aside {
            position: relative;
        }

        .caf-sticky-card {
            position: sticky;
            top: 92px;
            padding: 18px;
            box-shadow: 0 12px 34px rgba(15, 23, 42, .06);
        }

        .caf-aside-title {
            margin-bottom: 14px;
            font-size: 16px;
            font-weight: 900;
        }

        .caf-aside-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 0;
            border-top: 1px solid var(--caf-border);
        }

        .caf-aside-row strong {
            max-width: 170px;
            text-align: right;
            font-size: 13px;
            font-weight: 800;
        }

        .caf-aside-note {
            margin: 14px 0;
            padding: 12px;
            border-radius: 8px;
            background: var(--caf-bg);
            color: var(--caf-muted);
            font-size: 12px;
            line-height: 1.55;
        }

        @media (max-width: 1199.98px) {
            .caf-form-grid {
                grid-template-columns: 1fr;
            }

            .caf-sticky-card {
                position: static;
            }
        }

        @media (max-width: 991.98px) {
            .caf-form-hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .caf-form-hero,
            .caf-section-head,
            .caf-section-body {
                padding-left: 16px;
                padding-right: 16px;
            }

            .caf-form-hero h1 {
                font-size: 24px;
            }

            .caf-summary-prices {
                grid-template-columns: 1fr;
            }

            .caf-dropzone .dz-message {
                flex-direction: column;
                text-align: center;
            }

            .caf-dynamic-fields ul {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@include('Backend._components.dropzoneUx')

@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
            initGestiioDropzone("#kt_dropzonejs_example_1", {
                uploadUrl: "{{action([$controller,'uploadAllegato'])}}",
                deleteUrl: "{{action([$controller,'deleteAllegato'])}}",
                csrfToken: "{{ csrf_token() }}",
                existingFiles: @json(\App\Models\AllegatoCafPatronato::perBlade($uid,$record->id)),
                sendingData: {
                    uid: function () { return $('#uid').val(); },
                    caf_patronato_id: {{$record->id ? (int) $record->id : 0}}
                }
            });

            if ($('#agente_id').is("select")) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }

            $('#codice_fiscale').blur(function (e) {

                var codice_fiscale = $(this).val();
                if (codice_fiscale === "") {
                    return;
                }
                if ($('#cognome').val() !== '') {
                    return;
                }
                var url = '{{action([\App\Http\Controllers\Backend\AjaxController::class,'post'],'cliente-cf')}}';
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
                                $('#cellulare').val(resp.cliente.cellulare || resp.cliente.telefono);
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
                    url: "/select2?citta",
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

        });
    </script>
@endpush
