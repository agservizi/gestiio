<h3 class="card-title">Sky Glass</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'tipologia_cliente','testo'=>'Tipologia cliente','array'=>\App\Models\ProdottoSkyGlass::TIPOLOGIA_CLIENTE])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'dimensione','testo'=>'Dimensione','array'=>\App\Models\ProdottoSkyGlass::DIMENSIONI])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'colore_sky_glass','testo'=>'Colore sky glass','array'=>\App\Models\ProdottoSkyGlass::COLORI_GLASS])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'accessori','testo'=>'Accessori',])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'colore_front_cover','testo'=>'Colore front cover','array'=>\App\Models\ProdottoSkyGlass::COLORI_COVER])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'sky_stream','testo'=>'Dispositivi Sky stream'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'installazione_a_muro','testo'=>'Installazione a muro','valore'=>\App\siNo($record->installazione_a_muro)])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'pacchetti_netflix','testo'=>'Pacchetti Netflix','array'=>\App\Models\ProdottoSkyGlass::PACCHETTI_NETFLIX])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'pacchetti_sky','testo'=>'Pacchetti Sky','valore'=>implode(',',$record->pacchetti_sky??[])])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'servizi_opzionali','testo'=>'Servizi opzionali','array'=>\App\Models\ProdottoSkyGlass::SERVIZI_OPZIONALI])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'frequenza_pagamento_sky_glass','testo'=>'Frequenza pagamento sky glass','array'=>\App\Models\ProdottoSkyGlass::FREQUENZA_PAGAMENTO,])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'metodo_pagamento_sky_glass','testo'=>'Metodo pagamento sky glass','array'=>\App\Models\ProdottoSkyGlass::METODO_PAGAMENTO])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputTextReadonly',['campo'=>'metodo_pagamento_tv','testo'=>'Metodo pagamento tv','array'=>\App\Models\ProdottoSkyGlass::METODO_PAGAMENTO_TV])
    </div>
</div>

