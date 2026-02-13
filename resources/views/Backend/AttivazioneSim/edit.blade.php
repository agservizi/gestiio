@extends('Backend._layout._main')
@section('toolbar')
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

                <input type="hidden" id="gestore_id" name="gestore_id"
                       value="{{old('gestore_id',$record->gestore_id)}}">

                @if(Auth::user()->hasAnyPermission(['admin','operatore']))
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


                <h3 class="card-title">Dati cliente</h3>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','required'=>true,'autocomplete'=>'off','classe'=>'uppercase'])
                    </div>
                    <div class="col-md-6">
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
                        @include('Backend._inputs.inputText',['campo'=>'email','testo'=>'Email','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'cellulare','testo'=>'Cellulare','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'indirizzo','testo'=>'Indirizzo','autocomplete'=>'off'])
                    </div>
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
                <h3 class="card-title">Sim</h3>

                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputSelect2',['campo'=>'offerta_sim_id','testo'=>'Offerta','required'=>true,'selected'=>\App\Models\OffertaSim::selected(old('offerta_sim_id',$record->offerta_sim_id))])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'codice_sim','testo'=>'Seriale sim nuova','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        <div class="row mb-6">
                            <div class="col-lg-4 col-form-label text-lg-end">
                                <label class="form-check-label fs-6 fw-bold" for="portabilita">
                                    Portabilità
                                </label>
                            </div>
                            <div class="col-lg-8 fv-row fv-plugins-icon-container">
                                <div class="form-check form-switch form-check-custom form-check-solid mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="portabilita"
                                           name="portabilita" {{old('mnp',$record->mnp)?'checked':''}}/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="dati" class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'mnp','testo'=>'Seriale sim vecchia','required'=>false,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'numero_da_portare','testo'=>'Numero da portare','required'=>false,'autocomplete'=>'off'])
                    </div>
                </div>
                @includeIf('Backend.AttivazioneSim.GestoriEdit.'.$nomeGestore)
                <h3 class="card-title">Dati documento</h3>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputRadioH',['campo'=>'tipo_documento','testo'=>'Tipo documento','required'=>true,'array'=>\App\Models\ContrattoTelefonia::TIPI_DOCUMENTO ])
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        @include('Backend._inputs.inputText',['campo'=>'numero_documento','testo'=>'Numero documento','required'=>true,'autocomplete'=>'off'])
                    </div>
                    <div class="col-md-6">
                        @include('Backend._inputs.inputTextDataMask',['campo'=>'data_scadenza','testo'=>'Data scadenza','required'=>true,'autocomplete'=>'off'])
                    </div>
                </div>
                <div class="row">
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
                        <button class="btn btn-primary mt-3" type="submit"
                                id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\AttivazioneSim::NOME_SINGOLARE}}</button>
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
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
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
                        formData.append("attivazioni_sim_id", {{$record->id??-1}});
                        console.log(formData)
                    });
                    const esistenti =@json(\App\Models\AllegatoAttivazioneSim::perBlade($uid,$record->id));
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

            $('#offerta_sim_id').select2({
                placeholder: 'Seleziona un offerta',
                minimumInputLength: -1,
                allowClear: true,
                width: '100%',
                // dropdownParent: $('#modalPosizione'),
                ajax: {
                    quietMillis: 150,
                    url: function () {
                        return "/backend/select2?offerta_sim_id&gestore_id=" + $('#gestore_id').val();
                    },
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


            mostraNascondi($('#mnt').val());
            $('#portabilita').change(function () {
                mostraNascondi($(this).is(':checked'));
            });

            function mostraNascondi(mostra) {

                $('#dati').toggle(mostra);
            }


        });
    </script>
@endpush
