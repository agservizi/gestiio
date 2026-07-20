@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex flex-nowrap align-items-center gap-2">
        <a class="btn btn-sm btn-light flex-shrink-0" href="{{ action([$controller, 'pdf'], $record->id) }}" target="_blank" rel="noopener">PDF</a>
        @can('emit', $record)
            <form method="post" action="{{ action([$controller, 'emetti'], $record->id) }}" class="d-inline flex-shrink-0">
                @csrf
                <button type="button" class="btn btn-sm btn-primary" onclick="return gestiioAsk(this, 'Emettere questa proforma?')">Emetti</button>
            </form>
        @endcan
        @can('sendEmail', $record)
            <form method="post" action="{{ action([$controller, 'inviaEmail'], $record->id) }}" class="d-inline flex-shrink-0">
                @csrf
                <button type="button" class="btn btn-sm btn-light-info" onclick="return gestiioAsk(this, 'Inviare la proforma via email all\'agente?')">Invia email</button>
            </form>
        @endcan
        @can('markPaid', $record)
            <form method="post" action="{{ action([$controller, 'segnaPagata'], $record->id) }}" class="d-inline flex-shrink-0">
                @csrf
                <button type="button" class="btn btn-sm btn-light-success" onclick="return gestiioAsk(this, 'Segnare questa proforma come pagata?')">Segna pagata</button>
            </form>
        @endcan
        @can('regenerate', $record)
            <form method="post" action="{{ action([$controller, 'rigenera'], $record->id) }}" class="d-inline flex-shrink-0">
                @csrf
                <button type="button" class="btn btn-sm btn-light-warning" onclick="return gestiioAsk(this, 'Rigenerare le righe dalla produzione?')">Rigenera</button>
            </form>
        @endcan
        @can('delete', $record)
            <a class="btn btn-sm btn-light-danger flex-shrink-0" id="elimina" href="{{ action([$controller, 'destroy'], $record->id) }}">Elimina</a>
        @endcan
    </div>
@endsection

@section('content')
    @include('Backend._components.alertMessage')

    <div class="d-flex flex-wrap align-items-center gap-3 mb-5">
        <span class="badge {{ $record->statusBadgeClass() }} fs-7">{{ $record->statusLabel() }}</span>
        @if($record->periodoLabel())
            <span class="text-muted">Periodo {{ $record->periodoLabel() }}</span>
        @endif
        @if($intestazioneIncompleta ?? false)
            <span class="badge badge-light-warning">Intestazione incompleta</span>
        @endif
    </div>

    @include('Backend.FatturaProforma.card', ['view' => true])

    @can('updateIntestazione', $record)
        <div class="card mt-5">
            <div class="card-header">
                <h3 class="card-title">Modifica intestazione</h3>
            </div>
            <div class="card-body">
                <form method="post" action="{{ action([$controller, 'updateIntestazione'], $record->id) }}">
                    @csrf
                    @method('PATCH')
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="form-label required">Denominazione</label>
                            <input type="text" name="denominazione" class="form-control form-control-solid @error('denominazione') is-invalid @enderror"
                                   value="{{ old('denominazione', $record->intestazione->denominazione) }}" required/>
                            @error('denominazione')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Codice fiscale / P.IVA</label>
                            <input type="text" name="codice_fiscale" class="form-control form-control-solid"
                                   value="{{ old('codice_fiscale', $record->intestazione->codice_fiscale) }}"/>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Indirizzo</label>
                            <input type="text" name="indirizzo" class="form-control form-control-solid"
                                   value="{{ old('indirizzo', $record->intestazione->indirizzo) }}"/>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">CAP</label>
                            <input type="text" name="cap" class="form-control form-control-solid"
                                   value="{{ old('cap', $record->intestazione->cap) }}"/>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Città</label>
                            <input type="text" name="citta" class="form-control form-control-solid"
                                   value="{{ old('citta', $record->intestazione->citta) }}"/>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nazione</label>
                            <input type="text" name="nazione" class="form-control form-control-solid"
                                   value="{{ old('nazione', $record->intestazione->nazione) }}"/>
                        </div>
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">Salva intestazione</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('customScript')
<script>
    $(function () {
        formDelete();
    });
</script>
@endpush
