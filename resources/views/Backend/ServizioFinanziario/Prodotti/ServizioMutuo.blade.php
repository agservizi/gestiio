<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'finalita','testo'=>'Finalità','required'=>true,'array'=>\App\arrayToKeyValue(\App\Models\ServizioMutuo::FINALIITA)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'tipo_di_tasso','testo'=>'Tipo di tasso','required'=>true,'array'=>\App\arrayToKeyValue(\App\Models\ServizioMutuo::TIPI_TASSO)])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'valore_immobile','testo'=>'Valore immobile','required'=>true,"classe"=>"autonumericImporto"])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'importo_del_mutuo','testo'=>'Importo del mutuo','required'=>true,"classe"=>"autonumericImporto"])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'durata','testo'=>'Durata','required'=>true,'array'=>\App\Models\ServizioMutuo::DURATA])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextDataMask',['campo'=>'data_di_nascita','testo'=>'Data di nascita','required'=>true,'autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'posizione_lavorativa','testo'=>'Posizione lavorativa','required'=>true,'array'=>\App\arrayToKeyValue(\App\Models\ServizioMutuo::POSIZIONE_LAVORATIVA)])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'reddito_richiedenti','testo'=>'Reddito richiedenti','required'=>true,"classe"=>"autonumericImporto"])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'comune_domicilio','testo'=>'Comune domicilio','required'=>true,'selected'=>\App\Models\Comune::selected(old('comune_domicilio',$record->comune_domicilio))])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'comune_immobile','testo'=>'Comune immobile','required'=>true,'selected'=>\App\Models\Comune::selected(old('comune_immobile',$record->comune_immobile))])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2KeyValue',['campo'=>'stato_ricerca_immobile','testo'=>'Stato ricerca immobile','required'=>true,'array'=>\App\arrayToKeyValue(\App\Models\ServizioMutuo::STATO_RICERCA)])
    </div>
</div>
@include('Backend._inputs_.allegati')
@push('customScript')
    <script src="/assets_backend/js-miei/select2_it.js"></script>
    <script src="/assets_backend/js-miei/autoNumeric.min.js"></script>
    <script>
        $(function () {
            eliminaHandler('Questa voce verrà eliminata definitivamente');
            autonumericImporto('autonumericImporto');
            select2Universale('comune_domicilio', 'un comune', 3, 'citta');
            select2Universale('comune_immobile', 'un comune', 3, 'citta');
        });
    </script>
@endpush
