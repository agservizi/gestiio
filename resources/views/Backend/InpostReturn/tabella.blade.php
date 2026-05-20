<div class="table-responsive inpost-table-responsive">
    <table class="table align-middle" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-7 text-uppercase text-gray-500">
            <th>Data</th>
            <th>Destinatario reso</th>
            <th>Punto di ritiro</th>
            <th>Stato</th>
            <th>Riferimento</th>
            <th>Agente</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr data-id="{{$record->id}}">
                <td>{{$record->created_at->format('d/m/Y')}}</td>
                <td>
                    <div class="fw-bold text-dark">{{$record->receiver_name}}</div>
                    <div class="text-muted fs-7">{{$record->receiver_email ?: $record->receiver_phone ?: '-'}}</div>
                </td>
                <td>
                    <div class="fw-bold text-dark">{{$record->point_id}}</div>
                    <div class="text-muted fs-7">{{$record->point_label ?: '-'}}</div>
                </td>
                <td>{!! $record->statusBadge() !!}</td>
                <td class="text-muted fs-7">{{$record->customer_reference ?: '-'}}</td>
                <td>
                    <span class="inpost-agent-pill">{{$record->agente?->aliasAgente() ?: '-'}}</span>
                </td>
                <td class="text-end text-nowrap">
                    <div class="inpost-action-group">
                        <a class="btn btn-icon btn-sm btn-light btn-active-light-primary" href="{{action([$controller,'show'],$record->id)}}" data-bs-toggle="tooltip" title="Vedi">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/>
                            </svg>
                        </a>
                        <a class="btn btn-icon btn-sm btn-light btn-active-light-danger" data-modal-delete="true"
                           data-url="{{action([$controller,'destroy'],$record->id)}}"
                           data-bs-toggle="tooltip" title="Elimina">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                            </svg>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="w-100 text-center">{{$records->links()}}</div>
@endif
