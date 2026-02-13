@extends('Backend._layout._main')
@section('toolbar')
    @can('admin')
        <div class="d-flex align-items-center py-1">
                <a class="btn btn-sm btn-primary fw-bold" id="elimina" data-targetZ="kt_modal" data-toggleZ="modal-ajax" href="{{action([$controller,'destroy'],$record->id)}}">Elimina</a>
        </div>
    @endcan

@endsection
@section('content')
    @include('Backend.FatturaProforma.card',['view'=>true])
@endsection
@push('customScript')
    <script>
        $(function (){
           formDelete();
        });
    </script>
@endpush
