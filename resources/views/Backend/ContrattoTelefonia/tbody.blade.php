@foreach($records as $record)
    <tr class="" id="tr_{{$record->id}}">
        <td class="d-none d-lg-table-cell">{{$record->data->format('d/m/y')}}</td>
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
        <td class="d-none d-md-table-cell">
            {!! \App\telefonoWhatsapp($record->telefono) !!}<br>
            {{$record->email}}
        </td>
        <td class="d-none d-lg-table-cell">
            @if($record->citta)
                {{$record->comune->comuneConTarga()}}
            @endif
        </td>
        <td class="">
            @if($record->agente_id)
                {{$record->agente->aliasAgente()}}
            @endif
        </td>
        <td class="">
            @if($record->mese_pagamento)
                {{str_replace('_','/',$record->mese_pagamento)}}
            @endif
            {!! $record->labelPagato() !!}
        </td>
        <td class="text-end text-nowrap">
            <div class="btn-group" role="group">
                @if($record->tipoContratto->pda && $record->esito_id=='bozza')
                    <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'pda'],$record->id)}}"
                       class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                       data-bs-toggle="tooltip" data-bs-placement="top" title="Scarica pda"

                    >
                    <span class="svg-icon svg-icon-muted svg-icon-1">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 11H7C6.4 11 6 10.6 6 10V9C6 8.4 6.4 8 7 8H17C17.6 8 18 8.4 18 9V10C18 10.6 17.6 11 17 11ZM22 5V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4V5C2 5.6 2.4 6 3 6H21C21.6 6 22 5.6 22 5Z"
                              fill="currentColor"/>
                        <path opacity="0.3"
                              d="M21 16H3C2.4 16 2 15.6 2 15V14C2 13.4 2.4 13 3 13H21C21.6 13 22 13.4 22 14V15C22 15.6 21.6 16 21 16ZM18 20V19C18 18.4 17.6 18 17 18H7C6.4 18 6 18.4 6 19V20C6 20.6 6.4 21 7 21H17C17.6 21 18 20.6 18 20Z"
                              fill="currentColor"/>
                        </svg>
                    </span>
                    </a>
                @endif
                @if($puoCreare)
                    <a href="{{action([\App\Http\Controllers\Backend\ContrattoTelefoniaController::class,'create'],['duplica'=>$record->id])}}"
                       class="btn btn-icon btn-sm btn-light btn-active-light-primary duplica"
                       data-bs-toggle="tooltip" data-bs-placement="top" title="Duplica"
                    >
                    <span class="svg-icon svg-icon-muted svg-icon-1">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect opacity="0.5" x="7" y="2" width="14" height="16" rx="3" fill="currentColor"/>
                            <rect x="3" y="6" width="14" height="16" rx="3" fill="currentColor"/>
                        </svg>
                    </span>
                    </a>

                @endif
                @if($record->allegati_count)
                    <a href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['modal-allegati-telefonia',$record->id])}}"
                       class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                       data-target="kt_modal" data-toggle="modal-ajax"
                       data-bs-toggle="tooltip" data-bs-placement="top" title="Scarica allegati"
                    >
                    <span class="svg-icon svg-icon-muted svg-icon-1"><svg width="24" height="24" viewBox="0 0 24 24"
                                                                          fill="none"
                                                                          xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.3"
                          d="M4.425 20.525C2.525 18.625 2.525 15.525 4.425 13.525L14.825 3.125C16.325 1.625 18.825 1.625 20.425 3.125C20.825 3.525 20.825 4.12502 20.425 4.52502C20.025 4.92502 19.425 4.92502 19.025 4.52502C18.225 3.72502 17.025 3.72502 16.225 4.52502L5.82499 14.925C4.62499 16.125 4.62499 17.925 5.82499 19.125C7.02499 20.325 8.82501 20.325 10.025 19.125L18.425 10.725C18.825 10.325 19.425 10.325 19.825 10.725C20.225 11.125 20.225 11.725 19.825 12.125L11.425 20.525C9.525 22.425 6.425 22.425 4.425 20.525Z"
                          fill="currentColor"/>
                    <path d="M9.32499 15.625C8.12499 14.425 8.12499 12.625 9.32499 11.425L14.225 6.52498C14.625 6.12498 15.225 6.12498 15.625 6.52498C16.025 6.92498 16.025 7.525 15.625 7.925L10.725 12.8249C10.325 13.2249 10.325 13.8249 10.725 14.2249C11.125 14.6249 11.725 14.6249 12.125 14.2249L19.125 7.22493C19.525 6.82493 19.725 6.425 19.725 5.925C19.725 5.325 19.525 4.825 19.125 4.425C18.725 4.025 18.725 3.42498 19.125 3.02498C19.525 2.62498 20.125 2.62498 20.525 3.02498C21.325 3.82498 21.725 4.825 21.725 5.925C21.725 6.925 21.325 7.82498 20.525 8.52498L13.525 15.525C12.325 16.725 10.525 16.725 9.32499 15.625Z"
                          fill="currentColor"/>
                    </svg>
                    </span>
                    </a>
                @endif
                <a class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                   href="{{action([$controller,'show'],$record->id)}}"
                   data-bs-toggle="tooltip" data-bs-placement="top" title="Vedi"

                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye"
                         viewBox="0 0 16 16">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                    </svg>
                </a>

                @if($record->esito_id!='bozza')
                    @if($puoModificare)
                        <a href="{{action([\App\Http\Controllers\Backend\ModalController::class,'show'],['modifica-stato',$record->id])}}"
                           class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                           data-target="kt_modal" data-toggle="modal-ajax"
                           data-bs-toggle="tooltip" data-bs-placement="top" title="Modifica esito"

                        >
                            <span class="svg-icon svg-icon-3">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                 width="24px"
                                 height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect x="0" y="0" width="24" height="24"></rect>
                                    <path
                                            d="M7,3 L17,3 C19.209139,3 21,4.790861 21,7 C21,9.209139 19.209139,11 17,11 L7,11 C4.790861,11 3,9.209139 3,7 C3,4.790861 4.790861,3 7,3 Z M7,9 C8.1045695,9 9,8.1045695 9,7 C9,5.8954305 8.1045695,5 7,5 C5.8954305,5 5,5.8954305 5,7 C5,8.1045695 5.8954305,9 7,9 Z"
                                            fill="#000000"></path>
                                    <path
                                            d="M7,13 L17,13 C19.209139,13 21,14.790861 21,17 C21,19.209139 19.209139,21 17,21 L7,21 C4.790861,21 3,19.209139 3,17 C3,14.790861 4.790861,13 7,13 Z M17,19 C18.1045695,19 19,18.1045695 19,17 C19,15.8954305 18.1045695,15 17,15 C15.8954305,15 15,15.8954305 15,17 C15,18.1045695 15.8954305,19 17,19 Z"
                                            fill="#000000" opacity="0.3"></path>
                                </g>
                            </svg>
                        </span>
                        </a>
                    @endif
                @endif
                @if($puoCreare && $record->puoModificare($puoModificare))
                    <a class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                       href="{{action([$controller,'edit'],$record->id)}}"
                       data-bs-toggle="tooltip" data-bs-placement="top" title="Modifica"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                             class="bi bi-pencil-fill" viewBox="0 0 16 16">
                            <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>
                        </svg>
                    </a>
                @endif
            </div>
        </td>
    </tr>
@endforeach
