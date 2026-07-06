@extends('Backend._layout._main')
@section('titolo','Registro modifiche')
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifiche backend</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th>Data</th>
                        <th>Utente</th>
                        <th>Metodo</th>
                        <th>Path</th>
                        <th>Esito</th>
                        <th class="text-end">ms</th>
                        <th>Dati</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td class="text-nowrap">{{ optional($record->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $record->utente->name ?? $record->user_id ?? '-' }}</td>
                            <td>{{ $record->method }}</td>
                            <td><code class="text-break">{{ $record->path }}</code></td>
                            <td>{{ $record->status_code }}</td>
                            <td class="text-end">{{ $record->duration_ms }}</td>
                            <td><code class="text-break">{{ json_encode($record->payload, JSON_UNESCAPED_UNICODE) }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Nessuna modifica registrata.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($records, 'links'))
                {{ $records->links() }}
            @endif
        </div>
    </div>
@endsection
