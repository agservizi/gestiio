<div class="table-responsive">
    <table class="table table-row-bordered align-middle gy-4" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th>Codice</th>
            <th>Cliente</th>
            <th>Borse</th>
            <th>Data</th>
            <th>Stato</th>
            <th>Fonte</th>
            <th class="text-end">Azioni</th>
        </tr>
        </thead>
        <tbody>
        @forelse($records as $record)
            @php
                $canCancel = ! in_array($record->status->value, ['ANNULLATO', 'COMPLETATO'], true);
            @endphp
            <tr>
                <td><a href="{{ action([$controller,'show'], $record->id) }}" class="fw-bold">{{ $record->code }}</a></td>
                <td>
                    <div>{{ $record->customer_name }}</div>
                    <div class="text-muted fs-7">{{ $record->customer_email }}</div>
                </td>
                <td>{{ $record->bag_count }}</td>
                <td>{{ $record->booking_date?->format('d/m/Y') }}</td>
                <td><span class="badge {{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span></td>
                <td>{{ $record->source === 'PORTALE' ? 'Online' : 'Sportello' }}</td>
                <td class="text-end text-nowrap">
                    <a href="{{ action([$controller,'show'], $record->id) }}" class="btn btn-icon btn-sm btn-light btn-active-light-primary" title="Dettaglio">
                        <i class="bi bi-eye fs-5"></i>
                    </a>
                    @if($canCancel || auth()->user()?->can('delete', $record))
                        <button type="button" class="btn btn-icon btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" title="Azioni">
                            <i class="bi bi-three-dots-vertical fs-5"></i>
                        </button>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3" data-kt-menu="true">
                            <div class="menu-item px-3">
                                <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">Azioni</div>
                            </div>
                            @if($canCancel)
                                @if($record->status->value === 'PRENOTATO')
                                    <div class="menu-item px-3">
                                        <form method="post" action="{{ action([$controller,'action'], $record->id) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="no-show">
                                            <button type="button" class="menu-link px-3 w-100 border-0 bg-transparent text-start text-danger" onclick="return gestiioAsk(this, 'Segnare {{ $record->code }} come no-show?', true)">
                                                <i class="bi bi-person-x me-2"></i>No-show
                                            </button>
                                        </form>
                                    </div>
                                @endif
                                <div class="menu-item px-3">
                                    <form method="post" action="{{ action([$controller,'action'], $record->id) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="button" class="menu-link px-3 w-100 border-0 bg-transparent text-start text-warning" onclick="return gestiioAsk(this, 'Annullare la prenotazione {{ $record->code }}?')">
                                            <i class="bi bi-x-circle me-2"></i>Annulla prenotazione
                                        </button>
                                    </form>
                                </div>
                            @endif
                            @can('delete', $record)
                                <div class="menu-item px-3">
                                    <form method="post" action="{{ action([$controller,'action'], $record->id) }}?view={{ request('view', 'oggi') }}">
                                        @csrf
                                        <input type="hidden" name="action" value="delete">
                                        <button type="button" class="menu-link px-3 w-100 border-0 bg-transparent text-start text-danger" onclick="return gestiioAsk(this, 'Eliminare definitivamente {{ $record->code }} dal database?', true)">
                                            <i class="bi bi-trash me-2"></i>Elimina dal database
                                        </button>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-10">Nessun deposito trovato.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="w-100 text-center">{{ $records->links() }}</div>
@endif
