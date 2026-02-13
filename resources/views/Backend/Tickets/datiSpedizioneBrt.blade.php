@if($record)
    <div class="card mb-3">
        <div class="card-header min-h-40px px-3">
            <h3 class="card-title fw-bold">Dati spedizione</h3>
            <div class="card-toolbar">
                <a class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                   href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'show'],$record->id)}}"
                   data-bs-toggle="tooltip" data-bs-placement="top" title="Vedi"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                         class="bi bi-eye" viewBox="0 0 16 16">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                    </svg>
                </a>
            </div>
        </div>
        <div class="card-body py-1 px-3">
            <div class="table-responsive">
                <table class="table table-row-bordered" id="tabella-elenco">
                    <thead>
                    <tr class="fw-bolder fs-6 text-gray-800">
                        <th class="">Data</th>
                        <th class="">Ragione sociale destinatario</th>
                        <th class="">Localita destinazione</th>
                        <th class="">Nazione destinazione</th>
                        <th class="">Tariffa</th>
                        <th class="">Pacchi</th>
                        <th class="">Peso totale</th>
                        <th class="">Prezzo</th>
                        <th class="">Esito</th>
                        <th class="">Tracking</th>
                        <th class="">Borderò</th>
                        <th class="">Agente</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="">
                        <td class="">{{$record->created_at->format('d/m/Y')}}</td>
                        <td class="">{{$record->ragione_sociale_destinatario}}</td>
                        <td class="">{{$record->localita_destinazione}}</td>
                        <td class="">{{$record->nazione_destinazione}}</td>
                        <td class="">{{$record->tariffa}}</td>
                        <td class="">{{$record->numero_pacchi}}</td>
                        <td class="text-end">{{$record->peso_totale}}</td>
                        <td class="">{{\App\importo($record->prezzo_spedizione)}}</td>
                        <td>{!! $record->esitoBall() !!}</td>
                        <td>{!! $record->tracking() !!}</td>
                        <td>
                            @if($record->bordero_id)
                                <a href="{{action([\App\Http\Controllers\Backend\SpedizioneBrtController::class,'bordero'],$record->bordero_id)}}"
                                   target="_blank">{{$record->bordero_id}}</a>

                            @endif
                        </td>
                        <td>  {{$record->agente->aliasAgente()}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@else
    @php($level='danger')
    <div class="notice d-flex bg-light-danger rounded border-danger border border-dashed p-6 mb-6">
        <!--begin::Wrapper-->
        <div class="d-flex flex-stack flex-grow-1 flex-wrap flex-md-nowrap">
            <!--begin::Content-->
            <div class="mb-3 mb-md-0 fw-bold">
                <h4 class="text-gray-900 fw-bolder">Dati spedizione mancanti:</h4>
                <div class="fs-6 text-gray-700 pe-7">La spedizione è stata cancellata</div>
            </div>
        </div>
        <!--end::Wrapper-->
    </div>
@endif
