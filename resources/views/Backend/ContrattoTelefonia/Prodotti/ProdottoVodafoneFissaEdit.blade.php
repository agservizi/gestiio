<h3 class="card-title">Vodafone linea fissa</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'offerta','testo'=>'Offerta','required'=>true,'array'=>\App\Models\ProdottoVodafoneFissa::OFFERTE])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'tecnologia','testo'=>'Tecnologia','required'=>true,'array'=>\App\Models\ProdottoVodafoneFissa::TECNOLOGIE])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'metodo_pagamento','testo'=>'Modalità pagamento','required'=>true,'array'=>\App\Models\ProdottoVodafoneFissa::METODO_PAGAMENTO,'help'=>'<span class="text-danger">il bollettino postale ha un costo di deposito cauzionale di 60€ applicabile sulla prima fattura</span>'])

    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'numero_da_migrare','testo'=>'Numero da migrare','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'gestore_linea_esistente','testo'=>'Gestore linea esistente','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_migrazione','testo'=>'Codice migrazione','autocomplete'=>'off'])
    </div>
</div>
