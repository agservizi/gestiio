@extends('Backend._components.modal')
@section('content')
    @php($selected=old('tipo_servizio'))
    <div class="w-100 text-center pt-4">
        <div class="fw-bolder fs-4">in collaborazione con:</div>
        <img src="/loghi/logo_euransa.png" class="h-65px">
    </div>
    <div class="pt-6 w-100 text-center">
        <!--begin::Radio group-->
        @foreach(\App\Models\ServizioFinanziario::TIPI_SERVIZI as $key=>$value)
            <a href="{{action([\App\Http\Controllers\Backend\ServizioFinanziarioController::class,'create'],$key)}}"
               class="btn btn-primary">{{$value}}</a>
        @endforeach
    </div>
@endsection


