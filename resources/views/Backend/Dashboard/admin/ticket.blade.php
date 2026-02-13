<div class="card card-flush h-md-100">
    <div class="card-header border-0 pt-5"><h3 class="card-title align-items-start flex-column"><span
                    class="card-label fw-bold fs-3 mb-1">Tickets</span></h3>
        <div class="card-toolbar">
        </div>
    </div>
    <div class="card-body py-3">
        <div class="row p-0 px-1">
            @foreach(\App\Models\Ticket::STATI_TICKETS as $key=>$value)
                <div class="col-xxl-4 col-xl-12 col-md-12">
                    <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'],['stato'=>$key])}}">
                        <div class="border border-dashed border-gray-300 text-center min-h-90px min-h-xl-60px min-h-xl-50px rounded pt-4 pb-2 bg-{{$value['colore']}}">
                            <span class="fs-5 fs-md-6 fw-bold text-gray-200 d-block">{{$value['testo']}}</span>
                            <span class="fs-4 fw-bold text-gray-300 ">{{isset($conteggioTikets[$key])?$conteggioTikets[$key]->conteggio:0}}</span>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        <div class="table-responsive scroll-y d-none d-xxl-block">
            <table class="table table-row-dashed table-row-gray-200 align-middle gs-0 gy-4">
                <thead>
                <tr class="fw-bolder fs-6 text-gray-800">
                    <th>#</th>
                    <th>Oggetto</th>
                    <th>Stato</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($records as $record)
                    <tr>
                        <td class="fw-bold">
                            {{$record->uidTicket()}}
                        </td>
                        <td>
                            {{$record->oggetto}} {!! $record->causaleTicket->labelCausaleTicket() !!}
                        </td>
                        <td>
                            {!! $record->labelStatoTicket() !!}
                        </td>
                        <td class="text-end" style="white-space: nowrap;">
                            <a class="btn btn-sm btn-light btn-active-light-success" href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'show'],$record->id)}}">Vedi</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
