@extends('Frontend.Marketing._layout')

@section('content')
    <main>
        <section class="band">
            <div class="section-inner">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="feature-card text-center">
                            <div class="eyebrow mb-3">Confermata</div>
                            <h1 class="mb-2">Prenotazione confermata</h1>
                            <div class="fs-1 fw-bolder text-primary mb-4">{{ $deposit->code }}</div>
                            <p class="text-muted mb-6">Presentati in agenzia con questo codice o il QR code. Conserva la conferma per il ritiro.</p>
                            <div class="mb-6 d-inline-block">{!! $qrSvg !!}</div>
                            <p class="text-muted mb-0">{{ $deposit->customer_name }} · {{ $deposit->bag_count }} borse · {{ $deposit->booking_date->format('d/m/Y') }}</p>
                            <a href="{{ $deposit->verifyUrl() }}" class="btn btn-light-primary mt-6">Verifica stato prenotazione</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
