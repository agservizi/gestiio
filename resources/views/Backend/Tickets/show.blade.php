@php($breadcrumbs=[action([$controller,'index'])=>'Torna a elenco '.\App\Models\Ticket::NOME_PLURALE])

@extends('Backend._layout._main')

@section('content')
    @if($record->servizio_id)
        @includeIf('Backend.Tickets.dati'.$record->classeServizio(),['record'=>$record->servizio])
    @endif
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <div class="d-flex flex-column flex-xl-row">
                <div class="flex-lg-row-fluid mb-20 mb-xl-0">
                    <div class="mb-0">
                        <h1 class="text-gray-800 fw-bold">{{$record->uidTicket()}} - {{$record->oggetto}}</h1>
                        <div class="d-flex justify-content-start align-items-center">
                            <div class=" ">
                                <span class="fw-bold text-gray-600 me-6">Creato da: <span class="text-gray-800 ">{{$record->utente->nominativo()}}</span></span>
                                <span class="fw-bold text-gray-600">Creato il: <span class="fw-bolder text-gray-800 me-1">{{$record->created_at->diffForHumans()}}</span>({{$record->created_at->format('d/m/Y H:i')}})</span>
                            </div>
                            <div class=" ms-3">
                                @php($messaggio=$record->messaggi[0])
                                @if($messaggio->user_id==Auth::id())
                                    @if($messaggio->letto)
                                        <span class="badge badge-light-success fw-bolder ">Letto</span>
                                    @else
                                        <span class="badge badge-light-primary fw-bolder ">Da leggere</span>
                                    @endif
                                @else
                                    @if(!$messaggio->letto)
                                        <span class="badge badge-light-danger fw-bolder ">Nuovo</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @foreach($record->messaggi as $messaggio)
                            @if($loop->index==0)
                                <div class="mb-15">
                                    <div class="mb-15 fs-5 fw-normal text-gray-800">
                                        {!! $messaggio->messaggio !!}
                                    </div>
                                    @if($messaggio->allegati->count())
                                        <p class="fw-normal fs-7 text-gray-700 m-0">
                                            Allegati: @foreach($messaggio->allegati as $allegato)
                                                <a href="{{action([\App\Http\Controllers\Frontend\TicketController::class,'downloadAllegato'],['messaggioId'=>$messaggio->id,'allegatoId'=>$allegato->id])}}"> {{$allegato->filename_originale}}</a>
                                            @endforeach
                                        </p>
                                    @endif
                                </div>
                            @else
                                <div class="mb-9">
                                    <div class="card card-bordered w-100">
                                        <div class="card-body">
                                            <div class="w-100 d-flex flex-stack mb-8">
                                                <div class="d-flex align-items-center f">
                                                    <div class="d-flex justify-content-start align-items-center fw-bold fs-5 text-gray-600 text-dark">
                                                        <div class="d-flex align-items-center">
                                                            <a href="#" class="text-gray-800 fw-bolder text-hover-primary fs-5 me-3">
                                                                {{$messaggio->utente->nominativo()}}
                                                            </a>
                                                            <span class="m-0"></span>
                                                        </div>
                                                        <span class="text-muted fw-bold fs-6">{{$messaggio->created_at->diffForHumans()}} ({{$messaggio->created_at->format('d/m/Y H:i')}})</span>
                                                        <div class="ms-3">
                                                            @if($messaggio->user_id==Auth::id())
                                                                @if($messaggio->letto)
                                                                    <span class="badge badge-light-success fw-bolder my-2">Letto</span>
                                                                @else
                                                                    <span class="badge badge-light-primary fw-bolder my-2">Da leggere</span>
                                                                @endif
                                                            @else
                                                                @if(!$messaggio->letto)
                                                                    <span class="badge badge-light-danger fw-bolder my-2">Nuovo</span>
                                                                @endif

                                                            @endif
                                                        </div>
                                                    </div>


                                                </div>

                                            </div>
                                            <p class="fw-normal fs-5 text-gray-700 m-0">
                                                {!! $messaggio->messaggio !!}
                                            </p>
                                            @if($messaggio->allegati->count())
                                                <p class="fw-normal fs-7 text-gray-700 m-0">
                                                    Allegati: @foreach($messaggio->allegati as $allegato)
                                                        <a href="{{action([\App\Http\Controllers\Frontend\TicketController::class,'downloadAllegato'],['messaggioId'=>$messaggio->id,'allegatoId'=>$allegato->id])}}"> {{$allegato->filename_originale}}</a>
                                                    @endforeach
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        <form id="segnala-form" class="form-horizontal" method="POST" action="{{action([$controller,'update'],$record->id)}}">
                            @csrf
                            @method('PATCH')
                            @php($uid=old('uid',\Illuminate\Support\Str::ulid()))
                            <input type="hidden" name="uid" id="uid" value="{{$uid}}">
                            <div class="mb-0">
                                @if($admin)
                                    <label class="fw-bold fs-6 required ">Stato</label>
                                    <div class="mt-5 mb-10">
                                        @php($selected=old('tipo',$record->stato))
                                        @foreach(\App\Models\Ticket::STATI_TICKETS as $key=>$value)
                                            <div class="form-check form-check-custom form-check-solid mx-5" style="display: inline;">
                                                <input class="form-check-input gestori" type="radio" value="{{$key}}"
                                                       id="tipo{{$key}}" name="stato"
                                                       required {{$selected==$key?'checked':''}}>
                                                <label class="form-check-label fw-bolder"
                                                       for="tipo{{$key}}">
                                                    <span class="badge badge-{{$value['colore']}} fw-bolder me-2">{{$value['testo']}}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <textarea class="form-control form-control-solid placeholder-gray-600 fw-bolder fs-4 ps-9 pt-7" rows="6" name="messaggio"
                                          placeholder="Testo del messaggio" {{$admin?'':'required'}} id="messaggio"></textarea>
                            </div>
                            <div class="w-100 text-center mt-6">
                                <button type="submit" class="btn btn-primary">Aggiorna {{ucfirst(\App\Models\Ticket::NOME_SINGOLARE)}}</button>
                            </div>
                        </form>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('customScript')
    <script src="/assets_backend/js-progetto/ckeditor5/build/ckeditor.js"></script>
    <script>
        $(function () {
            ClassicEditor
                .create( document.querySelector( '#messaggio' ) )
                .catch( error => {
                    console.error( error );
                } );

            var myDropzone = new Dropzone("#kt_dropzonejs_example_1", {
                url: "{{action([\App\Http\Controllers\Frontend\TicketController::class,'uploadAllegato'])}}", // Set the url for your upload script location
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
                    const esistenti =@json(\App\Models\AllegatoMessaggioTicket::perBlade($uid,null));
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
                        url: '{{ action([\App\Http\Controllers\Frontend\TicketController::class,'deleteAllegato']) }}',
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

        })
    </script>

@endpush
