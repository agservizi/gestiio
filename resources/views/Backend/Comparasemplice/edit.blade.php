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
                @if(Auth::user()->hasPermissionTo('admin'))
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
                <div class="row">
                    <div class="col-md-12">
                        @include('Backend._inputs.inputCheckboxH',['campo'=>'tipo_segnalazione','testo'=>'Servizi','array'=>\App\Models\Comparasemplice::SERVIZI,'col' => 2])
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
                                        <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Trascina il file qui o clicca per
                                            selezionare i files</h3>
                                        <span class="fs-7 fw-bold text-gray-400">
                                            <span>Qui puoi allegare i documenti relativi al contratto</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit"
                                id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\Comparasemplice::NOME_SINGOLARE}}</button>
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
    <script src="/assets_backend/js-miei/autoNumeric.js"></script>


    <script>
        $(function () {
            autonumericImporto('importo');

            eliminaHandler('Questa voce verrà eliminata definitivamente');

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
                                $('#email').val(resp.cliente.email);
                                $('#cellulare').val(resp.cliente.telefono);

                                Swal.fire(
                                    "Cliente trovato",
                                    'I dati cliente sono stati inseriti',
                                    "info"
                                )

                                if (resp.dati_ritorno) {
                                    $('#data_di_nascita').val(resp.dati_ritorno.data_di_nascita);
                                }
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
            if ($('#kt_dropzonejs_example_1').length) {
                var myDropzone = new Dropzone("#kt_dropzonejs_example_1", {
                    url: "{{action([\App\Http\Controllers\Backend\AllegatoServizioController::class,'uploadAllegato'])}}", // Set the url for your upload script location
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
                            formData.append("allegato_id", {{$record->id??'0'}});
                            formData.append("allegato_type", '{{str_replace('\\','_',$allegatoServizioType)}}');

                        });
                        const esistenti =@json(\App\Models\AllegatoServizio::perBlade($uid,$record->id,$allegatoServizioType));
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
                            url: '{{ action([\App\Http\Controllers\Backend\AllegatoServizioController::class,'deleteAllegato']) }}',
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

            }


        });
    </script>
@endpush
