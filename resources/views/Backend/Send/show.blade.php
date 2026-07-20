@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <a href="{{ action([$controller, 'index']) }}" class="btn btn-sm btn-light">Elenco</a>
        @can('update', $record)
            <a href="{{ action([$controller, 'edit'], $record) }}" class="btn btn-sm btn-light-primary">Modifica</a>
        @endcan
    </div>
@endsection

@section('content')
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="d-flex flex-wrap align-items-center gap-3 mb-5">
        <h2 class="mb-0">{{ $record->request_number }}</h2>
        <span class="badge {{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span>
        <span class="badge {{ $record->priority->badgeClass() }}">{{ $record->priority->label() }}</span>
        <span class="text-muted">{{ $record->applicant_type->label() }}</span>
    </div>

    @if(count($missing))
        <div class="alert alert-warning">Requisiti mancanti per l’invio: {{ implode('; ', $missing) }}</div>
    @endif

    <div class="row g-5">
        <div class="col-lg-8">
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Dati pratica</h3></div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6"><div class="text-muted fs-7">Avviso</div><div>{{ $record->send_notice_identifier ?: '—' }}</div></div>
                        <div class="col-md-6"><div class="text-muted fs-7">IUN</div><div>{{ $record->iun ?: '—' }}</div></div>
                        <div class="col-md-6"><div class="text-muted fs-7">Ente</div><div>{{ $record->sender_entity ?: '—' }}</div></div>
                        <div class="col-md-6"><div class="text-muted fs-7">Operatore</div><div>{{ $record->creator?->nominativo() }}</div></div>
                        <div class="col-md-6"><div class="text-muted fs-7">Supervisore</div><div>{{ $record->supervisor?->nominativo() ?: '—' }}</div></div>
                        <div class="col-md-6"><div class="text-muted fs-7">Scadenza avviso</div><div>{{ optional($record->due_date)->format('d/m/Y') ?: '—' }}</div></div>
                        <div class="col-md-6">
                            <div class="text-muted fs-7">Importo cliente</div>
                            <div class="fw-bold text-primary fs-4">{!! importo($record->prezzo_cliente ?? 5, true) !!}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-7">Addebito plafond</div>
                            <div>{!! importo($record->prezzo_agente ?? 4, true) !!}</div>
                        </div>
                    </div>
                    @if($record->integration_reason)
                        <div class="alert alert-warning mt-5 mb-0">
                            <strong>Integrazione:</strong> {{ $record->integration_reason }}
                            @if($record->integration_category)<div class="fs-7">Categoria: {{ $record->integration_category }}</div>@endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Soggetti</h3></div>
                <div class="card-body">
                    @foreach($record->subjects as $s)
                        <div class="border rounded p-4 mb-3">
                            <div class="fw-bold text-capitalize">{{ str_replace('_', ' ', $s->subject_role) }}</div>
                            <div>{{ $s->displayName() }}</div>
                            @if($s->tax_code)<div class="text-muted">CF: {{ $s->tax_code }}</div>@endif
                            @if($s->vat_number)<div class="text-muted">P.IVA: {{ $s->vat_number }}</div>@endif
                            @if($s->phone)<div class="text-muted">Tel: {{ $s->phone }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Checklist</h3></div>
                <div class="card-body">
                    @foreach($record->checklistItems as $item)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="ki-duotone {{ $item->completed ? 'ki-check-circle text-success' : 'ki-cross-circle text-warning' }} fs-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <span>{{ $item->label }} @if($item->required)<span class="badge badge-light">obbligatorio</span>@endif</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header d-flex justify-content-between">
                    <h3 class="card-title">Allegati</h3>
                </div>
                <div class="card-body">
                    @can('uploadClientDocument', $record)
                        <div class="border border-primary border-dashed rounded p-4 mb-5 bg-light-primary" id="client-allegati">
                            <div class="fw-bold mb-2">Allegati SEND per il cliente</div>
                            <div class="text-muted fs-7 mb-3">
                                Carica qui risultato/ricevuta SEND. Puoi aggiungere più file; admin e agente li scaricano da elenco o da questa pagina.
                            </div>
                            @if($clientDocuments->isNotEmpty())
                                <div class="mb-4">
                                    @foreach($clientDocuments as $clientDoc)
                                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                            <div>
                                                <div class="fw-semibold">{{ $clientDoc->original_name }}</div>
                                                <div class="text-muted fs-7">Caricato · {{ number_format($clientDoc->size/1024,1) }} KB</div>
                                            </div>
                                            @can('downloadDocument', $clientDoc)
                                                <a class="btn btn-sm btn-light-primary" href="{{ action([$controller, 'downloadDocument'], [$record, $clientDoc]) }}">Scarica</a>
                                            @endcan
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <form method="post" action="{{ action([$controller, 'uploadClientDocument'], $record) }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                                @csrf
                                <div class="col-md-8"><input type="file" name="file" class="form-control" required></div>
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100" type="submit">
                                        {{ $clientDocuments->isNotEmpty() ? 'Aggiungi allegato' : 'Carica allegato SEND' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @elseif($clientDocuments->isNotEmpty())
                        <div class="border rounded p-4 mb-5" id="client-allegati">
                            <div class="fw-bold mb-3">Allegati SEND per il cliente</div>
                            @foreach($clientDocuments as $clientDoc)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <div class="fw-semibold">{{ $clientDoc->original_name }}</div>
                                        <div class="text-muted fs-7">{{ number_format($clientDoc->size/1024,1) }} KB</div>
                                    </div>
                                    @can('downloadDocument', $clientDoc)
                                        <a class="btn btn-sm btn-primary" href="{{ action([$controller, 'downloadDocument'], [$record, $clientDoc]) }}">Scarica</a>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @can('uploadOperatorDocument', $record)
                        <form method="post" action="{{ action([$controller, 'uploadDocument'], $record) }}" enctype="multipart/form-data" class="row g-3 mb-5">
                            @csrf
                            <div class="col-md-5">
                                <select name="category" class="form-select" required>
                                    @foreach($documentCategories as $cat)
                                        @if(! in_array($cat->value, ['risultato', 'ricevuta'], true))
                                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5"><input type="file" name="file" class="form-control" required></div>
                            <div class="col-md-2"><button class="btn btn-light-primary w-100" type="submit">Carica</button></div>
                        </form>
                    @endcan
                    @forelse($record->documents as $doc)
                        @continue($doc->visibility === 'citizen_receipt')
                        @can('viewDocument', $doc)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                <div>
                                    <div class="fw-semibold">
                                        {{ $doc->category->label() }}
                                        @if($doc->visibility === 'citizen_receipt')
                                            <span class="badge badge-light-success">Per cliente</span>
                                        @endif
                                    </div>
                                    <div class="text-muted fs-7">{{ $doc->original_name }} · {{ number_format($doc->size/1024,1) }} KB</div>
                                </div>
                                <div class="d-flex gap-2">
                                    @can('downloadDocument', $doc)
                                        <a class="btn btn-sm btn-light" href="{{ action([$controller, 'downloadDocument'], [$record, $doc]) }}">Scarica</a>
                                    @endcan
                                    @can('send.documents.delete')
                                        <form method="post" action="{{ action([$controller, 'destroyDocument'], [$record, $doc]) }}" data-confirm="Eliminare allegato?" data-confirm-danger>
                                            @csrf
                                            <button class="btn btn-sm btn-light-danger" type="submit">Elimina</button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        @endcan
                    @empty
                        <div class="text-muted">Nessun allegato.</div>
                    @endforelse
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Note</h3></div>
                <div class="card-body">
                    @foreach($record->notes as $note)
                        @php
                            $canSee = $note->visibility->value !== 'internal'
                                || auth()->user()->can('viewInternalNotes', $record);
                        @endphp
                        @if($canSee)
                            <div class="border rounded p-3 mb-3">
                                <div class="fs-7 text-muted">{{ $note->author?->nominativo() }} · {{ $note->created_at?->format('d/m/Y H:i') }} · {{ $note->visibility->label() }}</div>
                                <div>{{ $note->body }}</div>
                            </div>
                        @endif
                    @endforeach
                    <form method="post" action="{{ action([$controller, 'addNote'], $record) }}" class="row g-3">
                        @csrf
                        <div class="col-md-8"><textarea name="note" class="form-control" rows="2" required placeholder="Nuova nota"></textarea></div>
                        <div class="col-md-2">
                            <select name="visibility" class="form-select">
                                <option value="operator">Operatore</option>
                                @can('createInternalNote', $record)
                                    <option value="internal">Interna</option>
                                @endcan
                                <option value="citizen">Cittadino</option>
                            </select>
                        </div>
                        <div class="col-md-2"><button class="btn btn-light-primary w-100" type="submit">Aggiungi</button></div>
                    </form>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Timeline</h3></div>
                <div class="card-body">
                    @foreach($record->statusHistory as $h)
                        <div class="d-flex gap-3 mb-3">
                            <div class="fw-bold" style="min-width:140px">{{ $h->to_status?->label() }}</div>
                            <div>
                                <div class="fs-7 text-muted">{{ $h->created_at?->format('d/m/Y H:i') }} · {{ $h->changer?->nominativo() ?: 'Sistema' }}</div>
                                @if($h->reason)<div>{{ $h->reason }}</div>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @can('viewAudit', $record)
                <div class="card mb-5">
                    <div class="card-header"><h3 class="card-title">Audit</h3></div>
                    <div class="card-body">
                        @foreach($record->auditLogs as $log)
                            <div class="fs-7 border-bottom py-2">
                                {{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->user?->nominativo() }} · {{ $log->action }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-lg-4">
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Azioni</h3></div>
                <div class="card-body d-grid gap-3">
                    @can('downloadClientDocument', $record)
                        @if($clientDocuments->count() === 1)
                            <a class="btn btn-light-primary w-100" href="{{ action([$controller, 'downloadDocument'], [$record, $clientDocuments->first()]) }}">
                                Scarica allegato SEND
                            </a>
                        @elseif($clientDocuments->count() > 1)
                            <a class="btn btn-light-primary w-100" href="#client-allegati">
                                Scarica allegati SEND ({{ $clientDocuments->count() }})
                            </a>
                        @endif
                    @endcan

                    @can('submit', $record)
                        @if(in_array($record->status, [\App\Enums\SendRequestStatus::DRAFT, \App\Enums\SendRequestStatus::INTEGRATION_REQUIRED], true))
                            <form method="post" action="{{ action([$controller, 'submit'], $record) }}">
                                @csrf
                                @php $allowManual = \App\Models\SendSetting::getValue('allow_manual_assignment', '1') === '1'; @endphp
                                @if($allowManual)
                                    <select name="supervisor_id" class="form-select mb-2">
                                        <option value="">Assegnazione automatica</option>
                                        @foreach(app(\App\Http\Services\SendAssignmentService::class)->eligibleSupervisors() as $sup)
                                            <option value="{{ $sup->id }}">{{ $sup->nominativo() }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <button class="btn btn-primary w-100" type="submit">Invia al supervisore</button>
                            </form>
                        @endif
                    @endcan

                    @can('claim', $record)
                        <form method="post" action="{{ action([$controller, 'claim'], $record) }}">@csrf
                            <button type="submit" class="btn btn-warning w-100 mb-3">Assegna a me</button>
                        </form>
                    @endcan
                    @can('takeCharge', $record)
                        <form method="post" action="{{ action([$controller, 'takeCharge'], $record) }}">@csrf
                            <button class="btn btn-info w-100" type="submit">Prendi in carico</button>
                        </form>
                    @endcan

                    @can('startProcessing', $record)
                        <form method="post" action="{{ action([$controller, 'startProcessing'], $record) }}">@csrf
                            <button class="btn btn-light-primary w-100" type="submit">Avvia lavorazione</button>
                        </form>
                    @endcan

                    @can('requestIntegration', $record)
                        <form method="post" action="{{ action([$controller, 'requestIntegration'], $record) }}" class="border rounded p-3">
                            @csrf
                            <label class="form-label">Richiedi integrazione</label>
                            <select name="category" class="form-select mb-2">
                                <option value="documento_illeggibile">Documento illeggibile</option>
                                <option value="documento_scaduto">Documento scaduto</option>
                                <option value="delega_mancante">Delega mancante</option>
                                <option value="delega_incompleta">Delega incompleta</option>
                                <option value="cf_non_leggibile">CF non leggibile</option>
                                <option value="dati_non_corrispondenti">Dati non corrispondenti</option>
                                <option value="poteri_non_documentati">Poteri non documentati</option>
                                <option value="avviso_incompleto">Avviso incompleto</option>
                                <option value="allegato_errato">Allegato errato</option>
                                <option value="altro">Altro</option>
                            </select>
                            <textarea name="reason" class="form-control mb-2" required placeholder="Motivazione obbligatoria"></textarea>
                            <button class="btn btn-warning w-100" type="submit">Richiedi integrazione</button>
                        </form>
                    @endcan

                    @can('reject', $record)
                        <form method="post" action="{{ action([$controller, 'reject'], $record) }}">@csrf
                            <textarea name="reason" class="form-control mb-2" required placeholder="Motivazione rifiuto"></textarea>
                            <button class="btn btn-danger w-100" type="submit">Rifiuta</button>
                        </form>
                    @endcan

                    @can('assign', $record)
                        <form method="post" action="{{ action([$controller, 'reassign'], $record) }}" class="border rounded p-3">
                            @csrf
                            <label class="form-label">Riassegna supervisore</label>
                            <select name="supervisor_id" class="form-select mb-2" required>
                                <option value="">Seleziona…</option>
                                @foreach(app(\App\Http\Services\SendAssignmentService::class)->eligibleSupervisors() as $sup)
                                    <option value="{{ $sup->id }}" @selected($record->assigned_supervisor_id === $sup->id)>{{ $sup->nominativo() }}</option>
                                @endforeach
                            </select>
                            <textarea name="reason" class="form-control mb-2" placeholder="Motivo (opz.)"></textarea>
                            <button class="btn btn-light-info w-100" type="submit">Riassegna</button>
                        </form>
                    @endcan

                    @can('deliver', $record)
                        <form method="post" action="{{ action([$controller, 'deliver'], $record) }}" class="border rounded p-3">
                            @csrf
                            <label class="form-label">Consegna al cittadino</label>
                            <input type="text" name="recipient_name" class="form-control mb-2" placeholder="Chi ritira" required>
                            <input type="text" name="identification_type" class="form-control mb-2" placeholder="Tipo identificazione">
                            <textarea name="documents_summary" class="form-control mb-2" placeholder="Documenti consegnati"></textarea>
                            <label class="form-check mb-2">
                                <input type="checkbox" name="print_done" value="1" class="form-check-input"> Stampa effettuata
                            </label>
                            <button class="btn btn-success w-100" type="submit">Registra consegna e chiudi</button>
                        </form>
                    @endcan

                    @if(in_array($record->status, [\App\Enums\SendRequestStatus::DELIVERED, \App\Enums\SendRequestStatus::CLOSED], true))
                        <a class="btn btn-light w-100" href="{{ action([$controller, 'deliveryReceiptPdf'], $record) }}">
                            Scarica ricevuta consegna PDF
                        </a>
                    @endif

                    @can('reopen', $record)
                        <form method="post" action="{{ action([$controller, 'reopen'], $record) }}">@csrf
                            <textarea name="reason" class="form-control mb-2" placeholder="Motivo riapertura (opz.)"></textarea>
                            <button class="btn btn-light-warning w-100" type="submit" data-confirm="Riaprire come bozza?" data-confirm-danger>Riapri pratica</button>
                        </form>
                    @endcan

                    @can('delete', $record)
                        <form method="post" action="{{ action([$controller, 'destroy'], $record) }}" data-confirm="Eliminare definitivamente la bozza?" data-confirm-danger>
                            @csrf
                            <button class="btn btn-light-danger w-100" type="submit">Elimina bozza</button>
                        </form>
                    @endcan

                    @can('cancel', $record)
                        <form method="post" action="{{ action([$controller, 'cancel'], $record) }}">@csrf
                            <textarea name="reason" class="form-control mb-2" required placeholder="Motivazione annullamento"></textarea>
                            <button class="btn btn-light-danger w-100" type="submit">Annulla pratica</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
