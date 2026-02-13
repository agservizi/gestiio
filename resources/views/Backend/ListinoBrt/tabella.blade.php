<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Fasce di peso</th>
            <th class="text-end">Home delivery</th>
            <th class="text-end">Brt fermopoint</th>
            <th class="text-end">Contrassegno</th>
            <th class="text-center">Al kg</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td class="">{{number_format($record->da_peso,1,',','.')}}
                    - {{number_format($record->a_peso,1,',','.')}}</td>
                <td class="text-end">{{\App\importo($record->home_delivery)}}</td>
                <td class="text-end">{{\App\importo($record->brt_fermopoint)}}</td>
                <td class="text-end">{{\App\importo($record->contrassegno)}}</td>
                <td class="text-center">
                    @if($record->al_kg)
                        <i class="fas fa-check fs-3" style="color: #26C281;"></i>
                    @endif
                </td>
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
