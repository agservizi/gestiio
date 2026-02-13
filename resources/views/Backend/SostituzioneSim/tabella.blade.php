<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
<th class="">Attivazione sim</th>
<th class="">Agente</th>
<th class="">Motivazione</th>
<th class="text-end">Importo</th>
<th class="">Seriale vecchia sim</th>
<th class="">Seriale nuova sim</th>
        <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="" >
<td class="text-center">
@if($record->attivazione_sim_id)
                        {{$record->attivazione_sim_id}}
                    @endif
                    </td>
<td class="text-center">
@if($record->agente_id)
                        {{$record->agente_id}}
                    @endif
                    </td>
<td class="">{{$record->motivazione}}</td>
<td class="text-end">{{$record->importo}}</td>
<td class="">{{$record->seriale_vecchia_sim}}</td>
<td class="">{{$record->seriale_nuova_sim}}</td>

                <td class="text-end text-nowrap">
                    <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                       class="btn btn-sm btn-light btn-active-light-primary"
                       href="{{action([$controller,'edit'],$record->id)}}">Modifica</a>
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
