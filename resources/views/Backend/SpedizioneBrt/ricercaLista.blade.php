@foreach($contatti as $contatto)
    <div class="d-flex justify-content-between">
        <div >
           <span class="fw-bold"> {{$contatto->ragione_sociale_destinatario}}</span><br>
            {{$contatto->indirizzo_destinatario}} - {{$contatto->localita_destinazione}}
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="imposta('{{$contatto->ragione_sociale_destinatario}}','{{$contatto->indirizzo_destinatario}}','{{$contatto->cap_destinatario}}','{{$contatto->localita_destinazione}}','{{$contatto->mobile_referente_consegna}}','{{$contatto->provincia_destinatario}}','{{$contatto->provincia?->provincia}}')" >Usa</button>
        </div>
    </div>
@endforeach
