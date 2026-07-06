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
                            @php($destinatarioTipo=old('destinatario_tipo',$defaultDestinatarioTipo ?? 'agente'))
                            @php($destinatarioIdSelezionato=(string)old('destinatario_id', $defaultDestinatarioId ?? ''))
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
                                    <option value="{{$agenteDestinatario->id}}" data-tipo="agente" {{$destinatarioIdSelezionato===(string)$agenteDestinatario->id?'selected':''}}>
                                        {{$agenteDestinatario->cognome}} {{$agenteDestinatario->nome}}
                                    </option>
                                @endforeach
                                @foreach($supervisoriDestinatari as $supervisoreDestinatario)
                                    <option value="{{$supervisoreDestinatario->id}}" data-tipo="supervisore" {{$destinatarioIdSelezionato===(string)$supervisoreDestinatario->id?'selected':''}}>
                                        {{$supervisoreDestinatario->cognome}} {{$supervisoreDestinatario->nome}}
                                    </option>
                                @endforeach
                                @foreach($operatoriDestinatari as $operatoreDestinatario)
                                    <option value="{{$operatoreDestinatario->id}}" data-tipo="operatore" {{$destinatarioIdSelezionato===(string)$operatoreDestinatario->id?'selected':''}}>
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
                    <div class="row">
                        <div class="col-md-6">
                            <label class="fw-bold fs-6 pt-2">Priorità</label>
                            <select class="form-select form-select-solid mt-2 mb-5" name="priorita">
                                <option value="">Automatica</option>
                                @foreach(\App\Models\Ticket::PRIORITA_TICKETS as $key => $value)
                                    <option value="{{$key}}" {{old('priorita')===$key?'selected':''}}>{{$value['testo']}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold fs-6 pt-2">Team</label>
                            <select class="form-select form-select-solid mt-2 mb-5" name="owner_team">
                                <option value="">Automatico</option>
                                @foreach(\App\Models\Ticket::TEAM_TICKETS as $key => $value)
                                    <option value="{{$key}}" {{old('owner_team')===$key?'selected':''}}>{{$value}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-light-info border border-info border-dashed">
                        Il sistema applicherà automaticamente priorità, team e scadenze SLA se lasci i campi su automatico.
                    </div>
                    @include('Backend._inputs.inputTextArea',['campo'=>'messaggio','testo'=>'Messaggio','required'=>true,'autocomplete'=>'off'])
                    <div class="fv-row mt-2">
                        <div class="dropzone gestiio-dropzone" id="kt_dropzonejs_example_1">
                            <div class="dz-message needsclick">
                                <span class="gestiio-dropzone-icon">
                                    <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3>Trascina i file qui o clicca per selezionarli</h3>
                                    <span>Allega documenti, riscontri o screenshot utili al ticket.</span>
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
@include('Backend._components.dropzoneUx')
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

            initGestiioDropzone("#kt_dropzonejs_example_1", {
                uploadUrl: "{{action([\App\Http\Controllers\Frontend\TicketController::class,'uploadAllegato'])}}",
                deleteUrl: "{{ action([\App\Http\Controllers\Frontend\TicketController::class,'deleteAllegato']) }}",
                csrfToken: "{{ csrf_token() }}",
                maxFiles: 10,
                maxFilesize: 20,
                existingFiles: @json(\App\Models\AllegatoMessaggioTicket::perBlade($uid,null)),
                sendingData: {
                    uid: function () {
                        return $('#uid').val();
                    }
                },
            });

        });
    </script>
@endpush
