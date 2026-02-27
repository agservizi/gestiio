@extends('Backend._components.modal',['minW'=>'mw-1000px'])
@section('content')
    @include('Backend.ContrattoTelefonia.allegatiTabella', [
        'downloadController' => \App\Http\Controllers\Backend\ContrattoTelefoniaController::class,
        'idPadre' => $record->id,
        'previewModalId' => 'modal_preview_allegati_telefonia_download_' . $record->id,
    ])
@endsection
