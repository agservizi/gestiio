@extends('Backend._layout._main')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Dettaglio richiesta assistenza</h3>
            <div class="card-toolbar">
                <a class="btn btn-sm btn-light-primary" href="{{action([$controller,'edit'],$record->id)}}">Modifica</a>
                <a class="btn btn-sm btn-light" href="{{action([$controller,'index'])}}">Torna elenco</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-6">
                <div class="col-md-6">
                    <div class="text-muted fs-7">Cliente</div>
                    <div class="fw-bold fs-5">{{$record->cliente?->nominativo() ?: '-'}}</div>
                    <div class="text-muted">{{$record->cliente?->codice_fiscale ?: ''}}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-7">Prodotto assistenza</div>
                    <div class="fw-bold fs-5">{{$record->prodotto?->nome ?: '-'}}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-7">Operazione</div>
                    <div class="fw-bold">{{$record->tipoOperazioneLabel()}}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-7">Importo</div>
                    <div class="fw-bold">{!! importo($record->importo_economico, true) !!}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-7">Nome utente</div>
                    <div class="fw-bold">{{$record->nome_utente ?: '-'}}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-7">Password</div>
                    <div class="fw-bold">{{$record->password ?: '-'}}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-7">PIN</div>
                    <div class="fw-bold">{{$record->pin ?: '-'}}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
