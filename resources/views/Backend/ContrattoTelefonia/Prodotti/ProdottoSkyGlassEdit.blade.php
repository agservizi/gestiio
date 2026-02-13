<h3 class="card-title">Sky Glass</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'tipologia_cliente','testo'=>'Tipologia cliente','required'=>true,'array'=>\App\Models\ProdottoSkyGlass::TIPOLOGIA_CLIENTE,])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'dimensione','testo'=>'Dimensione','required'=>true,'array'=>\App\Models\ProdottoSkyGlass::DIMENSIONI])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'colore_sky_glass','testo'=>'Colore sky glass','required'=>true,'array'=>\App\Models\ProdottoSkyGlass::COLORI_GLASS])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'accessori','testo'=>'Accessori',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'colore_front_cover','testo'=>'Colore front cover','required'=>true,'array'=>\App\Models\ProdottoSkyGlass::COLORI_COVER])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'sky_stream','testo'=>'Dispositivi Sky stream','array'=>[0=>0,1=>1,2=>2]])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'installazione_a_muro','testo'=>'Installazione a muro',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'pacchetti_netflix','testo'=>'Pacchetti Netflix','required'=>true,'array'=>\App\Models\ProdottoSkyGlass::PACCHETTI_NETFLIX])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputCheckboxH',['campo'=>'pacchetti_sky','testo'=>'Pacchetti Sky','array'=>\App\Models\ProdottoSkyGlass::PACCHETTI_SKY_TV])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputCheckboxH',['campo'=>'servizi_opzionali','testo'=>'Servizi opzionali','array'=>\App\Models\ProdottoSkyGlass::SERVIZI_OPZIONALI])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'frequenza_pagamento_sky_glass','testo'=>'Frequenza pagamento sky glass','required'=>true,'array'=>\App\Models\ProdottoSkyGlass::FREQUENZA_PAGAMENTO,])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'metodo_pagamento_sky_glass','testo'=>'Metodo pagamento sky glass','required'=>true,'array'=>\App\Models\ProdottoSkyGlass::METODO_PAGAMENTO,'help'=>'<span class="text-danger">Inserire IBAN BANCARIO/POSTALE - NO POSTEPAY EVOLUTION dell\'intestatario dell\'abbonamento nei dati generali</span>'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputRadioH',['campo'=>'metodo_pagamento_tv','testo'=>'Metodo pagamento tv','array'=>\App\Models\ProdottoSkyGlass::METODO_PAGAMENTO_TV])
    </div>
</div>

