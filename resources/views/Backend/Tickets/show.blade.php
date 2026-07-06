@php($breadcrumbs=[action([$controller,'index'])=>'Torna a elenco '.\App\Models\Ticket::NOME_PLURALE])

@extends('Backend._layout._main')

@section('content')
    @if($record->servizio_id)
        @includeIf('Backend.Tickets.dati'.$record->classeServizio(),['record'=>$record->servizio])
    @endif

    @php($sla = $record->slaStatus())

    <div class="row g-6">
        <div class="col-xl-8">
            <div class="card border-0 mb-6" style="background: linear-gradient(135deg, #111827 0%, #1d4ed8 56%, #60a5fa 100%);">
                <div class="card-body p-8 text-white">
                    <div class="d-flex flex-wrap justify-content-between gap-4">
                        <div>
                            <div class="text-uppercase opacity-75 fw-bold fs-8 mb-2">Ticket {{$record->uidTicket()}}</div>
                            <h1 class="fw-bolder text-white mb-3">{{$record->oggetto}}</h1>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge badge-light">Creato da {{$record->utente?->nominativo() ?? 'Utente non disponibile'}}</span>
                                <span class="badge badge-light">Assegnato a {{$record->assegnatario?->nominativo() ?? 'Non assegnato'}}</span>
                                <span class="badge badge-light">{{$record->created_at->format('d/m/Y H:i')}}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="mb-2">{!! $record->labelStatoTicket() !!}</div>
                            <div class="mb-2">{!! $record->labelPrioritaTicket() !!}</div>
                            <span class="badge badge-light-{{$sla['classe']}}">{{$sla['testo']}}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 mb-6">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Conversazione</h3>
                </div>
                <div class="card-body pt-0">
                    @foreach($record->messaggi as $messaggio)
                        <div class="rounded p-5 mb-5 {{(int)$messaggio->user_id === (int)Auth::id() ? 'bg-light-primary' : 'bg-light'}} border border-gray-200">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                <div>
                                    <div class="fw-bold">{{$messaggio->utente?->nominativo() ?? 'Utente non disponibile'}}</div>
                                    <div class="text-muted fs-8">{{$messaggio->created_at->format('d/m/Y H:i')}} ({{$messaggio->created_at->diffForHumans()}})</div>
                                </div>
                                <div>
                                    @if((int)$messaggio->user_id === (int)Auth::id())
                                        <span class="badge badge-light-{{$messaggio->letto ? 'success' : 'primary'}}">{{$messaggio->letto ? 'Letto' : 'Da leggere'}}</span>
                                    @elseif(!$messaggio->letto)
                                        <span class="badge badge-light-danger">Nuovo</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-gray-800 fs-6">{!! \Illuminate\Support\Str::of($messaggio->messaggio)->stripTags('<p><br><b><i><u><strong><em><ul><ol><li><a><span><div><h1><h2><h3><h4><h5><h6>') !!}</div>
                            @if($messaggio->allegati->count())
                                <div class="mt-4 pt-4 border-top border-gray-200">
                                    <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Allegati</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($messaggio->allegati as $allegato)
                                            <a class="btn btn-sm btn-light" href="{{action([\App\Http\Controllers\Frontend\TicketController::class,'downloadAllegato'],['messaggioId'=>$messaggio->id,'allegatoId'=>$allegato->id])}}">{{$allegato->filename_originale}}</a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card border-0">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Aggiorna Ticket</h3>
                </div>
                <div class="card-body pt-0">
                    @include('Backend._components.alertErrori')
                    <form id="segnala-form" method="POST" action="{{action([$controller,'update'],$record->id)}}">
                        @csrf
                        @method('PATCH')
                        @php($uid=old('uid',\Illuminate\Support\Str::ulid()))
                        <input type="hidden" name="uid" id="uid" value="{{$uid}}">

                        @if($canGestireAssegnazione)
                            <div class="row g-5 mb-6">
                                <div class="col-md-6">
                                    <label class="fw-bold fs-6 mb-2">Assegnatario</label>
                                    <select class="form-select form-select-solid" name="agente_id" id="agente_id">
                                        <option value="">Seleziona assegnatario</option>
                                        @foreach($assegnatariDestinatari as $assegnatarioDestinatario)
                                            <option value="{{$assegnatarioDestinatario->id}}" {{(int)$record->agente_id === (int)$assegnatarioDestinatario->id ? 'selected' : ''}}>{{$assegnatarioDestinatario->cognome}} {{$assegnatarioDestinatario->nome}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="fw-bold fs-6 mb-2">Priorità</label>
                                    <select class="form-select form-select-solid" name="priorita">
                                        <option value="">Mantieni</option>
                                        @foreach(\App\Models\Ticket::PRIORITA_TICKETS as $key=>$value)
                                            <option value="{{$key}}" {{$record->priorita === $key ? 'selected' : ''}}>{{$value['testo']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="fw-bold fs-6 mb-2">Team</label>
                                    <select class="form-select form-select-solid" name="owner_team">
                                        <option value="">Mantieni</option>
                                        @foreach(\App\Models\Ticket::TEAM_TICKETS as $key=>$value)
                                            <option value="{{$key}}" {{$record->owner_team === $key ? 'selected' : ''}}>{{$value}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        @if($canGestireStato)
                            <div class="mb-6">
                                <label class="fw-bold fs-6 mb-2">Stato</label>
                                <div class="d-flex flex-wrap gap-3 mt-2">
                                    @php($selected=old('stato',$record->stato))
                                    @foreach(\App\Models\Ticket::STATI_TICKETS as $key=>$value)
                                        <label class="form-check form-check-custom form-check-solid px-3 py-2 rounded bg-light">
                                            <input class="form-check-input" type="radio" value="{{$key}}" name="stato" {{$selected===$key?'checked':''}}>
                                            <span class="form-check-label ms-2"><span class="badge badge-{{$value['colore']}}">{{$value['testo']}}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mb-6">
                            <textarea class="form-control form-control-solid" rows="6" name="messaggio" id="messaggio" placeholder="Scrivi un aggiornamento operativo o una risposta al cliente" {{$canGestireAssegnazione?'':'required'}}></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Salva aggiornamento</button>
                        </div>
                    </form>

                    <div class="fv-row mt-8">
                        <div class="dropzone gestiio-dropzone" id="kt_dropzonejs_example_1">
                            <div class="dz-message needsclick">
                                <span class="gestiio-dropzone-icon">
                                    <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3>Trascina i file qui o clicca per allegarli</h3>
                                    <span>Documenti, riscontri o screenshot utili alla risoluzione.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 mb-6">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Cockpit Helpdesk</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="rounded p-5 mb-4" style="background:#f8fafc;">
                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">SLA</div>
                        <div class="fw-bold mb-2">{{$record->slaSummary()}}</div>
                        <span class="badge badge-light-{{$sla['classe']}}">{{$sla['testo']}}</span>
                    </div>
                    <div class="rounded p-5 mb-4" style="background:#f8fafc;">
                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Team owner</div>
                        <div class="fw-bold">{{\App\Models\Ticket::TEAM_TICKETS[$record->owner_team] ?? 'Helpdesk'}}</div>
                    </div>
                    <div class="rounded p-5 mb-4" style="background:#f8fafc;">
                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Ultima attività cliente</div>
                        <div class="fw-bold">{{$record->last_customer_message_at?->format('d/m/Y H:i') ?? 'Non disponibile'}}</div>
                    </div>
                    <div class="rounded p-5" style="background:#f8fafc;">
                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Ultima attività team</div>
                        <div class="fw-bold">{{$record->last_agent_message_at?->format('d/m/Y H:i') ?? 'Non disponibile'}}</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 mb-6" style="background: linear-gradient(135deg, #ecfeff 0%, #dbeafe 100%);">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">AI Assist</h3>
                </div>
                <div class="card-body pt-0">
                    <div class="badge badge-light-primary mb-3">{{$aiSnapshot['tone']}}</div>
                    <div class="fw-semibold text-gray-800 mb-3">{{$aiSnapshot['summary']}}</div>
                    <div class="rounded p-4 bg-white border border-gray-200 mb-3">
                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Prossimo passo suggerito</div>
                        <div class="fw-semibold">{{$aiSnapshot['next_action']}}</div>
                    </div>
                    <div class="rounded p-4 bg-white border border-gray-200">
                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Attualmente in attesa di</div>
                        <div class="fw-semibold text-capitalize">{{$aiSnapshot['waiting_on']}}</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 mb-6">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Regole automatiche</h3>
                </div>
                <div class="card-body pt-0">
                    @forelse(($record->automation_notes ?? []) as $note)
                        <div class="d-flex align-items-start gap-3 py-3 border-bottom border-gray-100">
                            <span class="badge badge-light-primary mt-1">Auto</span>
                            <div class="fw-semibold text-gray-700">{{$note}}</div>
                        </div>
                    @empty
                        <div class="text-muted">Nessuna regola attivata.</div>
                    @endforelse
                </div>
            </div>

            <div class="card border-0">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold">Timeline Stati</h3>
                </div>
                <div class="card-body pt-0">
                    @forelse($record->statusLogs as $log)
                        <div class="py-3 border-bottom border-gray-100">
                            <div class="fw-semibold">{{\App\Models\Ticket::STATI_TICKETS[$log->to_state]['testo'] ?? $log->to_state}}</div>
                            <div class="text-muted fs-8">{{$log->created_at->format('d/m/Y H:i')}} @if($log->utente) da {{$log->utente->nominativo()}} @endif</div>
                            @if($log->from_state)
                                <div class="text-muted fs-8">Da {{\App\Models\Ticket::STATI_TICKETS[$log->from_state]['testo'] ?? $log->from_state}}</div>
                            @endif
                            @if($log->note)
                                <div class="text-muted fs-8">{{$log->note}}</div>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted">Nessun cambio stato registrato.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@include('Backend._components.dropzoneUx')
@push('customScript')
    <script src="/assets_backend/js-progetto/ckeditor5/build/ckeditor.js"></script>
    <script>
        $(function () {
            const statoAttualeTicket = @json($record->stato);
            const assegnatarioAttualeTicket = @json((int)($record->agente_id ?? 0));

            ClassicEditor
                .create(document.querySelector('#messaggio'))
                .catch(error => {
                    console.error(error);
                });

            $('#segnala-form').on('submit', function (event) {
                const form = this;
                const nuovoStato = $('input[name="stato"]:checked').val() || null;
                const nuovoAssegnatario = parseInt($('#agente_id').val() || '0', 10);

                const confermaChiusura = nuovoStato === 'chiuso' && statoAttualeTicket !== 'chiuso';
                const confermaRiassegnazione = nuovoAssegnatario > 0 && nuovoAssegnatario !== assegnatarioAttualeTicket;

                if (!confermaChiusura && !confermaRiassegnazione) {
                    return true;
                }

                event.preventDefault();
                const messaggi = [];
                if (confermaChiusura) {
                    messaggi.push('stai chiudendo il ticket');
                }
                if (confermaRiassegnazione) {
                    messaggi.push('stai riassegnando il ticket');
                }

                Swal.fire({
                    text: 'Confermi? ' + messaggi.join(' e ') + '.',
                    icon: 'question',
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: 'Sì, conferma',
                    cancelButtonText: 'Annulla',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-light'
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            initGestiioDropzone('#kt_dropzonejs_example_1', {
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
                }
            });
        });
    </script>
@endpush
