@extends('Backend._layout._main')

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($plainApiKey ?? null)
                <div class="alert alert-warning">
                    <strong>API key (mostrata una sola volta):</strong>
                    <code class="user-select-all">{{ $plainApiKey }}</code>
                </div>
            @endif

            <div class="mb-6">
                <h2 class="mb-1">Postazioni locker point</h2>
                <p class="text-muted mb-0">Abilita le API REST agli agenti che le richiedono. L'area HQ globale non viene modificata.</p>
            </div>

            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                    <tr>
                        <th>Postazione</th>
                        <th>Agente</th>
                        <th>Slug</th>
                        <th>Online</th>
                        <th>API</th>
                        <th>Richiesta</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($stations as $station)
                        <tr>
                            <td>{{ $station->name }}</td>
                            <td>{{ $station->user?->cognome }} {{ $station->user?->nome }}</td>
                            <td><code>{{ $station->slug }}</code></td>
                            <td>{{ $station->online_intake_enabled ? 'Sì' : 'No' }}</td>
                            <td>
                                @if($station->api_enabled)
                                    <span class="badge badge-light-success">ON</span>
                                    <span class="text-muted ms-1">{{ $station->api_key_prefix }}…</span>
                                @else
                                    <span class="badge badge-light">OFF</span>
                                @endif
                            </td>
                            <td>{{ $station->api_requested_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="text-end text-nowrap">
                                @unless($station->api_enabled)
                                    <form class="d-inline" method="post" action="{{ action([$controller, 'enableStationApi'], $station->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-primary">Abilita API</button>
                                    </form>
                                @else
                                    <form class="d-inline" method="post" action="{{ action([$controller, 'regenerateStationApi'], $station->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-light">Rigenera key</button>
                                    </form>
                                    <form class="d-inline" method="post" action="{{ action([$controller, 'disableStationApi'], $station->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-light-danger">Disabilita</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted">Nessuna postazione agente ancora creata.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
