@extends('Backend._layout._main')

@section('content')
    <div class="card mb-6">
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
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-6 d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <h2 class="mb-1">La mia postazione</h2>
                    <p class="text-muted mb-0">Tariffe, capacità e accettazione online della tua postazione locker point.</p>
                </div>
                <a href="{{ action([$controller, 'dashboard']) }}" class="btn btn-light">Torna al dashboard</a>
            </div>

            <div class="rounded border border-dashed p-4 bg-light mb-6">
                <div class="fw-semibold mb-1">Link pubblico locker point</div>
                <a href="{{ $station->publicBookingUrl() }}" target="_blank" rel="noopener">{{ $station->publicBookingUrl() }}</a>
                <div class="form-text mt-2">Slug: <code>{{ $station->slug }}</code></div>
            </div>

            <form method="post" action="{{ action([$controller, 'updateStationSettings']) }}" class="mb-8">
                @csrf
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nome postazione</label>
                        <input type="text" name="name" value="{{ old('name', $station->name) }}" class="form-control form-control-solid" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tariffa giornaliera (€)</label>
                        <input type="number" step="0.01" min="0" name="daily_rate" value="{{ old('daily_rate', $station->daily_rate) }}" class="form-control form-control-solid" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Valuta</label>
                        <input type="text" maxlength="3" name="currency" value="{{ old('currency', $station->currency) }}" class="form-control form-control-solid text-uppercase" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Capacità massima / giorno</label>
                        <input type="number" min="1" name="max_capacity" value="{{ old('max_capacity', $station->max_capacity) }}" class="form-control form-control-solid" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Max pacchi / prenotazione</label>
                        <input type="number" min="1" name="max_packages_per_booking" value="{{ old('max_packages_per_booking', $station->max_packages_per_booking) }}" class="form-control form-control-solid" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Giorni minimi fatturabili</label>
                        <input type="number" min="1" name="min_days" value="{{ old('min_days', $station->min_days) }}" class="form-control form-control-solid" required>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="online_intake_enabled" value="1" id="online_intake_enabled"
                                {{ old('online_intake_enabled', $station->online_intake_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label" for="online_intake_enabled">Accettazione online abilitata sul tuo link pubblico</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-6">Salva postazione</button>
            </form>

            <div class="border-top pt-6">
                <h3 class="mb-3">API REST per il tuo sito</h3>
                <p class="text-muted">Usa la chiave API (solo server-side) verso <code>/api/public/locker-point/*</code>. I pacchi creati restano isolati nella tua postazione.</p>
                <ul class="mb-4">
                    <li>Stato API: <strong>{{ $station->api_enabled ? 'Abilitate' : 'Disabilitate' }}</strong></li>
                    <li>Prefisso chiave: <code>{{ $station->api_key_prefix ?: '—' }}</code></li>
                    <li>Richiesta inviata: {{ $station->api_requested_at?->format('d/m/Y H:i') ?: 'no' }}</li>
                </ul>
                @unless($station->api_enabled)
                    <form method="post" action="{{ action([$controller, 'requestStationApi']) }}">
                        @csrf
                        <button type="submit" class="btn btn-light-primary">Richiedi abilitazione API ad admin</button>
                    </form>
                @endunless
            </div>
        </div>
    </div>
@endsection
