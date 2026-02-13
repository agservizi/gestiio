@extends('Backend._components.modal',['centered'=>true])
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    @include('Backend._components.alertErrori')
    <form method="POST" action="{{action([$controller,'update'],$record->id??'')}}">
        @csrf
        @method($record->id?'PATCH':'POST')
        @php($uid=old('uid',\Illuminate\Support\Str::ulid()))
        <input type="hidden" name="uid" id="uid" value="{{$uid}}">

        <div class="row">
            <div class="col-md-12">
                @include('Backend._inputs_v.inputText',['campo'=>'oggetto','testo'=>'Oggetto','required'=>true,'autocomplete'=>'off'])
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @include('Backend._inputs_v.inputSelect2KeyValue',['campo'=>'contratto_id','testo'=>'Contratto','required'=>true,'array'=>$contratti])
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @include('Backend._inputs_v.inputSelect2KeyValue',['campo'=>'tipo','testo'=>'Tipo','required'=>true,'array'=>\App\Models\Ticket::TIPI_TICKETS])
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @include('Backend._inputs_v.inputTextArea',['campo'=>'messaggio','testo'=>'Messaggio','required'=>true,'autocomplete'=>'off'])
            </div>
        </div>
        <div class="fv-row mt-2">
            <div class="dropzone" id="kt_dropzonejs_example_1">
                <div class="dz-message needsclick">
                    <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>

                    <div class="ms-4">
                        <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Trascina il file qui o clicca per selezionare i files</h3>
                        <span class="fs-7 fw-bold text-gray-400">
                                            <span>Qui puoi allegare i documenti relativi al ticket</span>

                                        </span>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-4 offset-md-4 text-center">
                <button class="btn btn-primary mt-3" type="submit" id="submit">{{$vecchio?'Salva modifiche':'Crea '.\App\Models\Ticket::NOME_SINGOLARE}}</button>
            </div>
            @if($vecchio)
                <div class="col-md-4 text-end">
                    @if($eliminabile===true)
                        <a class="btn btn-danger mt-3" id="elimina" href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
                    @elseif(is_string($eliminabile))
                        <span data-bs-toggle="tooltip" title="{{$eliminabile}}">
                                    <a class="btn btn-danger mt-3 disabled" href="javascript:void(0)">Elimina</a>
                                </span>
                    @endif
                </div>
            @endif
        </div>
    </form>
@endsection
@push('customScript')
    <script>
        $(function () {
            formSubmitAndDelete();
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
                        console.log(formData)
                    });
                    const esistenti =@json(\App\Models\AllegatoMessaggioTicket::perBlade($uid,$record->id));
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
