<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Codice fiscale</th>
            <th class="">Nominativo</th>
            <th class="">Ragione sociale</th>
            <th class="">Utente</th>
            <th class="">Invio dati accesso</th>
            <th class="">Ultimo accesso</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td class="">{{$record->codice_fiscale}}</td>
                <td class="">{{$record->nominativo()}}</td>
                <td class="">{{$record->ragione_sociale}}</td>
                <td class="">
                    @isset($record->utente)
                        {{$record->utente->email}}
                    @endif
                </td>
                <td>
                    @isset($record->utente)
                        {{$record->utente->invio_dati_accesso?$record->utente->invio_dati_accesso->format('d/m/Y H:i'):''}}
                    @endisset
                </td>
                <td>
                    @isset($record->utente)
                        {{$record->utente->ultimo_accesso?$record->utente->ultimo_accesso->format('d/m/Y H:i'):''}}
                    @endisset
                </td>
                <td class="text-end text-nowrap">
                    <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                       class="btn btn-sm btn-light btn-active-light-primary"
                       href="{{action([$controller,'show'],$record->id)}}">Vedi</a>
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
