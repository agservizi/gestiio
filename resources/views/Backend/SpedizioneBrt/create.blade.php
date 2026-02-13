@extends('Backend._components.modal')
@section('content')
    <div class="row pt-6 ">
        <div class="col-md-6 text-center">
            <a href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'create'],'ITALIA')}}" class="btn btn-primary">Italia</a>
        </div>
        <div class="col-md-6 text-center">
            <a href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'create'],'EUROPA')}}" class="btn btn-primary">Europa</a>
        </div>
    </div>
@endsection
