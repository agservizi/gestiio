@extends('Backend._layout._main')
@section('titolo','Registro upload e allegati')
@section('content')
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Audit file manager</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th>Data</th>
                        <th>Utente</th>
                        <th>Azione</th>
                        <th>File</th>
                        <th>Path</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($auditLogs as $record)
                        <tr>
                            <td class="text-nowrap">{{ optional($record->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $record->utente->name ?? $record->user_id ?? '-' }}</td>
                            <td>{{ $record->azione }}</td>
                            <td>{{ $record->filename_originale }}</td>
                            <td><code class="text-break">{{ $record->path_filename }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Nessun audit file manager disponibile.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ultimi allegati pratiche</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th>Modulo</th>
                        <th>ID</th>
                        <th>File</th>
                        <th>Tipo</th>
                        <th class="text-end">Dimensione</th>
                        <th>Creato</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($allegati as $record)
                        <tr>
                            <td>{{ $record['modulo'] }}</td>
                            <td>{{ $record['id'] }}</td>
                            <td><code class="text-break">{{ $record['filename_originale'] ?: $record['path_filename'] }}</code></td>
                            <td>{{ $record['tipo_file'] }}</td>
                            <td class="text-end">
                                @if($record['dimensione_file'])
                                    {{ \App\humanFileSize($record['dimensione_file']) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ optional($record['created_at'])->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Nessun allegato recente rilevato.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
