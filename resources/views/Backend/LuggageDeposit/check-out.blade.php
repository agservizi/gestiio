@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2 py-1">
        <a href="{{ action([$controller,'index']) }}" class="btn btn-sm btn-light">Elenco</a>
    </div>
@endsection

@section('content')
    <div class="card mb-6">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Codice deposito</label>
                    <input type="text" name="code" class="form-control form-control-solid" placeholder="LB-XXXXXX" value="{{ request('code') }}">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100">Cerca</button>
                </div>
            </form>
        </div>
    </div>

    @if($deposit)
        @include('Backend.LuggageDeposit.partials.deposit-summary', ['deposit' => $deposit])
        @if($preview)
            <div class="alert alert-success mt-5 fs-5">Importo da incassare: <strong>€ {{ number_format($preview['total'], 2, ',', '.') }}</strong> ({{ $preview['days'] }} giorno/i)</div>
        @endif
        @if($deposit->status->value === 'CHECK_IN')
            <div class="card mt-5">
                <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pickupMobileModal-{{ $deposit->id }}">
                        Ritiro mobile (QR cliente + scan tag)
                    </button>
                    @include('Backend.LuggageDeposit.partials.pickup-mobile-qr-modal', ['deposit' => $deposit])
                    <form method="post" action="{{ action([$controller,'action'], $deposit->id) }}" class="d-flex flex-wrap gap-2 align-items-center">@csrf
                        <input type="hidden" name="action" value="check-out">
                        <select name="paymentMethod" class="form-select form-select-solid w-auto">
                            <option>Contanti</option><option>Carta</option><option>Bonifico</option>
                        </select>
                        <button class="btn btn-success">Conferma check-out</button>
                    </form>
                </div>
            </div>
        @endif
    @endif

    <div class="card mt-8">
        <div class="card-header"><h3 class="card-title">Depositi in custodia</h3></div>
        <div class="card-body pt-0">
            @forelse($attivi as $item)
                <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                    <div><strong>{{ $item->code }}</strong> — {{ $item->customer_name }}</div>
                    <a href="{{ action([$controller,'checkOutPage'], ['code'=>$item->code]) }}" class="btn btn-sm btn-success">Check-out</a>
                </div>
            @empty
                <p class="text-muted py-5 mb-0">Nessun deposito in custodia.</p>
            @endforelse
        </div>
    </div>
@endsection
