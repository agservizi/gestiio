@php
    $inizioMese = now()->startOfMonth();
@endphp
<div class="table-responsive">
    <table class="table table-row-bordered align-middle gy-4" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th>Periodo</th>
            <th>Agente</th>
            <th class="text-end">Telefonia</th>
            <th class="text-end">Energia</th>
            <th class="text-end d-none d-lg-table-cell">Altri</th>
            <th class="text-end">Totale</th>
            <th>Stato proforma</th>
            <th class="text-end">Azioni</th>
        </tr>
        </thead>
        <tbody>
        @forelse($records as $record)
            @php
                $altri = (float)($record->importo_servizi_finanziari ?? 0)
                    + (float)($record->importo_segnalazioni ?? 0)
                    + (float)($record->importo_attivazioni_sim ?? 0);
                $chiudibile = \Carbon\Carbon::createFromDate($record->anno, $record->mese, 1)->lessThan($inizioMese);
                $haProforma = (bool) $record->fattura_proforma_id;
            @endphp
            <tr>
                <td>{{ str_pad($record->mese, 2, '0', STR_PAD_LEFT) }}/{{ $record->anno }}</td>
                <td>
                    @can('admin')
                        <a href="{{ action([\App\Http\Controllers\Backend\AgenteController::class, 'show'], $record->user_id) }}" class="text-gray-800 text-hover-primary">
                            {{ optional($record->agente)->nominativo() }}
                        </a>
                    @else
                        {{ optional($record->agente)->nominativo() }}
                    @endcan
                </td>
                <td class="text-end">{{ importo($record->importo_ordini) }}</td>
                <td class="text-end">{{ importo($record->importo_contratti_energia) }}</td>
                <td class="text-end d-none d-lg-table-cell">{{ importo($altri) }}</td>
                <td class="text-end fw-bold">{{ importo($record->importo_totale) }}</td>
                <td>
                    @if($haProforma && $record->fatturaProforma)
                        <span class="badge {{ $record->fatturaProforma->statusBadgeClass() }}">
                            #{{ $record->fatturaProforma->numero }} · {{ $record->fatturaProforma->statusLabel() }}
                        </span>
                    @elseif($chiudibile && (float)$record->importo_totale > 0)
                        <span class="badge badge-light-warning">Chiudibile</span>
                    @else
                        <span class="badge badge-light">Mese aperto</span>
                    @endif
                </td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-light" href="{{ action([\App\Http\Controllers\Backend\ProduzioneOperatoreController::class, 'show'], $record->id) }}" aria-label="Dettaglio">Dettaglio</a>
                    @can('admin')
                        <form method="post" action="{{ action([\App\Http\Controllers\Backend\ProduzioneOperatoreController::class, 'ricalcola'], $record->id) }}" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-sm btn-light" onclick="return gestiioAsk(this, 'Ricalcolare la produzione di questo periodo?')" aria-label="Ricalcola">Ricalcola</button>
                        </form>
                    @endcan
                    @if($haProforma)
                        <a class="btn btn-sm btn-light-primary" href="{{ action([\App\Http\Controllers\Backend\FatturaProformaController::class, 'show'], $record->fattura_proforma_id) }}">Vedi</a>
                        <a class="btn btn-sm btn-light" href="{{ action([\App\Http\Controllers\Backend\FatturaProformaController::class, 'pdf'], $record->fattura_proforma_id) }}" target="_blank" rel="noopener">PDF</a>
                    @elseif($chiudibile && (float)$record->importo_totale > 0)
                        @can('admin')
                            <button type="button" class="btn btn-sm btn-primary"
                                    onclick="return apriPreviewProforma('{{ action([\App\Http\Controllers\Backend\ProduzioneOperatoreController::class, 'previewProforma'], $record->id) }}')">
                                Crea proforma
                            </button>
                        @endcan
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-10">
                    Nessuna produzione per i filtri selezionati
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="w-100 text-center">
        {{ $records->links() }}
    </div>
@endif
