<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th class="">Nome</th>
            <th class="">Colore</th>
            <th class="">Email notifica</th>
            <th class="text-center">Attivo</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td class="">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px symbol-2by3 me-3">
                            @if($record->logo)
                                <img src="{{$record->immagineLogo()}}" class="" alt="">
                            @endif
                        </div>
                        <div class="d-flex justify-content-start flex-column">
                            {{$record->nome}}
                            <span class="text-gray-400 fw-semibold d-block fs-7">{{$record->url}}</span>
                        </div>
                    </div>
                </td>
                <td class=""><span class="badge min-w-20" style="background-color: {{$record->colore_hex}}"> </span>
                <td >
                    {{$record->email_notifica_a_gestore}}
                </td>
                <td class="text-center">
                    @if($record->attivo)
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
