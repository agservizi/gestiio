@extends('Backend._components.modal',['minW'=>'mw-1000px'])
@section('content')
    @include('Backend.ContrattoTelefonia.allegati',['downloadController' => \App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'idPadre'=>$record->id])
@endsection
