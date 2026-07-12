@extends('Backend._layout._main')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('print_documents'))
        <div class="alert alert-primary d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>Stampa i tag bagaglio e fai firmare il documento di accettazione al cliente.</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ action([$controller,'pdfTags'], $deposit->id) }}" class="btn btn-sm btn-primary" target="_blank">Stampa tag bagagli</a>
                <a href="{{ action([$controller,'pdfAgreement'], [$deposit->id, 'v' => $deposit->updated_at?->timestamp]) }}" class="btn btn-sm btn-light-primary" target="_blank">Documento firma cliente</a>
            </div>
        </div>
    @endif
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $deposit->code }}</h3>
            <div class="card-toolbar">
                <span class="badge {{ $deposit->status->badgeClass() }} me-2">{{ $deposit->status->label() }}</span>
                <a class="btn btn-sm btn-light" href="{{ action([$controller,'index']) }}">Torna elenco</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-6">
                <div class="col-md-6"><div class="text-muted fs-7">Cliente</div><div class="fw-bold fs-5">{{ $deposit->customer_name }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Email</div><div class="fw-bold">{{ $deposit->customer_email ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Telefono</div><div class="fw-bold">{{ $deposit->customer_phone ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Borse</div><div class="fw-bold">{{ $deposit->bag_count }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Data prenotazione</div><div class="fw-bold">{{ $deposit->booking_date?->format('d/m/Y') }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Tariffa/giorno</div><div class="fw-bold">€ {{ number_format($deposit->daily_rate, 2, ',', '.') }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Check-in</div><div class="fw-bold">{{ $deposit->checked_in_at?->format('d/m/Y H:i') ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Check-out</div><div class="fw-bold">{{ $deposit->checked_out_at?->format('d/m/Y H:i') ?: '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Importo totale</div><div class="fw-bold">{{ $deposit->total_amount ? '€ '.number_format($deposit->total_amount, 2, ',', '.') : '—' }}</div></div>
                <div class="col-md-6"><div class="text-muted fs-7">Fonte</div><div class="fw-bold">{{ $deposit->source === 'PORTALE' ? 'Prenotazione online' : 'Sportello' }}</div></div>
                @if($deposit->notes)
                    <div class="col-12"><div class="text-muted fs-7">Note</div><div class="fw-bold">{{ $deposit->notes }}</div></div>
                @endif
                @if($deposit->bag_tags)
                    <div class="col-12"><div class="text-muted fs-7">Tag bagagli</div><div class="fw-bold">{{ implode(', ', $deposit->bag_tags) }}</div></div>
                @endif
            </div>

            @if($pricingPreview)
                <div class="alert alert-info mt-6">Anteprima check-out: {{ $pricingPreview['days'] }} giorno/i · € {{ number_format($pricingPreview['total'], 2, ',', '.') }}</div>
            @endif

            <div class="d-flex flex-wrap gap-2 mt-6">
                @if(in_array($deposit->status->value, ['PRENOTATO','NO_SHOW']))
                    <form method="post" action="{{ action([$controller,'action'], $deposit->id) }}">@csrf
                        <input type="hidden" name="action" value="check-in">
                        <button class="btn btn-warning">Check-in</button>
                    </form>
                    @if($deposit->status->value === 'PRENOTATO')
                        <form method="post" action="{{ action([$controller,'action'], $deposit->id) }}">@csrf
                            <input type="hidden" name="action" value="no-show">
                            <button class="btn btn-light-danger" onclick="return confirm('Segnare come no-show?')">No-show</button>
                        </form>
                    @endif
                    <form method="post" action="{{ action([$controller,'action'], $deposit->id) }}">@csrf
                        <input type="hidden" name="action" value="cancel">
                        <button class="btn btn-light" onclick="return confirm('Annullare?')">Annulla</button>
                    </form>
                @endif
                @if($deposit->status->value === 'CHECK_IN')
                    <form method="post" action="{{ action([$controller,'action'], $deposit->id) }}" class="d-flex gap-2 align-items-center">
                        @csrf
                        <input type="hidden" name="action" value="check-out">
                        <select name="paymentMethod" class="form-select form-select-sm form-select-solid w-auto">
                            <option>Contanti</option><option>Carta</option><option>Bonifico</option>
                        </select>
                        <button class="btn btn-success">Check-out</button>
                    </form>
                    <a href="{{ $deposit->pickupUrl() }}" class="btn btn-primary" target="_blank">Ritiro mobile (scan QR cliente)</a>
                @endif
                <a href="{{ action([$controller,'pdfTags'], $deposit->id) }}" class="btn btn-light-primary" target="_blank">PDF Tag bagagli</a>
                <a href="{{ action([$controller,'pdfAgreement'], [$deposit->id, 'v' => $deposit->updated_at?->timestamp]) }}" class="btn btn-light" target="_blank">Documento firma cliente</a>
                @if($deposit->status->value === 'COMPLETATO')
                    <a href="{{ action([$controller,'pdfReceipt'], $deposit->id) }}" class="btn btn-light">PDF Ricevuta</a>
                @endif
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-header"><h3 class="card-title">QR & Verifica</h3></div>
        <div class="card-body text-center">
            <div class="mb-3">{!! \App\Http\Support\LuggageQrCode::svg($deposit->verifyUrl(), 180) !!}</div>
            <div class="text-muted fs-7 break-all">{{ $deposit->verifyUrl() }}</div>
        </div>
    </div>
@endsection
