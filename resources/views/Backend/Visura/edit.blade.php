@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    @php($allegatoServizioType=get_class($record))
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
                @csrf
                @method($record->id?'PATCH':'POST')
                @php($uid=old('uid',$record->uid))

                <input type="hidden" name="uid" id="uid" value="{{$uid}}">
                <input type="hidden" name="tipo_visura_id" value="{{old('tipo_visura_id',$tipoServizio->id)}}">
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

                @includeWhen($tipoServizio->tipo_visura=='azienda','Backend.Visura.azienda')
                @includeWhen($tipoServizio->tipo_visura=='privato','Backend.Visura.privato')
                <div class="row">
                    <div class="col-md-12">
                        @include('Backend._inputs.inputTextAreaCol',['campo'=>'note','testo'=>'Note','col'=>2])
                    </div>
                </div>
                @if($tipoServizio->html)
                    {!! $tipoServizio->html !!}
                @endif

                @if($tipoServizio->richiedi_allegati)
                    @include('Backend._inputs_.allegati')
                @endif
                <div class="row">
                    <div class="col-md-4 offset-md-4 text-center">
                        <button class="btn btn-primary mt-3" type="submit"
                                id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\Visura::NOME_SINGOLARE}}</button>
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
            if ($('#agente_id').is("select")) {
                select2UniversaleBackend('agente_id', 'un agente', 1);
            }

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

        });
    </script>
@endpush
