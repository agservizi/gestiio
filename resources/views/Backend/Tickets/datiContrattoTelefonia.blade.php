<div class="card mb-3">
    <div class="card-header min-h-40px px-3">
        <h3 class="card-title fw-bold">Dati contratto</h3>
        <div class="card-toolbar">
            <a class="btn btn-icon btn-sm btn-light btn-active-light-primary"
               href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'show'],$record->id)}}"
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
                    <th class="">Codice cliente</th>
                    <th class="">Codice contratto</th>
                    <th class="">Tipo contratto</th>
                    <th class="">Esito</th>
                    <th class="">Nominativo</th>
                    <th class="">Contatti</th>
                    <th class="">Indirizzo</th>
                    <th class="">Agente</th>
                </tr>
                </thead>
                <tbody>
                <tr class="" id="tr_{{$record->id}}">
                    <td class="">{{$record->data->format('d/m/y')}}</td>
                    <td class="">{{$record->codice_cliente}}</td>
                    <td class="">{{$record->codice_contratto}}</td>
                    <td class="fw-semibold" style="color: {{$record->tipoContratto->gestore->colore_hex}};">
                        {{$record->tipoContratto->nome}}
                    </td>
                    <td class="">
                        <div class="d-flex align-items-center">
                            {!! $record->bulletEsitoFinale() !!}
                            <div class="me-1">
                                {!! $record->esito->labelStato() !!}
                            </div>
                            @if($record->motivo_ko)
                                <i class="fas fa-question-circle ms-1 fs-6 " data-bs-toggle="tooltip"
                                   title="{{$record->motivo_ko}}"></i>
                            @endif
                        </div>
                    </td>
                    <td class="">
                        {{$record->nominativo()}}<br>
                        {{$record->ragione_sociale}}
                    </td>
                    <td class="">
                        {{$record->telefono}}<br>
                        {{$record->email}}
                    </td>
                    <td class="">
                        {{$record->indirizzo}}
                        @if($record->citta)
                            {{$record->comune->comuneConTarga()}}
                        @endif
                    </td>
                    <td class="">
                        @if($record->agente_id)
                            {{$record->agente->aliasAgente()}}
                        @endif
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
    </div>
</div>
