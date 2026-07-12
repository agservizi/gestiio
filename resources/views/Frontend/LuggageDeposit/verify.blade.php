@extends('Frontend.Marketing._layout')

@section('content')
    <main>
        <section class="band band-soft">
            <div class="section-inner">
                <div class="row justify-content-center">
                    <div class="col-lg-5">
                        <div class="feature-card">
                            <span class="badge badge-light-primary mb-3">{{ $deposit->status->label() }}</span>
                            <h1 class="mb-1">{{ $deposit->code }}</h1>
                            <p class="text-muted mb-6">{{ $deposit->customer_name }}</p>
                            <div class="row g-4">
                                <div class="col-6"><div class="text-muted fs-7">Borse</div><div class="fw-bold">{{ $deposit->bag_count }}</div></div>
                                <div class="col-6"><div class="text-muted fs-7">Data</div><div class="fw-bold">{{ $deposit->booking_date->format('d/m/Y') }}</div></div>
                                @if($deposit->checked_in_at)
                                    <div class="col-6"><div class="text-muted fs-7">Check-in</div><div class="fw-bold">{{ $deposit->checked_in_at->format('d/m/Y H:i') }}</div></div>
                                @endif
                                @if($deposit->checked_out_at)
                                    <div class="col-6"><div class="text-muted fs-7">Check-out</div><div class="fw-bold">{{ $deposit->checked_out_at->format('d/m/Y H:i') }}</div></div>
                                @endif
                                @if($deposit->total_amount)
                                    <div class="col-6"><div class="text-muted fs-7">Totale</div><div class="fw-bold">€ {{ number_format($deposit->total_amount, 2, ',', '.') }}</div></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
