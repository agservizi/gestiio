@extends('Backend._layout._main')

@section('content')
    @php
        $luggage = $settings;
        $apiDocsUrl = url('/api/public/deposito-bagagli/docs');
        $adminDocsUrl = url('/api/admin/deposito-bagagli/docs');
        $publicBookingUrl = url('/deposito-bagagli');
        $notificationFields = collect(config('luggage_settings_fields', []));
    @endphp

    <div class="card mb-6">
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
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

            <div class="mb-6">
                <h2 class="mb-1">Impostazioni Deposito Bagagli</h2>
                <p class="text-muted mb-0">Tariffe, capacità, prenotazioni online, notifiche e integrazione API.</p>
            </div>

            <form method="post" action="{{ action([$controller, 'updateSettings']) }}">
                @csrf
                <div class="accordion" id="accordionDepositoBagagli">
                    <div class="accordion-item border rounded bg-light mb-4" id="tariffe">
                        <h2 class="accordion-header">
                            <button class="accordion-button bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLuggageTariffe">
                                Tariffe e capacità
                            </button>
                        </h2>
                        <div id="collapseLuggageTariffe" class="accordion-collapse collapse show" data-bs-parent="#accordionDepositoBagagli">
                            <div class="accordion-body pt-4">
                                <div class="row g-6">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Tariffa giornaliera (€)</label>
                                        <input type="number" step="0.01" min="0" name="daily_rate" value="{{ old('daily_rate', $luggage->daily_rate) }}" class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Valuta</label>
                                        <input type="text" maxlength="3" name="currency" value="{{ old('currency', $luggage->currency) }}" class="form-control form-control-solid text-uppercase">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Capacità massima giornaliera (borse)</label>
                                        <input type="number" min="1" name="max_capacity" value="{{ old('max_capacity', $luggage->max_capacity) }}" class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Max borse per prenotazione</label>
                                        <input type="number" min="1" name="max_bags_per_booking" value="{{ old('max_bags_per_booking', $luggage->max_bags_per_booking) }}" class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Giorni minimi fatturabili</label>
                                        <input type="number" min="1" name="min_days" value="{{ old('min_days', $luggage->min_days) }}" class="form-control form-control-solid">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border rounded bg-light mb-4" id="prenotazioni">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLuggagePrenotazioni">
                                Prenotazioni e portale
                            </button>
                        </h2>
                        <div id="collapseLuggagePrenotazioni" class="accordion-collapse collapse" data-bs-parent="#accordionDepositoBagagli">
                            <div class="accordion-body pt-4">
                                @foreach($notificationFields->whereIn('name', ['luggage_online_booking_enabled', 'luggage_booking_instructions']) as $field)
                                    @include('Backend.LuggageDeposit._setting_field', ['field' => $field])
                                @endforeach
                                <div class="rounded border border-dashed p-4 bg-white">
                                    <div class="fw-semibold mb-1">Pagina pubblica prenotazione</div>
                                    <a href="{{ $publicBookingUrl }}" target="_blank" class="text-primary">{{ $publicBookingUrl }}</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border rounded bg-light mb-4" id="notifiche">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLuggageNotifiche">
                                Notifiche
                            </button>
                        </h2>
                        <div id="collapseLuggageNotifiche" class="accordion-collapse collapse" data-bs-parent="#accordionDepositoBagagli">
                            <div class="accordion-body pt-4">
                                @foreach($notificationFields->whereIn('name', ['luggage_notify_staff', 'luggage_notify_customer_receipt', 'luggage_staff_notification_email']) as $field)
                                    @include('Backend.LuggageDeposit._setting_field', ['field' => $field])
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border rounded bg-light" id="api">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLuggageApi">
                                Integrazione API REST
                            </button>
                        </h2>
                        <div id="collapseLuggageApi" class="accordion-collapse collapse" data-bs-parent="#accordionDepositoBagagli">
                            <div class="accordion-body pt-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Base URL API pubblica</label>
                                        <input type="text" readonly class="form-control form-control-solid" value="{{ url('/api/public/deposito-bagagli') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Header autenticazione</label>
                                        <input type="text" readonly class="form-control form-control-solid" value="x-api-key">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">OpenAPI pubblica</label>
                                        <a href="{{ $apiDocsUrl }}" target="_blank" class="btn btn-sm btn-light-primary">{{ $apiDocsUrl }}</a>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">OpenAPI admin</label>
                                        <a href="{{ $adminDocsUrl }}" target="_blank" class="btn btn-sm btn-light-primary">{{ $adminDocsUrl }}</a>
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-warning mb-0">
                                            La chiave API è configurata in <code>.env</code> come <code>LUGGAGE_API_KEY</code>.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="btn btn-primary">Salva impostazioni</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('customScript')
<script>
    (function () {
        var hash = window.location.hash.replace('#', '');
        if (!hash) return;
        var panel = document.getElementById(hash);
        if (!panel) return;
        var collapse = panel.querySelector('.accordion-collapse');
        if (collapse) {
            collapse.classList.add('show');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    })();
</script>
@endpush
