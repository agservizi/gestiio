<div class="card card-flush h-md-100">
    <div class="card-header border-0 pt-5 pb-2">
        <div class="card-title d-flex align-items-center gap-2">
            <span class="svg-icon svg-icon-2 text-primary">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.3" d="M4 5C4 3.89543 4.89543 3 6 3H18C19.1046 3 20 3.89543 20 5V8C18.8954 8 18 8.89543 18 10C18 11.1046 18.8954 12 20 12V15C20 16.1046 19.1046 17 18 17H6C4.89543 17 4 16.1046 4 15V12C5.10457 12 6 11.1046 6 10C6 8.89543 5.10457 8 4 8V5Z" fill="currentColor"/>
                    <path d="M9 7.5C9 6.67157 9.67157 6 10.5 6H13.5C14.3284 6 15 6.67157 15 7.5C15 8.32843 14.3284 9 13.5 9H10.5C9.67157 9 9 8.32843 9 7.5Z" fill="currentColor"/>
                </svg>
            </span>
            <div>
                <h3 class="card-title m-0">Ticket assistenza</h3>
                <div class="text-muted fs-7">Panoramica ticket aperti e in lavorazione</div>
            </div>
        </div>
        <div class="card-toolbar">
            <a class="btn btn-sm btn-light-primary fw-bold" href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'])}}">Vedi tutti</a>
        </div>
    </div>

    <div class="card-body pt-0">
        <div class="row g-3 mb-4">
            @foreach(\App\Models\Ticket::STATI_TICKETS as $key=>$value)
                <div class="col-4">
                    <a href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'index'],['stato'=>$key])}}" class="text-decoration-none">
                        <div class="border border-dashed border-gray-300 rounded p-3 text-center bg-light-{{$value['colore']}}">
                            <div class="text-gray-700 fw-semibold fs-8">{{$value['testo']}}</div>
                            <div class="fw-bolder fs-4">{{isset($conteggioTikets[$key])?$conteggioTikets[$key]->conteggio:0}}</div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-row-dashed align-middle gs-0 gy-3">
                <thead>
                <tr class="fw-bold text-muted fs-8 text-uppercase">
                    <th>#</th>
                    <th>Oggetto</th>
                    <th>Stato</th>
                    <th class="text-end"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($records as $record)
                    <tr>
                        <td class="fw-bold">{{$record->uidTicket()}}</td>
                        <td>
                            <div class="fw-semibold">{{$record->oggetto}}</div>
                            @if($record->causaleTicket)
                                <div class="fs-8 text-muted">{{$record->causaleTicket->descrizione_causale}}</div>
                            @endif
                        </td>
                        <td>{!! $record->labelStatoTicket() !!}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-light-primary" href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'show'],$record->id)}}">Apri</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-muted">Nessun ticket aperto recente.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
