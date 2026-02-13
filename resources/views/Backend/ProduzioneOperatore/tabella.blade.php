<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Periodo</th>
            <th class="">Nominativo</th>
            <th class="text-end">Contratti Telefonia</th>
            <th class="text-end">Contratti Energia</th>
            <th class="text-end">Totale</th>
            <th class="text-end">Proforma</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @php($inizioMese=now()->startOfMonth())
        @foreach($records as $record)
            <tr class="">
                <td class="">{{$record->mese.'/'.$record->anno}}</td>

                <td class="">{{$record->agente->nominativo()}}</td>
                <td class="text-end">
                    {{$record->importo_ordini}}
                </td>
                <td class="text-end">{{$record->importo_contratti_energia}}</td>
                <td class="text-end">{{$record->importo_totale}}</td>
                <td class="text-end">
                    @if($record->fattura_proforma_id)
                        {{$record->fatturaProforma->numero}}
                    @endif
                </td>
                <td class="text-end text-nowrap">
                    @if($record->fattura_proforma_id)
                        <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                           class="btn btn-sm btn-light btn-active-light-success"
                           href="{{action([\App\Http\Controllers\Backend\FatturaProformaController::class,'show'],$record->fattura_proforma_id)}}">Vedi proforma</a>
                    @else
                        @if(\Carbon\Carbon::createFromDate($record->anno,$record->mese,1)->lessThan($inizioMese))
                            <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                               class="btn btn-sm btn-primary"
                               href="{{action([\App\Http\Controllers\Backend\ProduzioneOperatoreController::class,'creaProforma'],$record->id)}}">Crea proforma</a>
                        @endif
                    @endif
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
