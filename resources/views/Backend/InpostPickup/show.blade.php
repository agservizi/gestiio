@extends('Backend._layout._main')
@section('toolbar') @endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Stato</label>
                    <div>{!! $record->statusBadge() !!}</div>
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'remote_id','testo'=>'ID ritiro InPost'])
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'contact_name','testo'=>'Contatto'])
                </div>
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'contact_phone','testo'=>'Telefono'])
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'street','testo'=>'Via'])
                </div>
                <div class="col-md-3">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'post_code','testo'=>'CAP'])
                </div>
                <div class="col-md-3">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'city','testo'=>'Città'])
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'pickup_date','testo'=>'Data ritiro','valore'=>$record->pickup_date?->format('d/m/Y')])
                </div>
                <div class="col-md-4">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'parcel_count','testo'=>'Colli'])
                </div>
                <div class="col-md-4">
                    @include('Backend._inputs.inputTextReadonly',['campo'=>'customer_reference','testo'=>'Riferimento'])
                </div>
            </div>
            @if($record->note)
                <div class="row mt-3">
                    <div class="col-md-12">
                        @include('Backend._inputs.inputTextReadonly',['campo'=>'note','testo'=>'Note'])
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
