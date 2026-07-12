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
        @if($deposit && in_array($deposit->status->value, ['PRENOTATO', 'NO_SHOW']))
            <div class="card mt-5">
                <div class="card-body d-flex flex-wrap gap-2">
                    <form method="post" action="{{ action([$controller,'action'], $deposit->id) }}">@csrf
                        <input type="hidden" name="action" value="check-in">
                        <button class="btn btn-warning">Conferma check-in e genera tag</button>
                    </form>
                    <a href="{{ action([$controller,'pdfTags'], $deposit->id) }}" class="btn btn-light">Stampa tag</a>
                </div>
            </div>
        @endif
    @endif

    <div class="card mt-8">
        <div class="card-header"><h3 class="card-title">Prenotati in attesa</h3></div>
        <div class="card-body pt-0">
            @forelse($prenotati as $item)
                <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                    <div><strong>{{ $item->code }}</strong> — {{ $item->customer_name }} ({{ $item->bag_count }} borse)</div>
                    <a href="{{ action([$controller,'checkInPage'], ['code'=>$item->code]) }}" class="btn btn-sm btn-light">Seleziona</a>
                </div>
            @empty
                <p class="text-muted py-5 mb-0">Nessuna prenotazione in attesa.</p>
            @endforelse
        </div>
    </div>
@endsection
