@extends('Backend._layout._main')
@section('titolo','Registro backup database')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        <form method="POST" action="{{ route('registro.backup.esegui') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">Esegui backup</button>
        </form>
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
                        <th class="">Disco</th>
                        <th class="">File</th>
                        <th class="">Eseguito</th>
                        <th class="text-end">Dimensione</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($files as $file)
                        <tr>
                            <td>{{ $file['disk'] }}</td>
                            <td>{{ basename($file['path']) }}</td>
                            <td>{{ \Carbon\Carbon::createFromTimestamp($file['lastModified'])->diffForHumans() }}</td>
                            <td class="text-end">{{ \App\humanFileSize($file['fileSize']) }}</td>
                            <td class="text-end">
                                <a href="{{ route('registro.backup.scarica', ['path' => $file['path'], 'disk' => $file['disk']]) }}">Scarica</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Nessun backup disponibile.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

