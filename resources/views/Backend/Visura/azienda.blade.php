<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'partita_iva','testo'=>'Partita iva','required'=>true,'autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'ragione_sociale','testo'=>'Ragione sociale','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo','testo'=>'Indirizzo'])
    </div>
</div>
