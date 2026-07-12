@extends('Frontend.Marketing._layout')

@section('content')
    <main>
        <section class="band band-soft">
            <div class="section-inner">
                <div class="row g-10 align-items-start">
                    <div class="col-lg-6">
                        <div class="eyebrow">Deposito bagagli</div>
                        <h1>Prenota il deposito bagagli online</h1>
                        <p class="lead">Tariffa € {{ number_format($settings->daily_rate, 2, ',', '.') }} al giorno per bagaglio. Conferma immediata con codice e QR per il ritiro.</p>
                        @if($bookingInstructions)
                            <div class="alert alert-light border mt-4 mb-0">{!! nl2br(e($bookingInstructions)) !!}</div>
                        @endif
                        <div class="feature-grid mt-6">
                            <article class="feature-card">
                                <strong id="kpi-rate">€ {{ number_format($settings->daily_rate, 2, ',', '.') }}</strong>
                                <p>Tariffa giornaliera</p>
                            </article>
                            <article class="feature-card">
                                <strong id="kpi-available">{{ $availability['available_bags'] }}</strong>
                                <p>Posti disponibili oggi</p>
                            </article>
                            <article class="feature-card">
                                <strong>{{ $settings->max_bags_per_booking }}</strong>
                                <p>Max borse per prenotazione</p>
                            </article>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="feature-card">
                            @if(!$onlineBookingEnabled)
                                <h2 class="mb-4">Prenotazioni online sospese</h2>
                                <p class="text-muted mb-0">Al momento non è possibile prenotare online. Contatta l'agenzia per assistenza o presentati direttamente in sportello.</p>
                            @else
                                <h2 class="mb-6">Dati prenotazione</h2>
                                <form id="booking-form" method="post" action="{{ url('/deposito-bagagli/prenota') }}">
                                    @csrf
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Nome *</label>
                                            <input name="customerName" class="form-control form-control-solid" value="{{ old('customerName') }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Email</label>
                                            <input type="email" name="customerEmail" class="form-control form-control-solid" value="{{ old('customerEmail') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Telefono</label>
                                            <input name="customerPhone" class="form-control form-control-solid" value="{{ old('customerPhone') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Numero borse</label>
                                            <input type="number" name="bagCount" min="1" max="{{ $settings->max_bags_per_booking }}" value="{{ old('bagCount', 1) }}" class="form-control form-control-solid">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Data deposito *</label>
                                            <input type="date" name="bookingDate" id="bookingDate" required min="{{ today()->toDateString() }}" value="{{ old('bookingDate') }}" class="form-control form-control-solid">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Ritiro previsto</label>
                                            <input type="date" name="expectedCheckOut" id="expectedCheckOut" min="{{ today()->toDateString() }}" value="{{ old('expectedCheckOut') }}" class="form-control form-control-solid">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Note</label>
                                            <textarea name="notes" rows="3" class="form-control form-control-solid" placeholder="Es. valigia grande, zaino">{{ old('notes') }}</textarea>
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
    @if($onlineBookingEnabled)
    <script>
    document.getElementById('bookingDate')?.addEventListener('change', async (e) => {
        const date = e.target.value;
        if (!date) return;
        const res = await fetch(@json(url('/deposito-bagagli/disponibilita')) + '?date=' + encodeURIComponent(date), {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (json.success) document.getElementById('kpi-available').textContent = json.data.available_bags;
    });
    </script>
    @endif
@endsection
