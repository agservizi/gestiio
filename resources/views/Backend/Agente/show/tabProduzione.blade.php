<div class="table-responsive">
    <table class="table align-middle table-row-dashed fw-bold text-gray-600 fs-6 gy-5" id="kt_table_customers_logs">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th> Periodo</th>
            <th class="text-end"> Ok</th>
            <th class="text-end"> In lavorazione</th>
            <th class="text-end"> Contratti Telefonia</th>
            <th class="text-end"> Contratti Energia</th>
            <th class="text-end"> Totale</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td> {{$record->mese.'/'.$record->anno}} </td>
                <td class="text-end"> {{$record->conteggio_ordini_ok}}</td>
                <td class="text-end"> {{$record->conteggio_ordini-$record->conteggio_ordini_ok-$record->conteggio_ordini_ko}}</td>
                <td class="text-end"> {{$record->importo_ordini}}</td>
                <td class="text-end"> {{$record->importo_contratti_energia}}</td>
                <td class="text-end"> {{$record->importo_totale}}</td>
                <td class="text-end">
                    <a data-target="kt_modal" data-toggle="modal-ajax"
                       class="btn btn-sm btn-light btn-active-light-primary"
                       href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['dettaglio_produzione',$record->id])}}">Dettaglio</a>
                    @if(Auth::id()==1)
                        <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                           class="btn btn-sm btn-light btn-active-light-primary azione"
                           href="{{action([$controller,'azioni'],['id'=>$record->user_id,'azione'=>'ricalcola_provvigioni','anno'=>$record->anno,'mese'=>$record->mese])}}">Ricalcola</a>
                    @endif

                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{$records->links()}}
