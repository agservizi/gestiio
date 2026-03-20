<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="tutti"/>
                </div>
            </th>
            <th>Data</th>
            <th>Destinatario</th>
            <th>Destinazione</th>
            <th>Tipo</th>
            <th>Pacchi</th>
            <th>Peso</th>
            <th>Esito</th>
            <th>Tracking</th>
            <th>Stato tracking</th>
            <th>Agg. tracking</th>
            <th>Agente</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr data-id="{{$record->id}}">
                <td>
                    <div class="form-check">
                        <input class="form-check-input sel" type="checkbox" value="{{$record->id}}" id="check{{$record->id}}"/>
                    </div>
                </td>
                <td>{{$record->created_at->format('d/m/Y')}}</td>
                <td>{{$record->ragione_sociale_destinatario}}</td>
                <td>{{$record->localita_destinazione}} ({{$record->nazione_destinazione}})</td>
                <td>{{$record->delivery_type === 'point' ? 'Address to Point' : 'Address to Address'}}</td>
                <td>{{$record->numero_pacchi}}</td>
                <td class="text-end">{{$record->peso_totale}}</td>
                <td>{!! $record->esitoBall() !!}</td>
                <td class="tracking-cell">{!! $record->tracking() ?: '-' !!}</td>
                <td class="tracking-status-cell">{!! $record->trackingStatusBadge() !!}</td>
                <td class="tracking-updated-cell">{{$record->trackingUpdatedAtLabel() ?: '-'}}</td>
                <td>{{$record->agente?->aliasAgente()}}</td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-icon btn-sm btn-light btn-active-light-primary" href="{{action([$controller,'show'],$record->id)}}" data-bs-toggle="tooltip" title="Vedi">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/>
                        </svg>
                    </a>
                    <button type="button" class="btn btn-icon btn-sm btn-light btn-active-light-primary tracking-refresh-row"
                            data-url="{{action([$controller,'trackingRefresh'],$record->id)}}" data-bs-toggle="tooltip" title="Aggiorna tracking">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 1 1 .908-.418A6 6 0 1 1 8 2v1z"/><path d="M8 0a.5.5 0 0 1 .5.5V3h2.5a.5.5 0 0 1 0 1H8A.5.5 0 0 1 7.5 3V.5A.5.5 0 0 1 8 0z"/>
                        </svg>
                    </button>
                    <a class="btn btn-icon btn-sm btn-light btn-active-light-primary" href="{{action([$controller,'edit'],$record->id)}}" data-bs-toggle="tooltip" title="Modifica">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3z"/>
                            <path d="M3.293 9.707 9.793 3.207l3 3L6.293 12.707a.5.5 0 0 1-.168.11l-3 1a.5.5 0 0 1-.633-.633l1-3a.5.5 0 0 1 .11-.168z"/>
                        </svg>
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="w-100 text-center">{{$records->links()}}</div>
@endif
