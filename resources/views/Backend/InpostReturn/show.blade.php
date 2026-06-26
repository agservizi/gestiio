@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex gap-2">
        @if($record->remote_id)
            <a class="btn btn-sm btn-primary" href="{{action([\App\Http\Controllers\Backend\InpostReturnController::class,'sync'],$record->id)}}">Sincronizza</a>
        @endif
        @if($record->qrCodeUrl())
            <a class="btn btn-sm btn-light-primary" href="{{action([\App\Http\Controllers\Backend\InpostReturnController::class,'etichetta'],$record->id)}}" target="_blank">QR / Etichetta</a>
        @endif
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'status','testo'=>'Stato','valore'=>$record->status])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'remote_id','testo'=>'ID remoto InPost'])
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'receiver_name','testo'=>'Destinatario reso'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'receiver_email','testo'=>'Email'])
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'receiver_phone','testo'=>'Telefono'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'customer_reference','testo'=>'Riferimento ordine'])
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'point_id','testo'=>'Punto InPost (ID)'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'point_label','testo'=>'Punto InPost (etichetta)'])
                </div>
            </div>
            @if($record->trackingNumber())
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Tracking</label>
                        <div class="form-control form-control-sm">
                            <a href="https://inpost.pl/en/help/find-parcel?number={{urlencode($record->trackingNumber())}}" target="_blank">
                                {{$record->trackingNumber()}}
                            </a>
                        </div>
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
