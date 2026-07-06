@extends('Backend._layout._main')
@section('titolo','Registro errori server')
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Errori server, richieste lente e query lente</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Livello</th>
                        <th>Messaggio</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td class="text-nowrap">{{ $record['data'] }}</td>
                            <td class="text-nowrap">{{ $record['tipo'] }}</td>
                            <td class="text-nowrap">{{ $record['livello'] }}</td>
                            <td><code class="text-break">{{ $record['messaggio'] }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Nessun errore recente rilevato.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
