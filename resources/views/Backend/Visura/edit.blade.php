@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    @php($allegatoServizioType=get_class($record))
    @php($isBackoffice = Auth::user()->hasAnyPermission(['admin','operatore']))
    <div class="visura-form-page">
        <div class="visura-form-hero">
            <div>
                <div class="visura-eyebrow">Visure</div>
                <h1>{{ $vecchio ? 'Modifica visura' : 'Crea visura' }}</h1>
                <p>{{ $tipoServizio->nome }}: raccogli dati, allegati e note in un flusso ordinato per evitare pratiche incomplete.</p>
            </div>
            <div class="visura-form-summary">
                <span>Servizio selezionato</span>
                <strong>{{ $tipoServizio->nome }}</strong>
                <div class="visura-summary-grid">
                    <div>
                        <small>Tipo</small>
                        <b>{{ ucfirst($tipoServizio->tipo_visura) }}</b>
                    </div>
                    <div>
                        <small>Allegati</small>
                        <b>{{ $tipoServizio->richiedi_allegati ? 'Richiesti' : 'Opzionali' }}</b>
                    </div>
                </div>
            </div>
        </div>

        @include('Backend._components.alertErrori')

        <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}" id="form-visura" class="visura-form-shell">
            @csrf
            @method($record->id?'PATCH':'POST')
            @php($uid=old('uid',$record->uid))

            <input type="hidden" name="uid" id="uid" value="{{$uid}}">
            <input type="hidden" name="tipo_visura_id" value="{{old('tipo_visura_id',$tipoServizio->id)}}">
            <input type="hidden" name="fallback_backoffice" id="fallback_backoffice" value="{{old('fallback_backoffice', '0')}}">

            <div class="visura-form-grid">
                <main class="visura-form-main">
                    @if($isBackoffice)
                        <section class="visura-section">
                            <div class="visura-section-head">
                                <span class="visura-step">01</span>
                                <div>
                                    <h2>Assegnazione</h2>
                                    <p>Agente, data e riferimento operativo della richiesta.</p>
                                </div>
                            </div>
                            <div class="visura-section-body">
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
                        <input type="hidden" id="agente_id" name="agente_id" value="{{old('agente_id',$record->agente_id)}}">
                        <input type="hidden" name="data" value="{{old('data',$record->data->format('d/m/Y'))}}">
                    @endif

                    <section class="visura-section">
                        <div class="visura-section-head">
                            <span class="visura-step">{{ $isBackoffice ? '02' : '01' }}</span>
                            <div>
                                <h2>Dati richiesta</h2>
                                <p>Compila i dati del soggetto o dell'immobile in base alla visura selezionata.</p>
                            </div>
                        </div>
                        <div class="visura-section-body visura-dynamic-fields">
                            <div class="visura-inline-hint">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <span>Per aziende puoi usare la ricerca guidata; per visure catastali controlla provincia, soggetto e identificativi immobile.</span>
                            </div>
                            @includeWhen($tipoServizio->tipo_visura=='azienda','Backend.Visura.azienda')
                            @includeWhen($tipoServizio->tipo_visura=='privato','Backend.Visura.privato')
                            @includeWhen($isCatastale ?? false,'Backend.Visura.catasto')
                            @if($tipoServizio->html)
                                {!! $tipoServizio->html !!}
                            @endif
                        </div>
                    </section>

                    <section class="visura-section">
                        <div class="visura-section-head">
                            <span class="visura-step">{{ $isBackoffice ? '03' : '02' }}</span>
                            <div>
                                <h2>Note e documenti</h2>
                                <p>Note interne e file utili alla lavorazione della visura.</p>
                            </div>
                        </div>
                        <div class="visura-section-body">
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputTextAreaCol',['campo'=>'note','testo'=>'Note','col'=>2])
                                </div>
                                @if($tipoServizio->richiedi_allegati)
                                    <div class="col-md-6">
                                        <div class="dropzone visura-dropzone gestiio-dropzone" id="kt_dropzonejs_example_1">
                                            <div class="dz-message needsclick">
                                                <span class="gestiio-dropzone-icon">
                                                    <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                                                </span>
                                                <div>
                                                    <h3>Trascina i file qui o clicca per selezionarli</h3>
                                                    <span>Documenti, deleghe, identificativi catastali e allegati necessari alla richiesta.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>
                </main>

                <aside class="visura-form-aside">
                    <div class="visura-sticky-card">
                        <div class="visura-aside-title">Riepilogo</div>
                        <div class="visura-aside-row">
                            <span>Stato</span>
                            <strong>{{ $vecchio ? 'Modifica' : 'Nuova visura' }}</strong>
                        </div>
                        <div class="visura-aside-row">
                            <span>Servizio</span>
                            <strong>{{ $tipoServizio->nome }}</strong>
                        </div>
                        <div class="visura-aside-row">
                            <span>Modalita</span>
                            <strong>{{ $tipoServizio->richiedi_allegati ? 'Con allegati' : 'Senza allegati' }}</strong>
                        </div>
                        <div class="visura-aside-note">
                            Salva quando dati e documenti sono completi. L’addebito avviene sempre sul portafoglio visure (listino Gestiio). Senza token Openapi sul profilo agente la pratica va in coda backoffice.
                        </div>
                        <button class="btn btn-primary w-100" type="submit" id="submit">
                            {{$vecchio?'Salva modifiche':'Crea '.\App\Models\Visura::NOME_SINGOLARE}}
                        </button>

                        @if($vecchio && Auth::user()->hasAnyPermission(['admin','operatore']) && ($record->openapi_stato_richiesta ?? '') === 'backoffice')
                            <button type="button" class="btn btn-light-info w-100 mt-3" id="btn-riprova-openapi">
                                Riprova Openapi
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

@include('Backend._components.dropzoneUx')

@push('customCss')
    <style>
        .visura-form-page {
            --visura-bg: #f8fafc;
            --visura-surface: #ffffff;
            --visura-text: #020617;
            --visura-muted: #64748b;
            --visura-border: #e2e8f0;
            --visura-primary: #0ea5e9;
            --visura-primary-dark: #0369a1;
            color: var(--visura-text);
        }

        .visura-form-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 380px);
            gap: 24px;
            align-items: stretch;
            padding: 28px;
            margin-bottom: 18px;
            border: 1px solid var(--visura-border);
            border-radius: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
            box-shadow: 0 12px 36px rgba(15, 23, 42, .05);
        }

        .visura-eyebrow {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            margin-bottom: 12px;
            border-radius: 999px;
            background: #e0f2fe;
            color: var(--visura-primary-dark);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .visura-form-hero h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .visura-form-hero p {
            max-width: 760px;
            margin: 10px 0 0;
            color: var(--visura-muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .visura-form-summary,
        .visura-sticky-card {
            border: 1px solid #cfe8ff;
            border-radius: 8px;
            background: rgba(255, 255, 255, .9);
        }

        .visura-form-summary {
            padding: 20px;
        }

        .visura-form-summary span,
        .visura-form-summary small,
        .visura-aside-row span {
            color: var(--visura-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .visura-form-summary strong {
            display: block;
            margin-top: 6px;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.35;
        }

        .visura-summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 16px;
        }

        .visura-summary-grid div {
            padding: 12px;
            border-radius: 8px;
            background: var(--visura-bg);
        }

        .visura-summary-grid b {
            display: block;
            margin-top: 4px;
            font-size: 16px;
        }

        .visura-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 20px;
            align-items: start;
        }

        .visura-section {
            margin-bottom: 16px;
            border: 1px solid var(--visura-border);
            border-radius: 8px;
            background: var(--visura-surface);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .visura-section-head {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 18px 20px;
            border-bottom: 1px solid var(--visura-border);
            background: #fbfdff;
        }

        .visura-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 8px;
            background: #e0f2fe;
            color: var(--visura-primary-dark);
            font-size: 12px;
            font-weight: 900;
        }

        .visura-section-head h2 {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 800;
        }

        .visura-section-head p {
            margin: 0;
            color: var(--visura-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .visura-section-body {
            padding: 22px 20px 8px;
        }

        .visura-section-body .row.mb-6 {
            margin-bottom: 16px !important;
        }

        .visura-section-body .col-form-label {
            padding-top: 11px;
        }

        .visura-section-body .form-control,
        .visura-section-body .form-select,
        .visura-section-body .select2-container--bootstrap5 .select2-selection {
            min-height: 46px;
            border-radius: 8px;
        }

        .visura-inline-hint {
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

        .visura-dynamic-fields > .row {
            max-width: 100%;
        }

        .visura-dynamic-fields > .row > [class*="offset-md"] {
            margin-left: 0 !important;
        }

        .visura-sticky-card {
            position: sticky;
            top: 92px;
            padding: 18px;
            box-shadow: 0 12px 34px rgba(15, 23, 42, .06);
        }

        .visura-aside-title {
            margin-bottom: 14px;
            font-size: 16px;
            font-weight: 900;
        }

        .visura-aside-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 0;
            border-top: 1px solid var(--visura-border);
        }

        .visura-aside-row strong {
            max-width: 170px;
            text-align: right;
            font-size: 13px;
            font-weight: 800;
        }

        .visura-aside-note {
            margin: 14px 0;
            padding: 12px;
            border-radius: 8px;
            background: var(--visura-bg);
            color: var(--visura-muted);
            font-size: 12px;
            line-height: 1.55;
        }

        @media (max-width: 1199.98px) {
            .visura-form-grid {
                grid-template-columns: 1fr;
            }

            .visura-sticky-card {
                position: static;
            }
        }

        @media (max-width: 991.98px) {
            .visura-form-hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .visura-form-hero,
            .visura-section-head,
            .visura-section-body {
                padding-left: 16px;
                padding-right: 16px;
            }

            .visura-form-hero h1 {
                font-size: 24px;
            }

            .visura-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
            if ($('#agente_id').is("select")) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }
            if ($('#provincia_ricerca').length) {
                select2UniversaleBackend('provincia_ricerca', 'una provincia');
            }

            @if(session('openapi_credito_bloccato') && !$vecchio)
            Swal.fire({
                icon: 'warning',
                title: 'Servizio automatico non disponibile',
                text: 'Credito Openapi dell’agente insufficiente oppure token non valido. Puoi ricaricare il portafoglio visure Gestiio, verificare email/API key Openapi sul profilo, oppure inviare la pratica al backoffice.',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Ricarica portafoglio visure',
                denyButtonText: 'Invia al backoffice',
                cancelButtonText: 'Annulla',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = "{{action([\App\Http\Controllers\Backend\RicaricaPlafonController::class,'show'], ['tab_portafoglio' => \App\Enums\TipiPortafoglioEnum::VISURE->value])}}";
                    return;
                }
                if (result.isDenied) {
                    $('#fallback_backoffice').val('1');
                    $('#form-visura').trigger('submit');
                }
            });
            @endif

            $('#btn-riprova-openapi').on('click', function () {
                const $btn = $(this);
                $btn.prop('disabled', true);
                $.ajax({
                    url: "{{ $vecchio ? action([\App\Http\Controllers\Backend\VisuraController::class,'richiediOpenApi'], $record->id) : '#' }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function (res) {
                        Swal.fire({
                            icon: res.success ? 'success' : 'error',
                            title: res.success ? 'Richiesta inviata' : 'Errore',
                            text: res.message || ''
                        }).then(function () {
                            if (res.success) {
                                window.location.reload();
                            }
                        });
                    },
                    error: function () {
                        Swal.fire({icon: 'error', title: 'Errore', text: 'Impossibile riprovare Openapi'});
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });

            initGestiioDropzone("#kt_dropzonejs_example_1", {
                uploadUrl: "{{action([\App\Http\Controllers\Backend\AllegatoServizioController::class,'uploadAllegato'])}}",
                deleteUrl: "{{action([\App\Http\Controllers\Backend\AllegatoServizioController::class,'deleteAllegato'])}}",
                csrfToken: "{{ csrf_token() }}",
                existingFiles: @json(\App\Models\AllegatoServizio::perBlade($uid,$record->id,$allegatoServizioType)),
                sendingData: {
                    uid: function () { return $('#uid').val(); },
                    allegato_id: {{$record->id??'0'}},
                    allegato_type: '{{str_replace('\\','_',$allegatoServizioType)}}'
                }
            });


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


            $('#partita_iva').blur(function () {

                if ($('#ragione_sociale').val() !== '') {
                    return;
                }

                var url = "{{action([\App\Http\Controllers\RegistratiController::class,'verificaPIvaEu'])}}";
                $.ajax(url,
                    {
                        dataType: 'json',
                        method: 'POST',
                        data: {
                            'partita_iva': $('#partita_iva').val(),
                            '_token': '{{csrf_token()}}'
                        },
                        success: function (resp) {
                            if (resp.success) {
                                $('#ragione_sociale').val(resp.res.name);
                                $('#indirizzo').val(resp.res.address);
                            }
                        }
                    });
            });

            $('#btn-ricerca-azienda').on('click', function () {
                const denominazione = ($('#ricerca_azienda_denominazione').val() || '').trim();
                const provinciaId = ($('#provincia_ricerca').val() || '').trim();
                const url = $(this).data('url');
                if (!denominazione) {
                    Swal.fire('Attenzione', 'Inserisci la denominazione da cercare', 'warning');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: (function () {
                        const payload = {
                            denominazione: denominazione,
                            _token: '{{csrf_token()}}'
                        };
                        if (provinciaId) {
                            payload.provincia_id = provinciaId;
                        }
                        return payload;
                    })(),
                    success: function (resp) {
                        const $box = $('#box-risultati-ricerca-azienda');
                        const $tbody = $('#tbody-risultati-ricerca-azienda');
                        $tbody.html('');

                        if (!resp.success || !resp.items || !resp.items.length) {
                            $box.addClass('d-none');
                            Swal.fire('Nessun risultato', resp.message || 'Nessuna azienda trovata', 'info');
                            return;
                        }

                        resp.items.forEach(function (item) {
                            const tr = $('<tr></tr>');
                            tr.append('<td>' + (item.denominazione || '-') + '</td>');
                            let pivaText = item.partita_iva || '-';
                            if (item.partita_iva && item.vies_valid === true) {
                                pivaText += ' <span class="badge badge-light-success ms-1">VIES OK</span>';
                            } else if (item.partita_iva && item.vies_valid === false) {
                                pivaText += ' <span class="badge badge-light-danger ms-1">VIES KO</span>';
                            }
                            tr.append('<td>' + pivaText + '</td>');
                            tr.append('<td>' + (item.comune || '-') + '</td>');
                            tr.append('<td>' + (item.natura_giuridica || '-') + '</td>');
                            const $btnUsa = $('<button type="button" class="btn btn-sm btn-primary">Usa dati</button>');
                            $btnUsa.on('click', function () {
                                const applyBaseData = function () {
                                    if (item.denominazione) {
                                        $('#ragione_sociale').val(item.denominazione);
                                    }
                                    if (item.indirizzo) {
                                        $('#indirizzo').val(item.indirizzo);
                                    }
                                };

                                applyBaseData();

                                if (item.partita_iva) {
                                    $('#partita_iva').val(item.partita_iva);
                                    Swal.fire('Completato', 'Dati azienda inseriti nel form', 'success');
                                    return;
                                }

                                const previousText = $btnUsa.text();
                                $btnUsa.prop('disabled', true).text('Recupero P.IVA...');

                                $.ajax({
                                    url: "{{action([\App\Http\Controllers\Backend\VisuraController::class,'ricercaAziendaDettaglio'])}}",
                                    type: 'POST',
                                    dataType: 'json',
                                    data: {
                                        denominazione: item.denominazione || '',
                                        provincia_id: ($('#provincia_ricerca').val() || '').trim(),
                                        raw: JSON.stringify(item.raw || {}),
                                        _token: '{{csrf_token()}}'
                                    },
                                    success: function (detailResp) {
                                        if (detailResp && detailResp.success && detailResp.partita_iva) {
                                            $('#partita_iva').val(detailResp.partita_iva);
                                            Swal.fire('Completato', 'Dati azienda inseriti nel form', 'success');
                                            return;
                                        }
                                        Swal.fire('Attenzione', (detailResp && detailResp.message) ? detailResp.message : 'Partita IVA non trovata automaticamente.', 'warning');
                                    },
                                    error: function () {
                                        Swal.fire('Errore', 'Recupero partita IVA non disponibile al momento.', 'error');
                                    },
                                    complete: function () {
                                        $btnUsa.prop('disabled', false).text(previousText);
                                    }
                                });
                            });
                            const td = $('<td class="text-end"></td>');
                            td.append($btnUsa);
                            tr.append(td);
                            $tbody.append(tr);
                        });

                        $box.removeClass('d-none');
                    },
                    error: function () {
                        Swal.fire('Errore', 'Ricerca azienda non disponibile al momento', 'error');
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });

            const isCatastale = @json((bool)($isCatastale ?? false));
            const toggleCatastoFields = function () {
                if (!isCatastale || !$('#catasto_entita').length) {
                    return;
                }

                const entita = $('#catasto_entita').val();
                const showImmobile = entita === 'immobile';
                $('#catasto_immobile_fields').toggle(showImmobile);
                $('#foglio_catasto, #particella_catasto').prop('required', showImmobile);

                if ($('#catasto_entita_hint').length) {
                    $('#catasto_entita_hint').text(
                        showImmobile
                            ? 'Per ricerca per immobile servono Foglio e Particella.'
                            : 'Per ricerca per soggetto usa ID soggetto oppure CF/P.IVA + Provincia.'
                    );
                }
            };

            if (isCatastale) {
                $('#provincia_catasto').on('input', function () {
                    this.value = this.value.toUpperCase();
                });

                $('#catasto_entita').on('change', toggleCatastoFields);
                toggleCatastoFields();
            }

        });
    </script>
@endpush
