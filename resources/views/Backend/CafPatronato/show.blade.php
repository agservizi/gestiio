@extends('Backend._layout._main')

@section('toolbar')
    @if($record->id && \App\Models\CafPatronato::puoModificare())
        <a href="{{ action([\App\Http\Controllers\Backend\CafPatronatoController::class, 'edit'], $record->id) }}" class="btn btn-sm btn-primary fw-bold">Modifica</a>
    @endif
@endsection

@section('content')
    <div class="card card-flush mb-6">
        <div class="card-body">
            <div class="mb-5">
                <h4 class="fw-bold mb-1">Dati pratica</h4>
                <div class="text-muted fs-7">Informazioni principali della richiesta CAF/Patronato</div>
            </div>
            <div class="row g-5">
                <div class="col-md-6">
                    <div class="text-muted fs-7">Data</div>
                    <div class="fw-semibold">{{ $record->data?->format('d/m/Y') }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-7">Tipo pratica</div>
                    <div class="fw-semibold">{{ $record->tipo?->nome }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-7">Agente</div>
                    <div class="fw-semibold">{{ $record->agente?->nominativo() }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-7">Esito</div>
                    <div class="fw-semibold">{{ $record->esito?->nome ?? $record->esito_id }}</div>
                </div>
            </div>

            <div class="separator separator-dashed my-6"></div>
            <div class="mb-5">
                <h4 class="fw-bold mb-1">Dati cliente</h4>
                <div class="text-muted fs-7">Anagrafica e recapiti</div>
            </div>
            <div class="row g-5">
                <div class="col-md-6">
                    <div class="text-muted fs-7">Nominativo</div>
                    <div class="fw-semibold">{{ $record->nominativo() }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-7">Codice fiscale</div>
                    <div class="fw-semibold">{{ $record->codice_fiscale }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-7">Email</div>
                    <div class="fw-semibold">{{ $record->email ?: '—' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-7">Cellulare</div>
                    <div class="fw-semibold">{{ $record->cellulare ?: '—' }}</div>
                </div>
                <div class="col-md-8">
                    <div class="text-muted fs-7">Indirizzo</div>
                    <div class="fw-semibold">{{ $record->indirizzo ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-7">CAP</div>
                    <div class="fw-semibold">{{ $record->cap ?: '—' }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted fs-7">Note</div>
                    <div class="fw-semibold">{{ $record->note ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header border-0 pt-5 pb-2">
            <h3 class="card-title">Allegati</h3>
        </div>
        <div class="card-body pt-0">
            @php
                $allegati = $record->allegati;
            @endphp
            @if($allegati->isEmpty())
                <div class="text-muted">Nessun allegato presente.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3">
                        <thead>
                        <tr class="fw-bold text-muted">
                            <th>Nome file</th>
                            <th class="text-end">Dimensione</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($allegati as $allegato)
                            <tr>
                                <td>{{ $allegato->filename_originale }}</td>
                                <td class="text-end">{{ \App\humanFileSize($allegato->dimensione_file) }}</td>
                                <td class="text-end">
                                    <a class="btn btn-light-primary btn-sm" href="{{ action([\App\Http\Controllers\Backend\CafPatronatoController::class, 'downloadAllegato'], [$record->id, $allegato->id]) }}">Scarica</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
