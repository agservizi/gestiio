@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex align-items-center gap-3">
        <a href="{{action([\App\Http\Controllers\Backend\VisuraController::class, 'index'])}}" class="btn btn-sm btn-light">
            Torna a elenco
        </a>
    </div>
@endsection

@section('content')
    @include('Backend.Visura._servicePicker')
@endsection
