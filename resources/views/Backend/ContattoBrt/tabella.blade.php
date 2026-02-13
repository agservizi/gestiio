<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Ragione sociale destinatario</th>
            <th class="">Indirizzo destinatario</th>
            <th class="">Cap destinatario</th>
            <th class="">Localita destinazione</th>
            <th class="">Provincia destinatario</th>
            <th class="">Nazione destinazione</th>
            <th class="">Mobile referente consegna</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td class="">{{$record->ragione_sociale_destinatario}}</td>
                <td class="">{{$record->indirizzo_destinatario}}</td>
                <td class="">{{$record->cap_destinatario}}</td>
                <td class="">{{$record->localita_destinazione}}</td>
                <td class="">{{$record->provincia_destinatario}}</td>
                <td class="">{{$record->nazione_destinazione}}</td>
                <td class="">{{$record->mobile_referente_consegna}}</td>
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
