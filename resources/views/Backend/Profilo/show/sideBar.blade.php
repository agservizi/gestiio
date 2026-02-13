<div class="card ">
    <div class="card-body">
        <h4>Altri dati</h4>
        <div class="fs-6">
            <div class="fw-bolder mt-5">#</div>
            <div class="text-gray-600">{{$record->id}}</div>
            <div class="fw-bolder mt-5">Ragione sociale</div>
            <div class="text-gray-600">{{$record->agente->ragione_sociale}}</div>
            <div class="fw-bolder mt-5">Codice fiscale</div>
            <div class="text-gray-600">{{$record->agente->codice_fiscale}}</div>
            <div class="fw-bolder mt-5">Partita iva</div>
            <div class="text-gray-600">{{$record->agente->partita_iva}}</div>
        </div>
        <div class="separator separator-dashed my-3"></div>
        <h4>Indirizzo</h4>
        <div class="fw-bolder mt-5">Indirizzo</div>
        <div class="text-gray-600">{{$record->agente->indirizzo}}</div>
        <div class="fw-bolder mt-5">Città</div>
        <div class="text-gray-600">
            @if($record->agente->citta)
                {{\App\Models\Comune::find($record->agente->citta)->comuneConTarga()}}
            @endif
        </div>
        <div class="fw-bolder mt-5">Cap</div>
        <div class="text-gray-600">{{$record->agente->cap}}</div>
    </div>
</div>
