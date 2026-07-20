@extends('Backend._layout._main')

@section('content')
    @php
        $locker = $settings;
        $notificationFields = collect([
            ['type' => 'checkbox', 'name' => 'locker_online_intake_enabled', 'label' => 'Accettazione online attiva'],
            ['type' => 'textarea', 'name' => 'locker_booking_instructions', 'label' => 'Istruzioni mostrate in fase di prenotazione'],
            ['type' => 'checkbox', 'name' => 'locker_notify_staff', 'label' => 'Invia email notifica allo staff'],
            ['type' => 'text', 'name' => 'locker_staff_notification_email', 'label' => 'Email aggiuntiva notifiche staff'],
        ]);
        $publicIntakeUrl = url('/locker-point');
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
                <h2 class="mb-1">Impostazioni Locker Point</h2>
                <p class="text-muted mb-0">Tariffe, capacità, accettazione online, notifiche e canone agenti.</p>
            </div>

            <form method="post" action="{{ action([$controller, 'updateSettings']) }}">
                @csrf
                <div class="accordion" id="accordionLockerPoint">
                    <div class="accordion-item border rounded bg-light mb-4" id="tariffe">
                        <h2 class="accordion-header">
                            <button class="accordion-button bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLockerTariffe">
                                Tariffe e capacità
                            </button>
                        </h2>
                        <div id="collapseLockerTariffe" class="accordion-collapse collapse show" data-bs-parent="#accordionLockerPoint">
                            <div class="accordion-body pt-4">
                                <div class="row g-6">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Tariffa giornaliera (€)</label>
                                        <input type="number" step="0.01" min="0" name="daily_rate" value="{{ old('daily_rate', $locker->daily_rate) }}" class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Valuta</label>
                                        <input type="text" maxlength="3" name="currency" value="{{ old('currency', $locker->currency) }}" class="form-control form-control-solid text-uppercase">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Capacità massima giornaliera (pacchi)</label>
                                        <input type="number" min="1" name="max_capacity" value="{{ old('max_capacity', $locker->max_capacity) }}" class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Max pacchi per prenotazione</label>
                                        <input type="number" min="1" name="max_packages_per_booking" value="{{ old('max_packages_per_booking', $locker->max_packages_per_booking) }}" class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Giorni minimi fatturabili</label>
                                        <input type="number" min="1" name="min_days" value="{{ old('min_days', $locker->min_days) }}" class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Canone mensile agente (€)</label>
                                        <input type="number" step="0.01" min="0" name="locker_agent_monthly_fee" value="{{ old('locker_agent_monthly_fee', \App\Http\Support\LockerConfig::agentMonthlyFee()) }}" class="form-control form-control-solid">
                                        <div class="form-text">Addebito dal portafoglio servizi per usare il modulo in sportello. 0 = gratuito.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border rounded bg-light mb-4" id="prenotazioni">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLockerPrenotazioni">
                                Accettazione online e portale
                            </button>
                        </h2>
                        <div id="collapseLockerPrenotazioni" class="accordion-collapse collapse" data-bs-parent="#accordionLockerPoint">
                            <div class="accordion-body pt-4">
                                @foreach($notificationFields->whereIn('name', ['locker_online_intake_enabled', 'locker_booking_instructions']) as $field)
                                    @include('Backend.LockerPoint._setting_field', ['field' => $field])
                                @endforeach
                                <div class="rounded border border-dashed p-4 bg-white">
                                    <div class="fw-semibold mb-1">Pagina pubblica locker point</div>
                                    <a href="{{ $publicIntakeUrl }}" target="_blank" class="text-primary">{{ $publicIntakeUrl }}</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border rounded bg-light mb-4" id="notifiche">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLockerNotifiche">
                                Notifiche
                            </button>
                        </h2>
                        <div id="collapseLockerNotifiche" class="accordion-collapse collapse" data-bs-parent="#accordionLockerPoint">
                            <div class="accordion-body pt-4">
                                @foreach($notificationFields->whereIn('name', ['locker_notify_staff', 'locker_staff_notification_email']) as $field)
                                    @include('Backend.LockerPoint._setting_field', ['field' => $field])
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border rounded bg-light" id="api">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLockerApi">
                                Integrazione API REST
                            </button>
                        </h2>
                        <div id="collapseLockerApi" class="accordion-collapse collapse" data-bs-parent="#accordionLockerPoint">
                            <div class="accordion-body pt-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Base URL API pubblica</label>
                                        <input type="text" readonly class="form-control form-control-solid" value="{{ url('/api/public/locker-point') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Header autenticazione</label>
                                        <input type="text" readonly class="form-control form-control-solid" value="x-api-key">
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-warning mb-0">
                                            La chiave API è configurata in <code>.env</code> come <code>LOCKER_API_KEY</code>.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 d-flex flex-wrap gap-3">
                    <button type="submit" class="btn btn-primary">Salva impostazioni</button>
                    <a href="{{ action([$controller, 'stationsIndex']) }}" class="btn btn-light">Gestisci postazioni agenti / API</a>
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
