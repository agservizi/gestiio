<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">{{ $deposit->code }}</h3>
        <span class="badge {{ $deposit->status->badgeClass() }}">{{ $deposit->status->label() }}</span>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6"><div class="text-muted fs-7">Cliente</div><div class="fw-bold">{{ $deposit->customer_name }}</div></div>
            <div class="col-md-6"><div class="text-muted fs-7">Borse</div><div class="fw-bold">{{ $deposit->bag_count }}</div></div>
            <div class="col-md-6"><div class="text-muted fs-7">Data deposito</div><div class="fw-bold">{{ $deposit->booking_date?->format('d/m/Y') }}</div></div>
            <div class="col-md-6"><div class="text-muted fs-7">Check-out previsto</div><div class="fw-bold">{{ $deposit->expected_check_out?->format('d/m/Y') ?? '—' }}</div></div>
            <div class="col-md-6"><div class="text-muted fs-7">Fonte</div><div class="fw-bold">{{ $deposit->source === 'PORTALE' ? 'Online' : 'Sportello' }}</div></div>
        </div>
    </div>
</div>
