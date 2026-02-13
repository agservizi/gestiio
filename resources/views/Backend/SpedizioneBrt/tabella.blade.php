<div class="table-responsive">
    <table class="table table-row-bordered" id="tabella-elenco">
        <thead>
        <tr class="fw-bolder fs-6 text-gray-800">
            <th>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           id="tutti"/>
                    <label class="form-check-label" for="tutti">
                    </label>
                </div>
            </th>
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
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($records as $record)
            <tr class="">
                <td>
                    @if(!$record->bordero_id)
                        <div class="form-check">
                            <input class="form-check-input sel" type="checkbox" value="{{$record->id}}"
                                   id="check{{$record->id}}" name="check[]]"/>
                            <label class="form-check-label" for="check{{$record->id}}">
                            </label>
                        </div>
                    @endif
                </td>
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
                <td class="text-end text-nowrap">

                    <a class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                       href="{{action([\App\Http\Controllers\Backend\TicketsController::class,'create'],['servizio_type'=>'spedizione-brt','servizio_id'=>$record->id])}}"
                       data-bs-toggle="tooltip" data-bs-placement="top" title="Apri ticket"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" height="16" width="18" viewBox="0 0 576 512">
                            <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                            <path d="M64 64C28.7 64 0 92.7 0 128v64c0 8.8 7.4 15.7 15.7 18.6C34.5 217.1 48 235 48 256s-13.5 38.9-32.3 45.4C7.4 304.3 0 311.2 0 320v64c0 35.3 28.7 64 64 64H512c35.3 0 64-28.7 64-64V320c0-8.8-7.4-15.7-15.7-18.6C541.5 294.9 528 277 528 256s13.5-38.9 32.3-45.4c8.3-2.9 15.7-9.8 15.7-18.6V128c0-35.3-28.7-64-64-64H64zm64 112l0 160c0 8.8 7.2 16 16 16H432c8.8 0 16-7.2 16-16V176c0-8.8-7.2-16-16-16H144c-8.8 0-16 7.2-16 16zM96 160c0-17.7 14.3-32 32-32H448c17.7 0 32 14.3 32 32V352c0 17.7-14.3 32-32 32H128c-17.7 0-32-14.3-32-32V160z"/>
                        </svg>


                    </a>


                    <a class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                       href="{{action([$controller,'show'],$record->id)}}"
                       data-bs-toggle="tooltip" data-bs-placement="top" title="Vedi"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                             class="bi bi-eye" viewBox="0 0 16 16">
                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                        </svg>
                    </a>

                    @if($record->esito=='ERROR' || !$record->esito)
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
