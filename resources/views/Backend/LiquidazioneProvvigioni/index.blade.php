@extends('Backend._layout._main')

@section('toolbar')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a class="btn btn-sm btn-light" href="{{ action([\App\Http\Controllers\Backend\LiquidazioneProvvigioniController::class, 'report'], ['anno' => $anno, 'mese' => $mese]) }}">Report</a>
        <a class="btn btn-sm btn-light-primary" href="{{ action([\App\Http\Controllers\Backend\LiquidazioneProvvigioniController::class, 'exportExcel'], ['anno' => $anno, 'mese' => $mese]) }}">Excel</a>
        <form method="post" action="{{ action([\App\Http\Controllers\Backend\LiquidazioneProvvigioniController::class, 'generaTutte']) }}" class="d-inline">
            @csrf
            <input type="hidden" name="anno" value="{{ $anno }}"/>
            <input type="hidden" name="mese" value="{{ $mese }}"/>
            <button type="button" class="btn btn-sm btn-primary" onclick="return gestiioAsk(this, 'Generare proforma per tutti gli affiliati del mese?')">Genera tutte</button>
        </form>
        <button type="button" class="btn btn-sm btn-light-success" data-bs-toggle="modal" data-bs-target="#modalBatchPagate" id="btnBatchPagate" disabled>
            Segna pagate selezionate
        </button>
    </div>
@endsection

@section('content')
    @include('Backend._components.alertMessage')

    <form method="get" class="card mb-5">
        <div class="card-body py-4 d-flex flex-wrap align-items-end gap-4">
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
            <div class="text-muted fs-7 ms-auto">{{ $riepilogo['agenti'] }} affiliati · previsto {{ importo($riepilogo['previsto'], true) }}</div>
        </div>
    </form>

    <div class="row g-5 mb-5">
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted fs-8 text-uppercase">Previsto</div>
                <div class="fs-2 fw-bold text-gray-900">{{ importo($riepilogo['previsto'], true) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted fs-8 text-uppercase">Liquidabile</div>
                <div class="fs-2 fw-bold text-primary">{{ importo($riepilogo['liquidabile'], true) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted fs-8 text-uppercase">Pagato</div>
                <div class="fs-2 fw-bold text-success">{{ importo($riepilogo['pagato'], true) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted fs-8 text-uppercase">Proforma da pagare</div>
                <div class="fs-2 fw-bold">{{ $riepilogo['proforma_da_pagare'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-row-dashed align-middle gs-0 gy-3">
                <thead>
                <tr class="fw-bold text-muted text-uppercase fs-8">
                    <th class="w-40px"><input type="checkbox" class="form-check-input" id="checkAllProforma"/></th>
                    <th>Affiliato</th>
                    <th class="text-end">Telefonia</th>
                    <th class="text-end">Energia</th>
                    <th class="text-end">Gare</th>
                    <th class="text-end">Totale</th>
                    <th class="text-center">Da liq. / Pagati</th>
                    <th>Proforma</th>
                    <th class="text-end">Azioni</th>
                </tr>
                </thead>
                <tbody>
                @forelse($righe as $r)
                    <tr>
                        <td>
                            @if($r->proforma_id && $r->proforma_status !== 'pagata')
                                <input type="checkbox" class="form-check-input js-proforma-check" value="{{ $r->proforma_id }}"/>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-gray-900">{{ $r->nominativo }}</div>
                            <div class="text-muted fs-8">{{ $r->email }}</div>
                        </td>
                        <td class="text-end">{{ importo($r->importo_telefonia, true) }}</td>
                        <td class="text-end">{{ importo($r->importo_energia, true) }}</td>
                        <td class="text-end">{{ importo($r->importo_gare ?? 0, true) }}</td>
                        <td class="text-end fw-bold">{{ importo($r->importo_totale, true) }}</td>
                        <td class="text-center">{{ $r->contratti_da_liquidare }} / {{ $r->contratti_pagati }}</td>
                        <td>
                            @if($r->proforma_id)
                                <a href="{{ action([\App\Http\Controllers\Backend\FatturaProformaController::class, 'show'], $r->proforma_id) }}" class="badge {{ $r->proforma_badge }} text-hover-primary">
                                    #{{ $r->proforma_numero }} · {{ $r->proforma_label }}
                                </a>
                            @else
                                <span class="badge badge-light">Nessuna</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if($r->produzione_id)
                                <a class="btn btn-sm btn-light" href="{{ action([\App\Http\Controllers\Backend\ProduzioneOperatoreController::class, 'show'], $r->produzione_id) }}">Produzione</a>
                            @endif
                            @if($r->produzione_id && ! $r->proforma_id && $r->importo_totale > 0)
                                <form method="post" action="{{ action([\App\Http\Controllers\Backend\LiquidazioneProvvigioniController::class, 'generaSingola'], $r->produzione_id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light-primary">Genera</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-10">Nessun affiliato con produzione o contratti in questo mese.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalBatchPagate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="{{ action([\App\Http\Controllers\Backend\LiquidazioneProvvigioniController::class, 'segnaPagateBatch']) }}" class="modal-content" id="formBatchPagate">
                @csrf
                <input type="hidden" name="anno" value="{{ $anno }}"/>
                <input type="hidden" name="mese" value="{{ $mese }}"/>
                <div id="batchProformaIds"></div>
                <div class="modal-header">
                    <h5 class="modal-title">Segna proforma selezionate come pagate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-5">
                        <label class="form-label required">Data pagamento</label>
                        <input type="date" name="data_pagamento" class="form-control form-control-solid" required value="{{ now()->toDateString() }}"/>
                    </div>
                    <div>
                        <label class="form-label required">Riferimento (CRO / causale)</label>
                        <input type="text" name="riferimento_pagamento" class="form-control form-control-solid" required maxlength="255" placeholder="CRO o causale bonifico"/>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Conferma</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('customScript')
<script>
(function () {
    var checks = document.querySelectorAll('.js-proforma-check');
    var all = document.getElementById('checkAllProforma');
    var btn = document.getElementById('btnBatchPagate');
    var box = document.getElementById('batchProformaIds');
    function sync() {
        var selected = Array.prototype.filter.call(checks, function (c) { return c.checked; });
        btn.disabled = selected.length === 0;
        box.innerHTML = selected.map(function (c) {
            return '<input type="hidden" name="proforma_ids[]" value="' + c.value + '"/>';
        }).join('');
    }
    checks.forEach(function (c) { c.addEventListener('change', sync); });
    if (all) {
        all.addEventListener('change', function () {
            checks.forEach(function (c) { c.checked = all.checked; });
            sync();
        });
    }
    sync();
})();
</script>
@endpush
