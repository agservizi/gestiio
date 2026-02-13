<div class="card card-flush h-md-100">
    <div class="card-header border-0 pt-5"><h3 class="card-title align-items-start flex-column"><span
                    class="card-label fw-bold fs-3 mb-1">Tickets recenti</span></h3>
        <div class="card-toolbar">


        </div>
    </div>
    <div class="card-body py-3">
        <div class="table-responsive">
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
                            {{$record->oggetto}} {!! $record->labelTipoTicket() !!}
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
