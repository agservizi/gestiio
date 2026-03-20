@extends('Backend._layout._main')
@section('toolbar')
    <a class="btn btn-sm btn-primary" href="{{action([$controller,'sync'],$record->id)}}">Sincronizza reso</a>
@endsection
@section('content')
    <div class="card"><div class="card-body">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'customer_reference','testo'=>'Customer reference'])
        @include('Backend._inputs.inputTextReadonly',['campo'=>'remote_id','testo'=>'Remote ID'])
        @include('Backend._inputs.inputTextReadonly',['campo'=>'status','testo'=>'Stato'])
        @include('Backend._inputs.inputTextReadonly',['campo'=>'point_id','testo'=>'Punto'])
        <div class="row"><div class="col-md-6"><pre>{!! print_r($record->request_payload, true) !!}</pre></div><div class="col-md-6"><pre>{!! print_r($record->response, true) !!}</pre></div></div>
    </div></div>
@endsection
