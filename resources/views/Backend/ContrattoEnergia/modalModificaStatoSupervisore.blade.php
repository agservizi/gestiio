@extends('Backend._components.modal')
@section('content')
    <form id="form-aggiorna-stato" action="{{action([$controller,'aggiornaStato'],$record->id)}}" method="POST">
        <input type="hidden" name="aggiorna" value="{{old('aggiorna',request()->input('aggiorna'))}}">
        <input type="hidden" name="ruolo" value="supervisore">
        <div class="fv-row">
            <label class="d-flex align-items-center fs-5 fw-bold mb-4">
                <span class="required">Stato</span>
            </label>
            <div class="row">
                @php($selected=$record->esito_id)
                @foreach($stati as $stato)
                    <div class="col-6">
                        <label class="d-flex flex-stack mb-1 cursor-pointer rounded-2" style="padding: 10px; background-color: {{$stato->colore_hex}}; color: white;">
                            <span class="d-flex align-items-center me-2">
                                <span class="d-flex flex-column ">
                                    <span class="fw-bolder fs-6 " id="testo_{{$stato->id}}">{{$stato->nome}}</span>
                                </span>
                            </span>
                            <span class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input stato" type="radio" name="esito_id" value="{{$stato->id}}" {{$selected==$stato->id?'checked':''}}
                                data-motivo="{{$stato->chiedi_motivo}}"
                                >
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        <div id="div_motivazione" class="mb-2">
            @include('Backend._inputs.inputTextArea',['campo'=>'motivo_ko','testo'=>'Motivazione ko','required'=>false,'autocomplete'=>'off'])
            @if(!$record->motivo_ko)
                <div style="margin-top: -15px;">
                    @foreach(\App\Models\TabMotivoKo::perModal('contratto-energia')->get()->sortBy('nome') as $motivo)
                        <button type="button" class="motivo_ko" style="font-size: 10px;">{{$motivo->nome}}</button>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="w-100 mt-6">
            @include('Backend._inputs.inputText',['campo'=>'codice_contratto','testo'=>'Codice contratto','required'=>false,'autocomplete'=>'off'])
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <button class="btn btn-primary mt-3" type="submit">Aggiorna dati</button>
            </div>
        </div>
    </form>
    <div class="row mt-6">
        <div class="col-lg-2 col-form-label text-lg-end">
            <label class="fw-bold fs-6">Allegati</label>
        </div>
        <div class="col-lg-10 fv-row fv-plugins-icon-container">
            <div class="fv-row">
                <div class="dropzone" id="kt_dropzonejs_example_1">
                    <div class="dz-message needsclick">
                        <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>

                        <div class="ms-4">
                            <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Trascina il file qui o clicca per selezionare i files</h3>
                            <span class="fs-7 fw-bold text-gray-400">
                                            <span>Qui puoi allegare i documenti relativi al contratto</span>
                                        </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('customScript')

    <script>
        $(function () {

            impostaDivMotivazione($('.stato:checked'));
            $('.stato').click(function () {
                impostaDivMotivazione($(this));
            });

            $('.motivo_ko').off();
            $('.motivo_ko').click(function () {
                $('#motivo_ko').val($(this).text());
                if ($('#motivo_ko').val() === '') {

                }
            });

            function impostaDivMotivazione(stato) {
                if (stato.data('motivo') == 1) {
                    $('#div_motivazione').slideDown();
                } else {
                    $('#div_motivazione').slideUp();
                }
            }

            $('#form-aggiorna-stato').off().submit(function (e) {
                e.preventDefault();
                var url = $(this).attr('action');
                console.log(url);
                var data = $(this).serialize();
                console.log(data);
                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: data,
                    success: function (response) {
                        if (response.success) {
                            $('#tr_' + response.id).replaceWith(base64_decode(response.html));
                            $('#kt_modal').modal('hide');
                            modalAjax();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
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
                        formData.append("contratto_id", {{$record->id}});
                    });
                    const esistenti =@json(\App\Models\AllegatoContratto::perBlade($uid,$record->id));
                    if (esistenti) {
                        $.each(esistenti, function (key, value) {

                            var mockFile = {name: value.path_filename, size: value.dimensione_file, filename: value.path_filename, id: value.id};

                            thisDropzone.emit('addedfile', mockFile);
                            if (value.tipo_file === 'immagine') {
                                thisDropzone.emit('thumbnail', mockFile, "/storage/" + value.path_filename);

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
                    console.dir(file);
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


        });
    </script>
@endpush
