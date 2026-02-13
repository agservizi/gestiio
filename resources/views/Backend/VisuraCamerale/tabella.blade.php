@foreach($records as $record)
    <tr id="tr_{{$record->id}}">
        <td>
            {{$record->created_at->format('d/m/Y')}}
        </td>
        @if($visualizzaAgente)
            <td>
                {{$record->agente->nominativo()}}
            </td>
        @endif
        <td>
            {{$record->contratto->ragione_sociale}}
        </td>
        <td>
            @if($record->contratto_id)
                {{$record->contratto_id}}
            @endif
        </td>
        <td class="">{{$record->tipo}}</td>
        <td class="text-end">{{\App\importo($record->prezzo)}}</td>
        <td>
            @if($record->richiesta_id)
                {{$record->richiesta_id}}
            @endif
        </td>
        <td>
            @if($record->stato_richiesta)
                {{$record->stato_richiesta}}
            @endif
        </td>
        <td class="text-end text-nowrap">
            @if($record->stato_richiesta!=='Dati disponibili')
                <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                   class="btn btn-sm btn-light btn-active-light-primary aggiorna-visura"
                   href="{{action([$controller,'show'],$record->id)}}">


                    <span class="indicator-label">
                        Aggiorna
                    </span>
                    <span class="indicator-progress">
                        Attendi... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </a>
            @else
                <a data-targetZ="kt_modal" data-toggleZ="modal-ajax"
                   class="btn btn-sm btn-light btn-active-light-success "
                   href="{{action([$controller,'show'],$record->id)}}">Allegato</a>
            @endif

        </td>
    </tr>
@endforeach

