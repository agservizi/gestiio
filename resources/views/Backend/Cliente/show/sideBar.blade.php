<div class="card card-stretch">
    <div class="card-body pt-15">
        <h4>Dati cliente</h4>
        <div class="fs-6">
            <div class="fw-bolder mt-5">#</div>
            <div class="text-gray-600">{{$record->id}}</div>
            <div class="fw-bolder mt-5">Email</div>
            <div class="text-gray-600">
                <a href="mailto:{{$record->email}}" class="text-gray-600 text-hover-primary">{{$record->email}}</a>
            </div>
            <div class="fw-bolder mt-5">Telefono</div>
            <div class="text-gray-600">
                <a href="tel:{{$record->telefono}}" class="text-gray-600 text-hover-primary">{!! $record->telefono !!}</a>
            </div>
            <div class="fw-bolder mt-5">Codice fiscale</div>
            <div class="text-gray-600">{{$record->codice_fiscale}}</div>
            <div class="fw-bolder mt-5">Data creazione</div>
            <div class="text-gray-600">{{$record->created_at->format('d/m/Y')}}</div>
        </div>
        <div class="separator separator-dashed my-3"></div>

        <h4>Indirizzo</h4>
        <div class="fw-bolder mt-5">Indirizzo</div>
        <div class="text-gray-600">{{$record->indirizzo}}</div>
        <div class="fw-bolder mt-5">Città</div>
        <div class="text-gray-600">
            @if($record->citta)
                {{\App\Models\Comune::find($record->citta)->comuneConTarga()}}
            @endif
        </div>
        <div class="fw-bolder mt-5">Cap</div>
        <div class="text-gray-600">{{$record->cap}}</div>
    </div>
</div>
