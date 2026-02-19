@extends('Backend._layout._main')
@section('toolbar')
    <div class="d-flex align-items-center py-1">
        <div class="me-4">
            <a href="#" class="btn btn-sm {{$conFiltro?'btn-success':'bg-body'}} btn-flex btn-light btn-active-primary fw-bolder" data-kt-menu-trigger="click"
               data-kt-menu-placement="bottom-end" data-kt-menu-flip="top-end">
                <span class="svg-icon svg-icon-6 svg-icon-muted me-1">
												<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path d="M19.0759 3H4.72777C3.95892 3 3.47768 3.83148 3.86067 4.49814L8.56967 12.6949C9.17923 13.7559 9.5 14.9582 9.5 16.1819V19.5072C9.5 20.2189 10.2223 20.7028 10.8805 20.432L13.8805 19.1977C14.2553 19.0435 14.5 18.6783 14.5 18.273V13.8372C14.5 12.8089 14.8171 11.8056 15.408 10.964L19.8943 4.57465C20.3596 3.912 19.8856 3 19.0759 3Z"
                                                          fill="currentColor"></path>
												</svg>
											</span>
                Filtri</a>
            <div class="menu menu-sub menu-sub-dropdown w-250px w-md-350px" data-kt-menu="true" id="filtri-drop">
                @include('Backend.Tickets.indexFiltri')
            </div>
        </div>
        <a class="btn btn-sm btn-primary" href="{{action([$controller,'create'])}}">Nuovo {{\App\Models\Ticket::NOME_SINGOLARE}}</a>
    </div>
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-200 align-middle gs-0 gy-4">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th>#</th>
                        <th>Data</th>
                        <th>Servizio</th>
                        <th>Oggetto</th>
                        <th>Da</th>
                        <th>Assegnato a</th>
                        <th>Cliente</th>
                        <th>Ultimo aggiornamento</th>
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
                                {{$record->created_at->format('d/m/Y H:i')}}
                            </td>
                            <td>
                                {{$record->classeServizio()}}
                                @if($record->lettura?->messaggio_letto===0)
                                    <span class="badge badge-danger ms-1">Nuovo</span>
                                @endif
                            </td>

                            <td>
                                {{$record->oggetto}} {!! $record->causaleTicket->labelCausaleTicket() !!}
                            </td>
                            <td>
                                {{$record->utente->nominativo()}}
                            </td>
                            <td>
                                {{$record->assegnatario?->nominativo() ?? '-'}}
                                @if(!$record->agente_id)
                                    <span class="badge badge-light-warning ms-1">Non assegnato</span>
                                @elseif((int)$record->agente_id === (int)Auth::id())
                                    <span class="badge badge-light-success ms-1">In carico a me</span>
                                @endif
                            </td>
                            <td>

                            </td>
                            <td>
                                {{$record->updated_at->format('d/m/Y H:i')}}
                            </td>
                            <td>
                                {!! $record->labelStatoTicket() !!}
                            </td>
                            <td class="text-end" style="white-space: nowrap;">
                                <a class="btn btn-sm btn-light btn-active-light-success" href="{{action([$controller,'show'],$record->id)}}">Vedi</a>
                                @if(Auth::user()?->hasAnyPermission(['admin','supervisore']) && $assegnatariFiltro->count())
                                    <form method="POST" action="{{action([$controller,'update'],$record->id)}}" class="d-inline-flex align-items-center ms-1 js-ticket-assign-form">
                                        @csrf
                                        @method('PATCH')
                                        <select name="agente_id" class="form-select form-select-sm form-select-solid me-1" style="width: 170px;" required>
                                            <option value="">Assegna a...</option>
                                            @foreach($assegnatariFiltro as $assegnatario)
                                                <option value="{{$assegnatario->id}}" {{(int)($record->agente_id ?? 0) === (int)$assegnatario->id ? 'selected' : ''}}>
                                                    {{$assegnatario->cognome}} {{$assegnatario->nome}}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-light-primary">Assegna</button>
                                    </form>
                                @endif
                                @if(Auth::user()?->hasAnyPermission(['admin','supervisore']) && (int)($record->agente_id ?? 0) !== (int)Auth::id())
                                    <form method="POST" action="{{action([$controller,'update'],$record->id)}}" class="d-inline-block ms-1 js-ticket-takeover-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="agente_id" value="{{Auth::id()}}">
                                        <button type="submit" class="btn btn-sm btn-light-warning">Prendi in carico</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-md-12 text-center">
                    {{$records->links()}}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('customScript')
    <script>
        $(function () {
            $(document).on('submit', '.js-ticket-assign-form', function (event) {
                event.preventDefault();
                const form = this;

                Swal.fire({
                    text: 'Confermi la riassegnazione di questo ticket?',
                    icon: 'question',
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: 'Sì, assegna',
                    cancelButtonText: 'Annulla',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-light'
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $(document).on('submit', '.js-ticket-takeover-form', function (event) {
                event.preventDefault();
                const form = this;

                Swal.fire({
                    text: 'Confermi la presa in carico di questo ticket?',
                    icon: 'question',
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: 'Sì, conferma',
                    cancelButtonText: 'Annulla',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-light'
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
