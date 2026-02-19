@php($breadcrumbs=[action([$controller,'index'])=>'Torna a elenco '.\App\Models\Ticket::NOME_PLURALE])

@extends('Backend._layout._main')
@section('content')
    @if($record->servizio_id)
        @includeIf('Backend.Tickets.dati'.$record->classeServizio(),['record'=>$record->servizio])
    @endif
    <div class="card">
        <div class="card-body">
            @include('Backend._components.alertErrori')
            <div class="alert alert-success " id="problema-alert" style="display: none;">
                <strong>Fatto: </strong> la tua segnalazione è stata inviata.
            </div>
            <div class="alert alert-info " id="problema-incorso" style="display: none;">
                <strong>Attendi: </strong> invio segnalazione in corso
            </div>
            <div id="problema-form">
                <form id="segnala-form" class="form-horizontal" method="POST"
                      action="{{action([$controller,'store'])}}">
                    @csrf
                    @php($uid=old('uid',\Illuminate\Support\Str::ulid()))
                    <input type="hidden" name="uid" id="uid" value="{{$uid}}">
                    @include('Backend._inputs.inputHidden',['campo'=>'servizio_id'])
                    @include('Backend._inputs.inputHidden',['campo'=>'servizio_type'])
                    @if($admin)
                        <label class="fw-bold fs-6 required pt-2">Destinatario</label>
                        <div class="mt-5 mb-3">
                            @php($destinatarioTipo=old('destinatario_tipo','agente'))
                            <div class="form-check form-check-custom form-check-solid mx-5" style="display: inline;">
                                <input class="form-check-input destinatario-tipo" type="radio" value="agente"
                                       id="destinatarioTipoAgente" name="destinatario_tipo"
                                       {{$destinatarioTipo==='agente'?'checked':''}}>
                                <label class="form-check-label fw-bolder" for="destinatarioTipoAgente">Agente</label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid mx-5" style="display: inline;">
                                <input class="form-check-input destinatario-tipo" type="radio" value="supervisore"
                                       id="destinatarioTipoSupervisore" name="destinatario_tipo"
                                       {{$destinatarioTipo==='supervisore'?'checked':''}}>
                                <label class="form-check-label fw-bolder" for="destinatarioTipoSupervisore">Supervisore</label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid mx-5" style="display: inline;">
                                <input class="form-check-input destinatario-tipo" type="radio" value="operatore"
                                       id="destinatarioTipoOperatore" name="destinatario_tipo"
                                       {{$destinatarioTipo==='operatore'?'checked':''}}>
                                <label class="form-check-label fw-bolder" for="destinatarioTipoOperatore">Operatore</label>
                            </div>
                        </div>

                        <div id="destinatari-options" class="mb-8">
                            <select class="form-select form-select-solid" name="destinatario_id" id="destinatario_id">
                                <option value="">Seleziona destinatario</option>
                                @foreach($agentiDestinatari as $agenteDestinatario)
                                    <option value="{{$agenteDestinatario->id}}" data-tipo="agente" {{(string)old('destinatario_id')===(string)$agenteDestinatario->id?'selected':''}}>
                                        {{$agenteDestinatario->cognome}} {{$agenteDestinatario->nome}}
                                    </option>
                                @endforeach
                                @foreach($supervisoriDestinatari as $supervisoreDestinatario)
                                    <option value="{{$supervisoreDestinatario->id}}" data-tipo="supervisore" {{(string)old('destinatario_id')===(string)$supervisoreDestinatario->id?'selected':''}}>
                                        {{$supervisoreDestinatario->cognome}} {{$supervisoreDestinatario->nome}}
                                    </option>
                                @endforeach
                                @foreach($operatoriDestinatari as $operatoreDestinatario)
                                    <option value="{{$operatoreDestinatario->id}}" data-tipo="operatore" {{(string)old('destinatario_id')===(string)$operatoreDestinatario->id?'selected':''}}>
                                        {{$operatoreDestinatario->cognome}} {{$operatoreDestinatario->nome}}
                                    </option>
                                @endforeach
                            </select>
                            <div id="destinatario-empty-alert" class="alert alert-warning mt-3 mb-0" style="display:none;">
                                Nessun destinatario disponibile per il tipo selezionato.
                            </div>
                        </div>
                    @endif
                    @include('Backend._inputs_v.inputText',['campo'=>'oggetto','testo'=>'Oggetto','required'=>true,'autocomplete'=>'off'])
                    @if($record->servizio_type)
                        <label class="fw-bold fs-6 required pt-2">Causale</label>
                        <div class="mt-5 mb-10">
                            @php($selected=old('causale_ticket_id',$record->causale_ticket_id))
                            @foreach(\App\Models\CausaleTicket::where('servizio_type',$record->servizio_type)->get() as $causale)
                                <div class="form-check form-check-custom form-check-solid mx-5" style="display: inline;">
                                    <input class="form-check-input gestori" type="radio" value="{{$causale->id}}"
                                           id="tipo{{$causale->id}}" name="causale_ticket_id"
                                           required {{$selected==$causale->id?'checked':''}}>
                                    <label class="form-check-label fw-bolder"
                                           for="tipo{{$causale->id}}">{{$causale->descrizione_causale}}</label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @include('Backend._inputs.inputTextArea',['campo'=>'messaggio','testo'=>'Messaggio','required'=>true,'autocomplete'=>'off'])
                    <div class="fv-row mt-2">
                        <div class="dropzone" id="kt_dropzonejs_example_1">
                            <div class="dz-message needsclick">
                                <i class="bi bi-file-earmark-arrow-up text-primary fs-3x"></i>

                                <div class="ms-4">
                                    <h3 class="fs-5 fw-bolder text-gray-900 mb-1">Trascina il file qui o clicca per
                                        selezionare i files</h3>
                                    <span class="fs-7 fw-bold text-gray-400">
                                            <span>Qui puoi allegare i documenti relativi al ticket</span>

                                        </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-100 text-center mt-4">
                        <input type="submit" value="Invia segnalazione" class="btn btn-primary" id="invia-ticket-btn">
                    </div>
                    <!-- /.col-md-4 -->
                </form>

            </div>
        </div>
    </div>
@endsection
@push('customScript')
    <script>
        $(function () {

            const filtraDestinatari = function () {
                const tipo = $('input[name="destinatario_tipo"]:checked').val();
                const select = $('#destinatario_id');
                if (!select.length) {
                    return;
                }

                const submitButton = $('#invia-ticket-btn');
                const emptyAlert = $('#destinatario-empty-alert');

                let selectedVisibile = false;
                let opzioniVisibili = 0;
                select.find('option').each(function () {
                    const optionTipo = $(this).data('tipo');
                    if (!optionTipo) {
                        $(this).prop('hidden', false);
                        return;
                    }

                    const visibile = optionTipo === tipo;
                    $(this).prop('hidden', !visibile);
                    if (visibile) {
                        opzioniVisibili++;
                    }
                    if (visibile && $(this).is(':selected')) {
                        selectedVisibile = true;
                    }
                });

                if (!selectedVisibile) {
                    select.val('');
                }

                const senzaDestinatari = opzioniVisibili === 0;
                emptyAlert.toggle(senzaDestinatari);
                submitButton.prop('disabled', senzaDestinatari);
            };

            filtraDestinatari();
            $(document).on('change', 'input[name="destinatario_tipo"]', filtraDestinatari);

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

                            var mockFile = {
                                name: value.path_filename,
                                size: value.dimensione_file,
                                filename: value.path_filename,
                                id: value.id
                            };

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

        });
    </script>
@endpush
