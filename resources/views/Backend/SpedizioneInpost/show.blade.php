@extends('Backend._layout._main')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'esito','testo'=>'Esito','valore'=>$record->esito_testo ?: $record->esito])
                </div>
                <div class="col-md-6">
                    <label class="form-label">Stato tracking</label>
                    <div>{!! $record->trackingStatusBadge() !!}</div>
                    <div class="text-muted mt-1">Ultimo aggiornamento: {{$record->trackingUpdatedAtLabel() ?: '-'}}</div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'tracking_number','testo'=>'Tracking number'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'shipment_uuid','testo'=>'Shipment UUID'])
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'ragione_sociale_destinatario','testo'=>'Destinatario'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'delivery_type','testo'=>'Tipo consegna','valore'=>$record->delivery_type === 'point' ? 'Address to Point' : 'Address to Address'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'indirizzo_destinatario','testo'=>'Indirizzo destinatario'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'localita_destinazione','testo'=>'Localita destinazione'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'cap_destinatario','testo'=>'CAP'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'punto_inpost_label','testo'=>'Punto InPost'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'nome_mittente','testo'=>'Mittente'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'mobile_mittente','testo'=>'Telefono mittente'])
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <a class="btn btn-sm btn-primary" href="{{action([\App\Http\Controllers\Backend\SpedizioneInpostController::class,'etichetta'],$record->id)}}">Etichetta</a>
                </div>
            </div>
            @if($record->trackingTimelineHtml())
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label class="form-label">Timeline tracking</label>
                        {!! $record->trackingTimelineHtml() !!}
                    </div>
                </div>
            @endif
            @can('admin')
                @foreach($record->chiamate as $log)
                    <h4 class="mt-6">{{$log->created_at->format('d/m/Y H:i:s')}}</h4>
                    <div class="row">
                        <div class="col-md-6"><pre>{!! print_r($log->request, true) !!}</pre></div>
                        <div class="col-md-6"><pre>{!! print_r($log->response, true) !!}</pre></div>
                    </div>
                @endforeach
            @endcan
        </div>
    </div>
@endsection
