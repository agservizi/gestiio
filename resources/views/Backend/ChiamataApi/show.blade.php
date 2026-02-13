@extends('Backend._layout._main')
@section('toolbar')
@endsection

@section('content')
    @php($vecchio=$record->id)
    <div class="card">
        <div class="card-body">

            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'servizio','testo'=>'Servizio','required'=>true,'autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'url','testo'=>'Url','required'=>true,'autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'method','testo'=>'Method','required'=>true,'autocomplete'=>'off'])
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'status','testo'=>'Status','autocomplete'=>'off'])
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <pre>
                    {!! print_r($record->request) !!}
                    </pre>
                </div> <div class="col-md-6">
                    <pre>
                    {!! print_r($record->response) !!}
                    </pre>
                </div>
            </div>
        </div>
    </div>
@endsection
