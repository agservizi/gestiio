<div class="table-responsive">
    <table class="table align-middle inpost-listino-table" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-7 text-uppercase text-gray-500">
            <th>Package</th>
            <th class="text-end">Locker o punto di ritiro</th>
            <th class="text-end">Indirizzo del destinatario</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr>
                <td>
                    @php
                        $label = match($record->package_type) {
                            'small' => 'Piccolo (S)',
                            'medium' => 'Medio (M)',
                            'large' => 'Grande (L)',
                            default => strtoupper((string) $record->package_type),
                        };
                    @endphp
                    <span class="fw-bold text-dark">{{$label}}</span>
                </td>
                <td class="text-end"><span class="badge badge-light-warning fs-7 inpost-listino-badge">{{\App\importo($record->locker_point)}}</span></td>
                <td class="text-end"><span class="badge badge-light-primary fs-7 inpost-listino-badge">{{\App\importo($record->home_delivery)}}</span></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-light btn-active-light-primary inpost-listino-action" href="{{action([$controller,'edit'],$record->id)}}">Modifica</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if($records instanceof \Illuminate\Pagination\LengthAwarePaginator )
    <div class="w-100 text-center">
        {{$records->links()}}
    </div>
@endif
