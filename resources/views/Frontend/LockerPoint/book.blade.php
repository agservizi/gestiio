@extends('Frontend.Marketing._layout')

@section('content')
    <main>
        <section class="band band-soft">
            <div class="section-inner">
                <div class="row g-10 align-items-start">
                    <div class="col-lg-6">
                        <div class="eyebrow">Locker Point</div>
                        <h1>Prenota il ritiro pacco online</h1>
                        @if($station)
                            <p class="lead mb-2">{{ $station->name }}</p>
                        @endif
                        <p class="lead">Tariffa € {{ number_format($settings->daily_rate, 2, ',', '.') }} al giorno. Conferma immediata con codice e QR per il ritiro.</p>
                        @if($bookingInstructions)
                            <div class="alert alert-light border mt-4 mb-0">{!! nl2br(e($bookingInstructions)) !!}</div>
                        @endif
                        <div class="feature-grid mt-6">
                            <article class="feature-card">
                                <strong id="kpi-rate">€ {{ number_format($settings->daily_rate, 2, ',', '.') }}</strong>
                                <p>Tariffa giornaliera</p>
                            </article>
                            <article class="feature-card">
                                <strong id="kpi-available">{{ $availability['available_packages'] }}</strong>
                                <p>Posti disponibili oggi</p>
                            </article>
                            <article class="feature-card">
                                <strong>{{ $settings->max_capacity }}</strong>
                                <p>Capacità massima</p>
                            </article>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="feature-card">
                            @if(!$onlineIntakeEnabled)
                                <h2 class="mb-4">Accettazione online sospesa</h2>
                                <p class="text-muted mb-0">Al momento non è possibile prenotare online. Contatta l'agenzia per assistenza o presentati direttamente in sportello.</p>
                            @else
                                <h2 class="mb-6">Dati pacco</h2>
                                <form id="booking-form" method="post" action="{{ $bookAction ?? url('/locker-point/prenota') }}">
                                    @csrf
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Destinatario *</label>
                                            <input name="recipientName" class="form-control form-control-solid" value="{{ old('recipientName') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Email destinatario</label>
                                            <input type="email" name="recipientEmail" class="form-control form-control-solid" value="{{ old('recipientEmail') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Telefono destinatario</label>
                                            <input name="recipientPhone" class="form-control form-control-solid" value="{{ old('recipientPhone') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Mittente</label>
                                            <input name="senderName" class="form-control form-control-solid" value="{{ old('senderName') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Corriere</label>
                                            <input name="carrier" class="form-control form-control-solid" value="{{ old('carrier') }}" placeholder="Es. BRT, GLS, DHL">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Codice tracking</label>
                                            <input name="trackingCode" class="form-control form-control-solid" value="{{ old('trackingCode') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Ritiro previsto *</label>
                                            <input type="date" name="expectedPickupDate" id="expectedPickupDate" required min="{{ today()->toDateString() }}" value="{{ old('expectedPickupDate') }}" class="form-control form-control-solid">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Note</label>
                                            <textarea name="notes" rows="3" class="form-control form-control-solid" placeholder="Es. pacco fragile, dimensioni">{{ old('notes') }}</textarea>
                                        </div>
                                    </div>
                                    @if($errors->any())
                                        <div class="alert alert-danger mt-4 mb-0">{{ $errors->first() }}</div>
                                    @endif
                                    <button type="submit" class="btn btn-primary w-100 mt-6" id="submit-btn">Conferma prenotazione</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    @if($onlineIntakeEnabled)
    <script>
    document.getElementById('expectedPickupDate')?.addEventListener('change', async (e) => {
        const date = e.target.value;
        if (!date) return;
        const res = await fetch(@json($availabilityUrl ?? url('/locker-point/disponibilita')) + '?date=' + encodeURIComponent(date), {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (json.success) document.getElementById('kpi-available').textContent = json.data.available_packages;
    });
    </script>
    @endif
@endsection
