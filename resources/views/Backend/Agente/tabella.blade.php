<div class="table-responsive">
    <table class="table table-row-bordered ">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th>Codice Agente</th>
            <th>Denominazione</th>
            <th>Contatti</th>
            <th class="d-none d-md-table-cell">Ultimo accesso</th>
            @if(!Auth::user()->hasPermissionTo('supervisore'))
                <th class="d-none d-md-table-cell text-end">Portafoglio Servizi</th>
                <th class="d-none d-md-table-cell text-end">Portafoglio Spedizioni</th>
            @endif
            <th class="d-none d-lg-table-cell text-center">Stato</th>
            <th class="d-none d-lg-table-cell text-center">Ruolo</th>
            <th class="d-none d-lg-table-cell text-center">2fa</th>
            <th class="text-end">
            </th>
        </tr>
        </thead>
        <tbody id="t-body">
        @foreach($records as $record)
            <tr>
                <td class="fw-bolder">{{$record->codiceAgente()}}</td>
                <td class="fw-bolder">{{$record->aliasAgente()}}</td>
                <td>{!! $record->contatti() !!}</td>
                <td class="d-none d-md-table-cell">
                    @if($record->ultimo_accesso)
                        {{$record->ultimo_accesso->format('d/m/Y H:i')}}
                        <div class="text-muted fs-8">{{$record->ultimo_accesso->diffForHumans()}}</div>
                    @else
                        <span class="text-muted">Mai</span>
                    @endif
                </td>
                @if(!Auth::user()->hasPermissionTo('supervisore'))
                    <td class="d-none d-lg-table-cell text-end">
                        {!! \App\importo($record->agente?->portafoglio_servizi) !!}
                    </td>
                    <td class="d-none d-lg-table-cell text-end">
                        {!! \App\importo($record->agente?->portafoglio_spedizioni) !!}
                    </td>
                @endif
                <td class="d-none d-lg-table-cell text-center">
                    @if($record->permissions->count())
                        <span class="badge badge-light-success">Attivo</span>
                    @else
                        <span class="badge badge-light-warning">Sospeso</span>
                    @endif
                </td>
                <td class="d-none d-lg-table-cell  text-center">
                    {!! $record->userLevel(false,$record) !!}
                </td>
                <td class="text-center">
                    @if($record->two_factor_secret)
                        <i class="fas fa-check fs-3" style="color: #26C281;"></i>
                    @endif
                </td>
                <td class="text-end">
                    <div class="btn-group" role="group">
                        <a class="btn btn-sm btn-light btn-active-light-success  mx-2"
                           href="{{action([\App\Http\Controllers\Backend\AgenteController::class,'show'],$record->id)}}">Vedi</a>
                        <a class="btn btn-sm btn-light btn-active-light-primary"
                           href="{{action([\App\Http\Controllers\Backend\AgenteController::class,'edit'],$record->id)}}">Modifica</a>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="w-100 text-center">
    {{$records->links()}}
</div>
