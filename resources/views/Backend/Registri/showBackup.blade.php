@extends('Backend._layout._main')
@section('titolo','Registro login')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        <a class="btn btn-sm btn-primary" data-targetZ="kt_modal" data-toggleZ="modal-ajax"
           href="{{action([\App\Http\Controllers\Backend\RegistriController::class,'index'],['backup-db','esegui'])}}">Esegui backup</a>
    </div>
@endsection
@section('content')
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Riepilogo</h3>
            <div class="card-toolbar">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered ">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        @foreach($headers as $th)
                            <th class="text-center"> {{$th}}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="">
                        @foreach($rows[0] as $td)
                            <td class="text-center"> {{$td}} </td>
                        @endforeach
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Elenco files</h3>
            <div class="card-toolbar">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered ">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th class="">File</th>
                        <th class="">Eseguito</th>
                        <th class="text-end">Dimensione</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($files as $file)
                        <tr>
                            <td>{{ basename($file['path']) }}</td>
                            <td>{{ \Carbon\Carbon::createFromTimestamp($file['lastModified'])->diffForHumans() }}</td>
                            <td class="text-end">{{ \App\humanFileSize($file['fileSize']) }}</td>
                            <td class="text-end">
                                <a href="{{ action([\App\Http\Controllers\Backend\RegistriController::class,'index'],['cosa'=>'backup-db','scarica'=>$file['path']]) }}">Scarica</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Nessun backup disponibile.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

