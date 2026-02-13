<h3 class="card-title">Vodafone linea fissa</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'offerta','testo'=>'Offerta','required'=>true,'array'=>\App\Models\ProdottoVodafoneFissa::OFFERTE])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'tecnologia','testo'=>'Tecnologia','required'=>true,'array'=>\App\Models\ProdottoVodafoneFissa::TECNOLOGIE])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'metodo_pagamento','testo'=>'metodo_pagamento'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'numero_da_migrare','testo'=>'Numero da migrare'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'gestore_linea_esistente','testo'=>'Gestore linea esistente'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'codice_migrazione','testo'=>'Codice migrazione'])
    </div>
</div>
