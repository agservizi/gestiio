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
                    <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'create'],['duplica'=>$record->id])}}"
                       class="menu-link px-3">Duplica</a>
                </div>
                @if($record->natura_giuridica)
                    <div class="menu-item px-3">
                        <a href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['richiedi_visura',$record->id])}}"
                           data-target="kt_modal" data-toggle="modal-ajax"
                           class="menu-link px-3">Richiedi visura</a>
                    </div>
                @endif
                @if( (Auth::id()==2 && $record->agente_id!=2) || Auth::id()==1)
                    <div class="menu-item px-3">
                        <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'create'],['servizio_id'=>$record->id,'servizio_type'=>'contratto-telefonia'])}}"
                           data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                           class="menu-link px-3">Nuovo ticket</a>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id?'PATCH':'POST')
                @php($uid=old('uid',$record->uid))
                <input type="hidden" name="uid" id="uid" value="{{$uid}}">
                <input type="hidden" id="tipo_contratto_id" name="tipo_contratto_id"
                       value="{{old('tipo_contratto_id',$record->tipo_contratto_id)}}">
                <input type="hidden" id="tipo_prodotto" name="tipo_prodotto"
                       value="{{old('tipo_prodotto',$tipoProdotto)}}">
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_contratto_id','testo'=>'Tipo contratto','valore'=>\App\Models\TipoContratto::find($record->tipo_contratto_id)?->nome])
                    </div>
                    @if(Auth::user()->hasAnyPermission(['admin','operatore']))
                        <div class="col-md-6">
                            @include('Backend._inputs.inputTextDataMask',['campo'=>'data','testo'=>'Data','required'=>true])
                        </div>
                        <div class="col-md-6">
                            @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                        </div>
                    @else
                        <input type="hidden" name="data" value="{{old('data',$record->data->format('d/m/Y'))}}">
                        <input type="hidden" id="agente_id" name="agente_id"
                               value="{{old('agente_id',$record->agente_id)}}">
                    @endif
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'codice_cliente','testo'=>'Codice cliente','required'=>false,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'codice_contratto','testo'=>'Codice contratto','required'=>false,'autocomplete'=>'off'])
                    </div>
                </div>
                <h3 class="card-title">Dati generali</h3>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','required'=>true,'autocomplete'=>'off','classe'=>'uppercase'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'natura_giuridica','testo'=>'Natura giuridica','array'=>['impresa-individuale'=>'Ditta individuale','societa-capitale'=>'Società capitale','societa-persone'=>'Società di persone']])
                    </div>
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
                        @include('Backend._inputs.inputText',['campo'=>'ragione_sociale','testo'=>'Ragione sociale','required'=>false,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'partita_iva','testo'=>'Partita iva','required'=>false,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextButton',['campo'=>'iban','testo'=>'Iban','testoButton'=>'Genera','classeButton'=>'btn-light-primary'])
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-md-4 text-end">
                                <label class=" align-items-center fw-bold fs-6 col-form-label mb-2">
                                    <span>Carta di credito</span>
                                </label>

                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        @include('Backend._inputs.inputTextV',['campo'=>'carta_di_credito','testo'=>'Carta di credito','mask'=>'9999 9999 9999 9999','placeholder'=>'Numero carta'])
                                    </div>
                                    <div class="col-md-3">
                                        @include('Backend._inputs.inputTextV',['campo'=>'carta_di_credito_scadenza','testo'=>'Scadenza','mask'=>'99/99','placeholder'=>'Data scadenza'])
                                    </div>
                                    <div class="col-md-3">
                                        @include('Backend._inputs.inputTextV',['campo'=>'carta_di_credito_cvc','testo'=>'Cvc','mask'=>'999','placeholder'=>'CVC'])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'email','testo'=>'Email','autocomplete'=>'off','required'=>true])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'telefono','testo'=>'Telefono','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <h3 class="card-title">Indirizzo</h3>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'indirizzo','testo'=>'Indirizzo','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'civico','testo'=>'Civico','autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2',['campo'=>'citta','testo'=>'Citta','required'=>true,'selected'=>\App\Models\Comune::selected(old('citta',$record->citta))])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'cap','testo'=>'Cap','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'nome_citofono','testo'=>'Nome citofono','autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'scala','testo'=>'Scala','autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'piano','testo'=>'Piano','autocomplete'=>'off'])
                    </div>
                </div>
                @include('Backend.ContrattoTelefonia.dati-documento')
                @if($recordProdotto)
                    @include('Backend.ContrattoTelefonia.Prodotti.'.$tipoProdotto.'Edit',['record'=>$recordProdotto])
                @endif
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextAreaCol',['campo'=>'note','testo'=>'Note','col'=>2])
                    </div>
                    <div class="col-md-6">
                        <div class="row mb-6">
                            <div class="col-lg-2 col-form-label text-lg-end">
                                <label class="fw-bold fs-6">Allegati</label>
                            </div>
                            <div class="col-lg-10 fv-row fv-plugins-icon-container">
                                <div class="fv-row">
                                    <div class="dropzone" id="kt_dropzonejs_example_1">
                                        <div class="dz-message needsclick">
                                            <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>

                                            <div class="ms-4">
                                                <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Trascina il file qui o
                                                    clicca per selezionare i files</h3>
                                                <span class="fs-7 fw-bold text-gray-400">
                                            <span>Qui puoi allegare i documenti relativi al contratto</span>
                                        </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        @if($creaContratto)
                            <button class="btn btn-primary mt-3" type="submit"
                                    id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\ContrattoTelefonia::NOME_SINGOLARE}}</button>
                        @endif
                        @if(!$vecchio || $record->esito_id=='bozza')
                            <button class="btn btn-warning mt-3" type="submit" id="submit-bozza" name="bozza"
                                    value="bozza">{{$vecchio?'Salva bozza':'Crea bozza'}}</button>
                        @endif
                    </div>
                    @if($vecchio)
                        <div class="col-md-4 text-end">
                            @if($eliminabile===true)
                                <a class="btn btn-danger mt-3" id="elimina"
                                   href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                            @elseif(is_string($eliminabile))
                                <span data-bs-toggle="tooltip" title="{{$eliminabile}}">
                                    <a class="btn btn-danger mt-3 disabled" href="javascript:void(0)">Elimina</a>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

            </form>
        </div>
    </div>
@endsection

@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script>
        function mostraNascondiPermesso(val) {
            if (val == 'IT' || !val) {
                $('#div_permesso_soggiorno').hide();
            } else {
                $('#div_permesso_soggiorno').show();
            }
        }

        $(function () {

            if ($('#agente_id').is('select')) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }

            eliminaHandler('Questa voce verrà eliminata definitivamente');

            mostraNascondiPermesso($('#cittadinanza').val());

            var myDropzone = new Dropzone("#kt_dropzonejs_example_1", {
                url: "{{action([$controller,'uploadAllegato'])}}", // Set the url for your upload script location
                paramName: "file", // The name that will be used to transfer the file
                maxFiles: 10,
                maxFilesize: 20, // MB
                addRemoveLinks: true,
                //acceptedFiles: "image/*",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                init: function () {
                    thisDropzone = this;
                    this.on("sending", function (file, xhr, formData) {
                        formData.append("uid", $('#uid').val());
                        formData.append("contratto_id", {{$record->id??-1}});
                        console.log(formData)
                    });
                    const esistenti =@json(\App\Models\AllegatoContratto::perBlade($uid,$record->id));
                    if (esistenti) {
                        $.each(esistenti, function (key, value) {

                            var mockFile = {
                                name: value.path_filename,
                                size: value.dimensione_file,
                                filename: value.path_filename,
                                id: value.id
                            };

                            thisDropzone.emit('addedfile', mockFile);
                            if (value.thumbnail) {
                                thisDropzone.emit('thumbnail', mockFile, "/storage/" + value.thumbnail);

                            }
                            thisDropzone.emit('complete', mockFile);


                        });
                    }

                },
                accept: function (file, done) {
                    if (file.name == "q") {
                        done("Naha, you don't.");
                    } else {
                        done();
                    }
                },
                success: function (file, response) {
                    file.filename = response.filename;
                    file.id = response.id;
                    if (response.thumbnail) {
                        file.previewElement.querySelector("img").src = response.thumbnail;
                    }
                },
                removedfile: function (file) {
                    console.dir(file);
                    var name = file.filename;
                    console.log(name);
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                        },
                        type: 'DELETE',
                        url: '{{ action([$controller,'deleteAllegato']) }}',
                        data: {
                            id: file.id
                        },
                        success: function (data) {
                            console.log("File has been successfully removed!!");
                        },
                        error: function (e) {
                            console.log(e);
                        }
                    });
                    var fileRef;
                    return (fileRef = file.previewElement) != null ?
                        fileRef.parentNode.removeChild(file.previewElement) : void 0;
                },
            });


            $('#button-iban').click(function () {
                var url = '{{action([\App\Http\Controllers\Backend\AjaxController::class,'post'],'genera-iban')}}';
                $.ajax({
                    url: url,
                    type: 'post',
                    dataType: 'json',
                    method: 'POST',
                    success: function (resp) {
                        if (resp.success) {
                            $('#iban').val(resp.iban);

                        }

                    }
                });

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
            $('#email').blur(function (e) {
                var email = $(this).val();
                if (email === "") {
                    return;
                }
                var url = '{{action([\App\Http\Controllers\Backend\AjaxController::class,'post'],'controlla-email-telefonia')}}';
                $.ajax({
                    url: url,
                    type: 'post',
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        email: email,
                        tipo_contratto_id: $('#tipo_contratto_id').val()
                    },
                    success: function (resp) {
                        if (resp.success) {
                            Swal.fire(
                                "Email esistente",
                                resp.message,
                                "info"
                            )
                        }
                    }
                });
            });

            $('#telefono').blur(function (e) {
                var telefono = $(this).val();
                if (telefono === "") {
                    return;
                }
                var url = '{{action([\App\Http\Controllers\Backend\AjaxController::class,'post'],'controlla-telefono-telefonia')}}';
                $.ajax({
                    url: url,
                    type: 'post',
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        telefono: telefono,
                        tipo_contratto_id: $('#tipo_contratto_id').val()
                    },
                    success: function (resp) {
                        if (resp.success) {
                            Swal.fire(
                                "Telefono esistente",
                                resp.message,
                                "info"
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

        });
        select2Universale('comune_rilascio', 'un comune', 3, 'citta');
        select2Universale('cittadinanza', 'la cittadinanza', 3, 'nazione').on('select2:select', function (e) {
            // Access to full data
            mostraNascondiPermesso(e.params.data.id);
        });


    </script>
@endpush
