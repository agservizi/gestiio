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
                        <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'create'],['contratto_id'=>$record->id])}}"
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
    @endphp
    <div class="card card-flush">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id?'PATCH':'POST')
                @php
                    $uid = old('uid', $record->uid);
                @endphp
                <input type="hidden" name="uid" id="uid" value="{{$uid}}">
                <input type="hidden" id="gestore_id" name="gestore_id"
                       value="{{old('gestore_id',$record->gestore_id)}}">
                <input type="hidden" id="tipo_prodotto" name="tipo_prodotto"
                       value="{{old('tipo_prodotto',$tipoProdotto)}}">

                <div class="mb-5">
                    <h4 class="fw-bold mb-1">Dati pratica</h4>
                    <div class="text-muted fs-7">Informazioni generali e assegnazione</div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipo_contratto_id','testo'=>'Tipo contratto','valore'=>\App\Models\GestoreContrattoEnergia::find($record->gestore_id)?->nome])
                    </div>

                </div>
                @if(Auth::user()->hasAnyPermission(['admin','operatore','supervisore']))
                    <div class="row">
                        <div class="col-md-6">
                            @include('Backend._inputs.inputSelect2',['campo'=>'agente_id','testo'=>'Agente','required'=>true,'selected'=>\App\Models\User::selected(old('agente_id',$record->agente_id))])
                        </div>
                        <div class="col-md-6">
                            @include('Backend._inputs.inputTextDataMask',['campo'=>'data','testo'=>'Data','required'=>true])
                        </div>
                    </div>
                @else
                    <input type="hidden" id="agente_id" name="agente_id"
                           value="{{old('agente_id',$record->agente_id)}}">
                    <input type="hidden" name="data" value="{{old('data',$record->data->format('d/m/Y'))}}">
                @endif

                @if($recordProdotto)
                    <div class="separator separator-dashed my-6"></div>
                    <div class="mb-5">
                        <h4 class="fw-bold mb-1">Dettagli contratto</h4>
                        <div class="text-muted fs-7">Campi specifici del gestore e della fornitura</div>
                    </div>
                    @include("Backend.ContrattoEnergia.Prodotti.{$tipoProdotto}Edit",['record'=>$recordProdotto,'codiceFiscale'=>$record->codice_fiscale,'email'=>$record->email,'telefono'=>$record->telefono,'denominazione'=>$record->denominazione])
                @endif

                <div class="separator separator-dashed my-6"></div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextAreaCol',['campo'=>'note','testo'=>'Note interne','col'=>2])
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


                <div class="separator separator-dashed my-6"></div>
                <div class="row align-items-center">
                    <div class="col-md-8 text-center text-md-start mb-3 mb-md-0">
                        @if($creaContratto)
                            <button class="btn btn-primary" type="submit"
                                    id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\ContrattoTelefonia::NOME_SINGOLARE}}</button>
                        @endif
                        @if(!$vecchio || $record->esito_id=='bozza')
                            <button class="btn btn-light-warning" type="submit" id="submit-bozza" name="bozza"
                                    value="bozza">{{$vecchio?'Salva bozza':'Crea bozza'}}</button>
                        @endif
                    </div>
                    @if($vecchio)
                        <div class="col-md-4 text-center text-md-end">
                            @if($eliminabile===true)
                                <a class="btn btn-light-danger" id="elimina"
                                   href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                            @elseif(is_string($eliminabile))
                                <span data-bs-toggle="tooltip" title="{{$eliminabile}}">
                                    <a class="btn btn-light-danger disabled" href="javascript:void(0)">Elimina</a>
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


        $(function () {

            eliminaHandler('Questa voce verrà eliminata definitivamente');
            if ($('#agente_id').is("select")) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }

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
                        formData.append("contratto_energia_id", {{$record->id??-1}});
                        console.log(formData)
                    });
                    const esistenti =@json(\App\Models\AllegatoContrattoEnergia::perBlade($uid,$record->id));
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

        });
        select2Universale('comune_rilascio', 'un comune', 3, 'citta');


    </script>
@endpush
