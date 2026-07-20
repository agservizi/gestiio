<div class="table-responsive">
    <table class="table table-row-bordered align-middle gy-4" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th>Numero</th>
            <th>Data</th>
            <th class="d-none d-md-table-cell">Periodo</th>
            <th>Intestazione</th>
            <th class="text-end">Totale</th>
            <th>Stato</th>
            <th class="text-end">Azioni</th>
        </tr>
        </thead>
        <tbody>
        @forelse($records as $record)
            <tr>
                <td>#{{ $record->numero }}</td>
                <td>{{ $record->data->format('d/m/Y') }}</td>
                <td class="d-none d-md-table-cell">{{ $record->periodoLabel() ?? '—' }}</td>
                <td>{{ optional($record->intestazione)->denominazione }}</td>
                <td class="text-end fw-bold">{{ importo($record->totale_con_iva) }}</td>
                <td>
                    <span class="badge {{ $record->statusBadgeClass() }}">{{ $record->statusLabel() }}</span>
                </td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-light-primary" href="{{ action([$controller, 'show'], $record->id) }}">Vedi</a>
                    <a class="btn btn-sm btn-light" href="{{ action([$controller, 'pdf'], $record->id) }}" target="_blank" rel="noopener" aria-label="Scarica PDF">PDF</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-10">Nessuna proforma</td>
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
