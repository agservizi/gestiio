@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    <div class="brt-create-page">
        <section class="brt-create-hero mb-8">
            <div>
                <div class="brt-create-kicker">Spedizione BRT</div>
                <h1 class="brt-create-title">{{$record->id ? 'Modifica spedizione' : 'Nuova spedizione'}}</h1>
                <p class="brt-create-text">Compila destinatario, colli, mittente e servizi accessori in un unico flusso operativo, mantenendo intatta la logica BRT gia presente.</p>
            </div>
            <div class="brt-create-docs">
                <span class="brt-create-doc-pill">{{$zona === 'ITALIA' ? 'Italia' : 'Europa'}}</span>
                <span class="brt-create-doc-pill">Colli</span>
                <span class="brt-create-doc-pill">Tracking</span>
            </div>
        </section>
    <div class="row">
        <div class="col-md-9">
            <div class="card brt-form-shell">
                <div class="card-body p-8 p-lg-10">
                    @include('Backend._components.alertErrori')
                    <form method="POST"
                          action="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'update'],$record->id??'')}}"
                          id="form-brt" onsubmit="return disableSubmitButton()">
                        @csrf
                        @method($record->id?'PATCH':'POST')
                        @php($vecchio=$record->id)
                        @if(Auth::user()->hasAnyPermission(['admin']))
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                                </div>
                            </div>
                        @else
                            <input type="hidden" id="agente_id" name="agente_id"
                                   value="{{old('agente_id',$record->agente_id)}}">
                        @endif
                        <div class="brt-section-card mb-8">
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputText',['campo'=>'ragione_sociale_destinatario','testo'=>'Ragione sociale destinatario','required'=>true,'autocomplete'=>'off','include' => 'Backend.SpedizioneBrt.ricerca'])
                            </div>
                            @if(!$vecchio)
                                <div class="col-md-6" id="div_salva_anagrafica">
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" value="1" id="salva_anagrafica"
                                               name="salva_anagrafica"/>
                                        <label class="form-check-label" for="salva_anagrafica">
                                            Salva nei preferiti
                                        </label>
                                    </div>
                                </div>
                            @endif
                        </div>
                        </div>

                        <div class="brt-section-card mb-8">
                        <div class="row">
                            <div class="col-md-6">
                                @if($zona=='ITALIA')
                                    @include('Backend._inputs.inputTextReadonly',['campo'=>'aaa','testo'=>'Nazione','valore'=>'Italia'])
                                    @include('Backend._inputs.inputHidden',['campo'=>'nazione_destinazione'])
                                @else
                                    @include('Backend._inputs.inputSelect2',['campo'=>'nazione_destinazione','testo'=>'Nazione destinazione','required'=>true,'selected'=>\App\Models\Nazione::selected(old('nazione_destinazione',$record->nazione_destinazione))])
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if($zona=='ITALIA')
                                    @include('Backend._inputs.inputSelect2',['campo'=>'provincia_destinatario','testo'=>'Provincia destinazione','required'=>false,'selected'=>\App\Models\Provincia::selectedString(old('provincia_destinatario',$record->provincia_destinatario))])
                                @endif
                            </div>
                            <div class="col-md-6">
                                @include('Backend._inputs.inputText',['campo'=>'indirizzo_destinatario','testo'=>'Indirizzo destinatario','required'=>true,'autocomplete'=>'off','altro' => 'wire:model="pippo"'])
                            </div>
                            <div class="col-md-6">
                                @include('Backend._inputs.inputText',['campo'=>'localita_destinazione','testo'=>'Località destinazione','required'=>true,'autocomplete'=>'off'])
                            </div>
                            <div class="col-md-6">
                                @include('Backend._inputs.inputText',['campo'=>'cap_destinatario','testo'=>'Cap destinazione','required'=>true,'autocomplete'=>'off'])
                            </div>
                        </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputText',['campo'=>'mobile_referente_consegna','testo'=>'Mobile referente consegna','required'=>true,'autocomplete'=>'off'])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputHidden',['campo'=>'numero_pacchi','testo'=>'Numero pacchi','required'=>true,'autocomplete'=>'off'])
                            </div>
                            <div class="col-md-6">
                                @include('Backend._inputs.inputHidden',['campo'=>'peso_totale','testo'=>'Peso totale','required'=>true,'classe'=>'peso','altro' => 'wire:model="peso"'])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputHidden',['campo'=>'volume_totale','testo'=>'Volume totale','required'=>true,'testoButton'=>'Calcola','classe'=>'importo'])
                            </div>
                        </div>

                        <div id="div_svizzera" class="mb-6"
                             style="@if($record->nazione_destinazione!=='CH') display:none; @endif">
                            <table class="w-100 fw-bold">
                                <tr>
                                    <td>Full description of goods</td>
                                    <td>Custom Commodity Code</td>
                                    <td>Country of Origin</td>
                                    <td>Pieces</td>
                                    <td>Unit Value and Currency</td>
                                    <td>Sub Total Value and Currency</td>
                                </tr>
                                @for($n=0;$n<=2;$n++)
                                    <tr>
                                        <td>
                                            <input type="text"
                                                   class="form-control form-control-solid form-control-sm"
                                                   name="altri_dati[dati_pdf][{{$n}}][description]"
                                                   value="{{old("altri_dati.dati_pdf.$n.description",$record->altri_dati['dati_pdf'][$n]['description']??'')}}">
                                        </td>
                                        <td>
                                            <input type="text"
                                                   class="form-control form-control-solid form-control-sm"
                                                   name="altri_dati[dati_pdf][{{$n}}][code]"
                                                   value="{{old("altri_dati.dati_pdf.$n.code",$record->altri_dati['dati_pdf'][$n]['code']??'')}}">
                                        </td>
                                        <td>
                                            <input type="text"
                                                   class="form-control form-control-solid form-control-sm"
                                                   name="altri_dati[dati_pdf][{{$n}}][country]"
                                                   value="{{old("altri_dati.dati_pdf.$n.country",$record->altri_dati['dati_pdf'][$n]['country']??'')}}">
                                        </td>
                                        <td>
                                            <input type="text"
                                                   class="form-control form-control-solid form-control-sm"
                                                   name="altri_dati[dati_pdf][{{$n}}][pieces]"
                                                   value="{{old("altri_dati.dati_pdf.$n.pieces",$record->altri_dati['dati_pdf'][$n]['pieces']??'')}}">
                                        </td>
                                        <td>
                                            <input type="text"
                                                   class="form-control form-control-solid form-control-sm"
                                                   name="altri_dati[dati_pdf][{{$n}}][value]"
                                                   value="{{old("altri_dati.dati_pdf.$n.value",$record->altri_dati['dati_pdf'][$n]['value']??'')}}">
                                        </td>

                                        <td>
                                            <input type="text"
                                                   class="form-control form-control-solid form-control-sm"
                                                   name="altri_dati[dati_pdf][{{$n}}][sub_total]"
                                                   value="{{old("altri_dati.dati_pdf.$n.sub_total",$record->altri_dati['dati_pdf'][$n]['sub_total']??'')}}">
                                        </td>
                                    </tr>
                                @endfor
                                <tr>
                                    <td colspan="5" class="text-end">
                                        Total Value and Currency
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-solid form-control-sm"
                                               name="altri_dati[dati_pdf][total_value]"
                                               value="{{old("altri_dat.dati_pdf.total_value",$record->altri_dati['dati_pdf']['total_value']??'')}}">
                                    </td>
                                </tr>
                            </table>
                            <div class="row">
                                <div class="col-md-6">
                                    <label>REASON FOR EXPORT</label>
                                    <input type="text"
                                           class="form-control form-control-solid form-control-sm"
                                           name="altri_dati[dati_pdf][reason]"
                                           value="{{old("altri_dat.dati_pdf.reason",$record->altri_dati['dati_pdf']['reason']??'')}}">
                                </div>
                                <div class="col-md-6">
                                    <label>TERMS OF DELIVERY</label>
                                    <input type="text"
                                           class="form-control form-control-solid form-control-sm"
                                           name="altri_dati[dati_pdf][terms]"
                                           value="{{old("altri_dat.dati_pdf.terms",$record->altri_dati['dati_pdf']['terms']??'')}}">
                                </div>
                                <div class="col-md-6">
                                    <label>these products are of </label>
                                    <input type="text"
                                           class="form-control form-control-solid form-control-sm"
                                           name="altri_dati[dati_pdf][are_of]"
                                           value="{{old("altri_dat.dati_pdf.are_of",$record->altri_dati['dati_pdf']['are_of']??'')}}">
                                </div>
                            </div>
                        </div>
                        @include('Backend.SpedizioneBrt.repeaterColli')
                        <div class="brt-section-card mb-8">
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputTextButton',['campo'=>'pudo_id','testo'=>'Pudo','autocomplete'=>'off','testoButton'=>'Cerca','classe'=>'cerca'])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputText',['campo'=>'nome_mittente','testo'=>'Nome mittente','required'=>true,'autocomplete'=>'off'])
                            </div>
                            <div class="col-md-6">
                                @include('Backend._inputs.inputText',['campo'=>'cap_mittente','testo'=>'Cap mittente','required'=>true,'autocomplete'=>'off'])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputText',['campo'=>'email_mittente','testo'=>'Email mittente','autocomplete'=>'off'])
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('Backend._inputs.inputText',['campo'=>'mobile_mittente','testo'=>'Mobile mittente','autocomplete'=>'off'])
                            </div>
                        </div>
                        </div>
                        @if($zona=='ITALIA')
                            <div class="brt-section-card mb-8">
                            <h4 class="brt-section-title-sm">Particolarita</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    @include('Backend._inputs.inputText',['campo'=>'contrassegno','testo'=>'Contrassegno','autocomplete'=>'off','classe' => 'importo'])
                                </div>
                            </div>
                            </div>
                        @endif
                        <div class="brt-submit-bar">
                            <div class="brt-submit-copy">
                                <div class="brt-submit-title">La spedizione e pronta per essere salvata.</div>
                                <div class="brt-submit-text">Controlla i dati inseriti e conferma per continuare.</div>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                            <button class="btn brt-submit-button" type="submit"
                                        id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\SpedizioneBrt::NOME_SINGOLARE}}</button>
                            @if($vecchio)
                                    @if($eliminabile===true)
                                        <a class="btn btn-danger" id="elimina"
                                           href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                                    @elseif(is_string($eliminabile))
                                        <span data-bs-toggle="tooltip" title="{{$eliminabile}}">
                                    <a class="btn btn-danger disabled" href="javascript:void(0)">Elimina</a>
                                </span>
                                    @endif
                            @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush brt-summary-shell" data-kt-sticky="true" data-kt-sticky-name="docs-sticky-summary"
                 data-kt-sticky-offset="{default: false, xl: '50px'}" data-kt-sticky-width="{lg: '250px', xl: '300px'}"
                 data-kt-sticky-left="auto" data-kt-sticky-top="50px" data-kt-sticky-animation="false"
                 data-kt-sticky-zindex="95" style="">
                <div class="card-header border-0 pt-7">
                    <div>
                        <div class="brt-summary-kicker">Summary</div>
                        <h3 class="card-title fs-2 fw-bolder mb-0">Riepilogo spedizione</h3>
                    </div>
                    <div class="card-toolbar">

                    </div>
                </div>
                <div class="card-body">
                    <div class="brt-summary-card">
                        <span class="brt-summary-label">Tariffa</span>
                        <div id="tariffa_dx" class="brt-summary-value brt-summary-value-sm">

                        </div>
                    </div>
                    <div class="brt-summary-card">
                        <span class="brt-summary-label">Indirizzo destinatario</span>
                        <div id="indirizzo_destinatario_dx" class="brt-summary-value brt-summary-value-sm">

                        </div>
                        <div id="localita_destinazione_dx" class="brt-summary-value brt-summary-value-sm">

                        </div>
                    </div>
                    <div class="brt-summary-card">
                        <span class="brt-summary-label">Pacchi</span>
                        <div id="numero_pacchi_dx" class="brt-summary-value">
                            {{\App\intero($record->numero_pacchi,true)}}
                        </div>
                    </div>
                    <div class="brt-summary-card">
                        <span class="brt-summary-label">Peso totale</span>
                        <div id="peso_totale_dx" class="brt-summary-value">
                            {{number_format($record->peso_totale,1,',','.')}}
                        </div>
                    </div>
                    <div class="brt-summary-card">
                        <span class="brt-summary-label">Contrassegno</span>
                        <div id="contrassegno_dx" class="brt-summary-value">
                            {{\App\importo($record->contrassegno,true)}}
                        </div>
                    </div>
                    <div class="brt-summary-card mb-0">
                        <span class="brt-summary-label">Prezzo spedizione</span>
                        <div id="prezzo_dx" class="brt-summary-value">
                            {{\App\importo($record->prezzo_spedizione,true)}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

@endsection
@push('customCss')
    <style>
        #infoAggiuntive {
            width: 100%;
            position: absolute;
            background-color: #fff;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        .brt-create-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .brt-create-hero {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 1.5rem;
            padding: 2rem;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(99, 129, 255, 0.28), transparent 28%),
                linear-gradient(135deg, #0f1b3d 0%, #162654 58%, #1b2f67 100%);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .brt-create-kicker,
        .brt-summary-kicker {
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

        .brt-create-title {
            max-width: 14ch;
            margin-bottom: .65rem;
            font-size: clamp(2rem, 3vw, 3.15rem);
            line-height: 1.03;
            font-weight: 800;
            color: #fff;
        }

        .brt-create-text {
            max-width: 70ch;
            margin-bottom: 0;
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
        }

        .brt-create-docs {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .65rem;
        }

        .brt-create-doc-pill {
            padding: .55rem .8rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: #fff;
            font-size: .9rem;
            font-weight: 600;
        }

        .brt-form-shell {
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfd 100%);
            border-radius: 22px;
            border: 1px solid #e3e6ec;
            box-shadow: 0 20px 60px rgba(22, 28, 45, 0.06);
        }

        .brt-section-card {
            background: #fff;
            border: 1px solid #e1e6f0;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 24px rgba(18, 24, 39, 0.04);
        }

        .brt-section-title-sm {
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 800;
            color: #20242c;
        }

        .brt-submit-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem 1.25rem;
            border-radius: 18px;
            border: 1px solid #dbe3ff;
            background: linear-gradient(135deg, #f6f8ff 0%, #edf2ff 100%);
        }

        .brt-submit-title {
            font-size: 1.02rem;
            font-weight: 800;
            color: #1f2750;
        }

        .brt-submit-text {
            color: #5b678a;
            font-size: .92rem;
        }

        .brt-submit-button {
            min-height: 48px;
            padding: .8rem 1.25rem;
            border-radius: 999px;
            background: #0f1b3d;
            color: #fff;
            border: 1px solid #0f1b3d;
            font-weight: 800;
        }

        .brt-submit-button:hover {
            color: #fff;
            background: #1d2d61;
            border-color: #1d2d61;
        }

        .brt-summary-shell {
            border-radius: 22px;
            border: 1px solid #e3e6ec;
            box-shadow: 0 20px 60px rgba(22, 28, 45, 0.06);
            overflow: hidden;
        }

        .brt-summary-card {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            margin-bottom: 1rem;
            padding: 1rem 1.1rem;
            border-radius: 16px;
            background: linear-gradient(180deg, #f7f9fc 0%, #f3f5f9 100%);
            border: 1px solid #e8edf4;
        }

        .brt-summary-label {
            color: #6b7383;
            font-size: .88rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .brt-summary-value {
            color: #1f2631;
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1;
        }

        .brt-summary-value-sm {
            font-size: 1.1rem;
            line-height: 1.35;
        }

        @media (max-width: 991px) {
            .brt-create-hero,
            .brt-submit-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
@endpush
@push('customScript')
    <script src="/assets_backend/plugins/custom/formrepeater/formrepeater.bundle.js"></script>
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script src="/assets_backend/js-miei/numeral.min.js"></script>
    <script src="/assets_backend/js-miei/numeralIt.min.js"></script>
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        var vecchio = {{$vecchio?'true':'false'}};

        function imposta(ragione_sociale_destinatario, indirizzo_destinatario, cap_destinatario, localita_destinazione, mobile_referente_consegna, provincia_destinatario, nome_provincia) {
            $('#ragione_sociale_destinatario').val(ragione_sociale_destinatario);
            $('#indirizzo_destinatario').val(indirizzo_destinatario);
            $('#cap_destinatario').val(cap_destinatario);
            $('#localita_destinazione').val(localita_destinazione);
            $('#mobile_referente_consegna').val(mobile_referente_consegna);
            var option = new Option(nome_provincia, provincia_destinatario, true, true); // Testo e valore dell'opzione
            $('#provincia_destinatario').append(option).trigger('change');
            $('#infoAggiuntive').hide();
            $('#div_salva_anagrafica').hide();
        }

        function aggiornaPrezzoBrt() {
            if (!$('#nazione_destinazione').val()) {
                return;
            }

            var form = $('#form-brt');

            var url = '{{action([\App\Http\Controllers\Backend\AjaxController::class,'post'],'aggiorna-prezzo-brt')}}';
            $.ajax({
                url: url,
                type: 'post',
                dataType: 'json',
                method: 'POST',
                data: form.find(':not(input[name=_method])').serialize(),
                success: function (resp) {
                    if (resp.success) {
                        $('#prezzo_dx').text(resp.prezzo);
                        $('#contrassegno_dx').text(resp.contrassegno);
                        $('#tariffa_dx').text(resp.tariffa);
                    } else {
                        Swal.fire(
                            "Errore",
                            resp.message,
                            "warning"
                        )
                    }
                }
            });
        }

        $(function () {
            if (!vecchio) {
                $('#ragione_sociale_destinatario').keyup(function () {
                    $.ajax({
                        url: '{{action([\App\Http\Controllers\Backend\Select2::class,'response'])}}',
                        dataType: 'json',
                        data: {
                            ragione_spedizioni: true,
                            term: $(this).val()
                        },
                        success: function (response) {

                            if (response.risultati >= 1) {
                                $('#infoAggiuntive').html(base64_decode(response.html)).show();
                                $('#infoAggiuntive').show();
                            } else {
                                $('#infoAggiuntive').text('').hide();

                            }

                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            var err = eval("(" + xhr.responseText + ")");

                            alert(err.message);
                        }
                    });
                });
            }

            if ($('#agente_id').is("select")) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }


            var stickyElement = document.querySelector("#kt_sticky_control");
            var sticky = new KTSticky(stickyElement);


            numeral.locale('it');
            var conteggioColli = 1;


            aggiornaConteggioColli();
            aggiornaPeso();

            $('#dati_colli').repeater({
                initEmpty: false,
                defaultValues: {
                    'text-input': ''
                },
                show: function () {
                    $(this).slideDown();
                    $('.ricalcola').donetyping(function () {
                        aggiornaPeso();
                    });
                    conteggioColli = $('.item-collo').length;
                    aggiornaConteggioColli();
                },
                hide: function (deleteElement) {
                    $(this).slideUp(deleteElement);

                    setTimeout(function () {
                        aggiornaPeso();
                        conteggioColli--;
                        aggiornaConteggioColli();
                        aggiornaPrezzoBrt();

                    }, 700);

                },
                ready: function () {

                    autonumericIntero('intero');
                    autonumericPeso('peso_reale');

                    $('.ricalcola').donetyping(function () {
                        aggiornaPeso();
                    });
                    aggiornaConteggioColli();

                }
            });


            $('#contrassegno').donetyping(function () {
                aggiornaPrezzoBrt();
            });
            $('#pudo_id').donetyping(function () {
                aggiornaPrezzoBrt();
            });


            function aggiornaConteggioColli() {
                $('#numero_pacchi_dx').text(conteggioColli);
                $('#numero_pacchi').val(conteggioColli);
                $('#avviso-multicollo').toggle(conteggioColli > 1);

            }


            function aggiornaPeso() {

                console.log('ricalcola');
                var volume_totale = 0;
                var peso_totale = 0;


                var elements = $('.item-collo');
                elements.each(function () {
                    var larghezza = numeral($(this).find(".larghezza").val()).value() ?? 0;
                    var altezza = numeral($(this).find(".altezza").val()).value() ?? 0;
                    var profondita = numeral($(this).find(".profondita").val()).value() ?? 0;
                    var peso_reale = numeral($(this).find(".peso_reale").val()).value() ?? 0;


                    var volume = larghezza / 100 * altezza / 100 * profondita / 100;

                    volume_totale += volume;

                    var peso_volumetrico = larghezza * altezza * profondita / 4000;

                    peso_totale += (peso_reale > peso_volumetrico ? peso_reale : peso_volumetrico);

                    $(this).find(".peso-vol").text(
                        new Intl.NumberFormat('it-IT', {
                            minimumFractionDigits: 1,
                            maximumFractionDigits: 1,
                        }).format(peso_volumetrico)
                    );
                    $(this).find(".peso_volumetrico").val(peso_volumetrico);

                });


                const volume_totale_txt = new Intl.NumberFormat('it-IT', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(volume_totale);
                $('#volume_totale_span').text(volume_totale_txt);
                $('#volume_totale').val(volume_totale_txt);

                const peso_totale_txt = new Intl.NumberFormat('it-IT', {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                }).format(peso_totale);
                $('#peso_totale_dx').text('Kg ' + peso_totale_txt);
                $('#peso_totale').val(peso_totale_txt);
                aggiornaPrezzoBrt();
            }


            $('#indirizzo_destinatario').keyup(function () {
                $('#indirizzo_destinatario_dx').text($('#indirizzo_destinatario').val());
            });
            $('#localita_destinazione').keyup(function () {
                $('#localita_destinazione_dx').text($('#localita_destinazione').val());
            });

            if ($('#provincia_destinatario')) {
                select2UniversaleBackend('provincia_destinatario', 'una provincia', 1);
            }


            function autonumericPeso(classe) {
                var slides = document.getElementsByClassName(classe);
                for (var i = 0; i < slides.length; i++) {
                    if (AutoNumeric.getAutoNumericElement(slides.item(i)) === null) {
                        new AutoNumeric(slides.item(i), {
                            decimalCharacter: ",",
                            decimalPlaces: 1,
                            digitGroupSeparator: ".",
                            watchExternalChanges: true,
                        });
                    }
                }
            }


            eliminaHandler('Questa voce verrà eliminata definitivamente');

            const element = document.getElementById('nazione_destinazione');
            if (element.tagName.toLowerCase() === 'select') {
                select2Universale('nazione_destinazione', 'una nazione', 1, 'nazione_brt').on('select2:select', function (e) {
                    aggiornaPrezzoBrt();
                    if (e.params.data.id === 'CH') {
                        $('#div_svizzera').show();
                    } else {
                        $('#div_svizzera').hide();
                    }

                });
            }

            $('#button-volume_totale').click(function () {
                const numeroPacchi = $('#numero_pacchi').val();
                if (!numeroPacchi) {
                    Swal.fire({
                        text: "Inserisci il numero di pacchi",
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                    return;

                }
                var url = '{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['calcola_volumi'])}}?colli=' + $('#numero_pacchi').val();
                modalAjax(url);
            });


            $('#button-pudo_id').click(function () {
                const nazione = $('#nazione_destinazione').val();
                if (!nazione) {
                    Swal.fire({
                        text: "Inserisci nazione",
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                    return;
                }
                const localita = $('#localita_destinazione').val();
                if (!localita) {
                    Swal.fire({
                        text: "Inserisci località destinazione",
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                    return;
                }
                const cap = $('#cap_destinatario').val();
                if (!cap) {
                    Swal.fire({
                        text: "Inserisci cap destinazione",
                        icon: "warning",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                    return;
                }


                var url = '{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['pudo_lista'])}}?nazione=' + nazione + '&cap=' + cap + '&citta=' + localita;
                modalAjax(url);
            });


            function modalAjax(url) {
                var target = '#kt_modal';
                console.log($(target).length);
                // AJAX request
                $.ajax({
                    url: url,
                    success: function (response) {

                        // Add response in Modal body
                        $(target).html(response);

                        // Display Modal
                        $(target).modal('show');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        var err = eval("(" + xhr.responseText + ")");

                        alert(err.message);
                    }
                });
            }


        });
    </script>
@endpush
