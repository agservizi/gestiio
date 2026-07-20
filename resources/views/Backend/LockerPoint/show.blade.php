@extends('Backend._layout._main')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('print_label'))
        <div class="alert alert-primary d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>Stampa l'etichetta con barcode per il pacco.</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ action([$controller, 'pdfLabel'], $package->id) }}" class="btn btn-sm btn-primary" target="_blank">Stampa etichetta</a>
            </div>
        </div>
    @endif
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $package->code }}</h3>
            <div class="card-toolbar">
                <span class="badge {{ $package->status->badgeClass() }} me-2">{{ $package->status->label() }}</span>
                <a class="btn btn-sm btn-light" href="{{ action([$controller, 'index']) }}">Torna elenco</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-6">
                <div class="col-md-6"><div class="text-muted fs-7">Destinatario</div><div class="fw-bold fs-5">{{ $package->recipient_name }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Email destinatario</div><div class="fw-bold">{{ $package->recipient_email ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Telefono destinatario</div><div class="fw-bold">{{ $package->recipient_phone ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Mittente</div><div class="fw-bold">{{ $package->sender_name ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Telefono mittente</div><div class="fw-bold">{{ $package->sender_phone ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Corriere</div><div class="fw-bold">{{ $package->carrier ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Tracking</div><div class="fw-bold">{{ $package->tracking_code ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Ritiro previsto</div><div class="fw-bold">{{ $package->expected_pickup_date?->format('d/m/Y') }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Tariffa/giorno</div><div class="fw-bold">€ {{ number_format($package->daily_rate, 2, ',', '.') }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Accettazione in giacenza</div><div class="fw-bold">{{ $package->received_at?->format('d/m/Y H:i') ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Consegna</div><div class="fw-bold">{{ $package->delivered_at?->format('d/m/Y H:i') ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Importo totale</div><div class="fw-bold">{{ $package->total_amount ? '€ '.number_format($package->total_amount, 2, ',', '.') : '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Fonte</div><div class="fw-bold">{{ $package->source instanceof \App\Enums\LockerPackageSource ? $package->source->label() : $package->source }}</div></div>
                @if($package->notes)
                    <div class="col-12"><div class="text-muted fs-7">Note</div><div class="fw-bold">{{ $package->notes }}</div></div>
                @endif
                @if($package->photo_path)
                    <div class="col-12">
                        <div class="text-muted fs-7 mb-2">Foto accettazione</div>
                        <img src="{{ action([$controller, 'photo'], $package->id) }}" alt="Foto pacco" class="img-fluid rounded border" style="max-height:240px">
                    </div>
                @endif
            </div>

            @if($pricingPreview ?? null)
                <div class="alert alert-info mt-6">Anteprima consegna: {{ $pricingPreview['days'] }} giorno/i · € {{ number_format($pricingPreview['total'], 2, ',', '.') }}</div>
            @endif

            <div class="d-flex flex-wrap gap-2 mt-6">
                @if(in_array($package->status->value, ['PRENOTATO', 'NO_SHOW']))
                    <a href="{{ action([$controller, 'intake'], $package->id) }}" class="btn btn-warning">Accettazione in giacenza</a>
                    @if($package->status->value === 'PRENOTATO')
                        <form method="post" action="{{ action([$controller, 'action'], $package->id) }}">@csrf
                            <input type="hidden" name="action" value="no-show">
                            <button type="button" class="btn btn-light-danger" onclick="return gestiioAsk(this, 'Segnare come no-show?', true)">No-show</button>
                        </form>
                    @endif
                    <form method="post" action="{{ action([$controller, 'action'], $package->id) }}">@csrf
                        <input type="hidden" name="action" value="cancel">
                        <button type="button" class="btn btn-light" onclick="return gestiioAsk(this, 'Confermi l\'annullamento di questo pacco?')">Annulla</button>
                    </form>
                @endif
                @if($package->status->value === 'IN_GIACENZA')
                    <form method="post" action="{{ action([$controller, 'action'], $package->id) }}" class="d-flex gap-2 align-items-center">
                        @csrf
                        <input type="hidden" name="action" value="deliver">
                        <select name="paymentMethod" class="form-select form-select-sm form-select-solid w-auto">
                            <option>Contanti</option><option>Carta</option><option>Bonifico</option>
                        </select>
                        <button class="btn btn-success">Consegna sportello</button>
                    </form>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pickupMobileModal-{{ $package->id }}">
                        Ritiro mobile (scan QR cliente)
                    </button>
                    @include('Backend.LockerPoint.partials.pickup-mobile-qr-modal', ['package' => $package])
                @endif
                <a href="{{ action([$controller, 'pdfLabel'], $package->id) }}" class="btn btn-light-primary" target="_blank">PDF Etichetta</a>
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-header"><h3 class="card-title">QR ritiro</h3></div>
        <div class="card-body text-center">
            <div class="mb-3">{!! \App\Http\Support\LuggageQrCode::svg($package->pickupUrl(), 180) !!}</div>
            <div class="text-muted fs-7 break-all">{{ $package->pickupUrl() }}</div>
        </div>
    </div>
@endsection
