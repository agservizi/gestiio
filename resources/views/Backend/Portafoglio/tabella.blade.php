@if($records->count())
    <div class="table-responsive">
        <table class="table table-row-bordered wallet-table" id="tabella-elenco">
            <thead>
            <tr class="fw-bolder">
                <th>Data</th>
                <th>Portafoglio</th>
                <th class="text-end">Importo</th>
                <th>Descrizione</th>
            </tr>
            </thead>
            <tbody>
            @foreach($records as $record)
                @php
                    $tipo = \App\Enums\TipiPortafoglioEnum::tryFrom($record->portafoglio);
                    $badge = match($record->portafoglio) {
                        \App\Enums\TipiPortafoglioEnum::SPEDIZIONI->value => 'badge-light-success',
                        \App\Enums\TipiPortafoglioEnum::VISURE->value => 'badge-light-info',
                        default => 'badge-light-primary',
                    };
                @endphp
                <tr>
                    <td class="wallet-date">{{$record->created_at->format('d/m/Y')}}</td>
                    <td>
                        <span class="badge {{$badge}}">{{$tipo?->testo() ?? 'Portafoglio'}}</span>
                    </td>
                    <td class="text-end wallet-amount">{{importo($record->importo, true)}}</td>
                    <td class="wallet-description">{{$record->descrizione}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="wallet-empty">
        <div class="fw-bold text-gray-800 mb-1">Nessun movimento trovato</div>
        <div>Quando ricarichi o usi il plafond, i movimenti compaiono qui.</div>
    </div>
@endif
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator && $records->hasPages())
    <div class="wallet-pagination w-100 text-center">
        {{$records->links()}}
    </div>
@endif
