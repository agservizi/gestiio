@extends('Backend._layout._main')

@section('toolbar')
    <a class="btn btn-sm btn-light" href="{{ action([\App\Http\Controllers\Backend\LiquidazioneProvvigioniController::class, 'index'], ['anno' => $anno, 'mese' => $mese]) }}">Torna a liquidazione</a>
@endsection

@section('content')
    <form method="get" class="card mb-5">
        <div class="card-body py-4 d-flex flex-wrap gap-4 align-items-end">
            <div>
                <label class="form-label">Anno</label>
                <select name="anno" class="form-select form-select-solid w-120px" onchange="this.form.submit()">
                    @foreach($elencoAnni as $a)
                        <option value="{{ $a }}" @selected($a == $anno)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Mese</label>
                <select name="mese" class="form-select form-select-solid w-150px" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($m == $mese)>{{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
        </div>
    </form>

    <div class="row g-5 mb-7">
        <div class="col-md-4">
            <div class="card border border-gray-300"><div class="card-body">
                <div class="text-muted text-uppercase fs-8">Previsto (produzione)</div>
                <div class="fs-1 fw-bolder">{{ importo($riepilogo['previsto'], true) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border border-primary"><div class="card-body">
                <div class="text-muted text-uppercase fs-8">Liquidabile (non pagato)</div>
                <div class="fs-1 fw-bolder text-primary">{{ importo($riepilogo['liquidabile'], true) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border border-success"><div class="card-body">
                <div class="text-muted text-uppercase fs-8">Pagato</div>
                <div class="fs-1 fw-bolder text-success">{{ importo($riepilogo['pagato'], true) }}</div>
                <div class="text-muted fs-7 mt-1">Delta: {{ importo($riepilogo['previsto'] - $riepilogo['pagato'], true) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Dettaglio affiliati {{ str_pad($mese, 2, '0', STR_PAD_LEFT) }}/{{ $anno }}</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-row-dashed gs-0 gy-3">
                <thead>
                <tr class="text-muted text-uppercase fs-8 fw-bold">
                    <th>Affiliato</th>
                    <th class="text-end">Previsto</th>
                    <th>Stato</th>
                    <th class="text-end">Contratti pagati</th>
                </tr>
                </thead>
                <tbody>
                @foreach($righe as $r)
                    <tr>
                        <td class="fw-semibold">{{ $r->nominativo }}</td>
                        <td class="text-end">{{ importo($r->importo_totale, true) }}</td>
                        <td><span class="badge {{ $r->proforma_badge }}">{{ $r->proforma_label }}</span></td>
                        <td class="text-end">{{ $r->contratti_pagati }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
