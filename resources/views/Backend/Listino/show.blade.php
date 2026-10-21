@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center gap-2">
        <a class="btn btn-sm btn-light" href="{{ action([\App\Http\Controllers\Backend\GaraController::class, 'index']) }}">Gare</a>
    </div>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body py-4">
            <div class="text-muted fs-7">
                Qui configuri le <strong>fasce di provvigione affiliato</strong> (importo per contratto a volume).
                Il canone mensile mostrato è il <strong>prezzo al cliente</strong> (da Offerte), non il premio gara.
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fw-bold text-gray-600 fs-6 gy-5">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th>Offerta / Tipo contratto</th>
                        <th>Canone cliente</th>
                        <th>Attivo</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($records as $tipo)
                        @php($offerta = $tipo->telefoniaOfferta)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px symbol-2by3 me-3">
                                        @if(optional($tipo->gestore)->logo)
                                            <img src="{{ $tipo->gestore->immagineLogo() }}" alt="">
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-gray-900">
                                            {{ $offerta?->nome ?: $tipo->nome }}
                                        </div>
                                        <div class="text-muted fs-8">{{ optional($tipo->gestore)->nome }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($offerta && $offerta->prezzo_mese !== null)
                                    {{ number_format((float) $offerta->prezzo_mese, 2, ',', '.') }} €/mese
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if(optional($tipo->gestore)->attivo && $tipo->attivo)
                                    <span class="badge badge-light-success fw-bolder">Abilitato</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a data-target="kt_modal" data-toggle="modal-ajax"
                                   class="btn btn-sm btn-light btn-active-light-primary"
                                   href="{{ action([\App\Http\Controllers\Backend\FasciaTipoContrattoController::class, 'edit'], [$record->id, $tipo->id]) }}">
                                    Modifica fasce
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
