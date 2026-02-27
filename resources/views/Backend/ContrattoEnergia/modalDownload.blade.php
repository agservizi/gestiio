@extends('Backend._components.modal',['minW'=>'mw-1000px'])
@section('content')
    @include('Backend.ContrattoEnergia.allegatiTabella', [
        'downloadController' => \App\Http\Controllers\Backend\ContrattoEnergiaController::class,
        'idPadre' => $record->id,
        'previewModalId' => 'modal_preview_allegati_energia_download_' . $record->id,
    ])
@endsection
