<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Numero</th>
            <th class="">Data</th>

            <th class="">Intestazione</th>
            <th class="text-end">Totale</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td class="">{{$record->numero}}</td>

                <td class="">{{$record->data->format('d/m/Y')}}</td>
                <td class="">
                    {{$record->intestazione->denominazione}}
                </td>
                <td class="text-end">{{$record->totale_con_iva}}</td>

                <td class="text-end text-nowrap">
                    <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                       class="btn btn-sm btn-light btn-active-light-success"
                       href="{{action([$controller,'show'],$record->id)}}">Vedi</a>
                    <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                       class="btn btn-sm btn-light btn-active-light-success"
                       href="{{action([$controller,'pdf'],$record->id)}}">Pdf</a>
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
