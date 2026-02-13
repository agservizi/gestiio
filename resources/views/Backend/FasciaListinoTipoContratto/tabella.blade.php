<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
<th class="">Mandato</th>
<th class="">Da ordini</th>
<th class="">A ordini</th>
<th class="text-end">Importo per ordine</th>
<th class="text-end">Importo bonus</th>
<th class="text-end">Importo soglie precedenti</th>
        <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="" >
<td class="text-center">
@if($record->mandato_id)
                        {{$record->mandato_id}}
                    @endif
                    </td>
<td class="">{{$record->da_ordini}}</td>
<td class="">{{$record->a_ordini}}</td>
<td class="text-end">{{$record->importo_per_ordine}}</td>
<td class="text-end">{{$record->importo_bonus}}</td>
<td class="text-end">{{$record->importo_soglie_precedenti}}</td>

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
