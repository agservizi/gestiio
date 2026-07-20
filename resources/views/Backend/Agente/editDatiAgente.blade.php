<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','autocomplete'=>'off'])
    </div>

</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'partita_iva','testo'=>'Partita iva','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'ragione_sociale','testo'=>'Ragione sociale','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include("Backend._inputs.inputSelect2",["campo"=>"citta","testo"=>"Città","required"=>true,"autocomplete"=>"off",'selected'=>\App\Models\Comune::selected(old('citta',$record->citta))])
    </div>
    <div class="col-md-6">
        @include("Backend._inputs.inputText",["campo"=>"cap","testo"=>"CAP","required"=>true,"autocomplete"=>"off"])
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        @include("Backend._inputs.inputText",["campo"=>"indirizzo","testo"=>"Indirizzo","required"=>true,"autocomplete"=>"off",'col'=>2])
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'iban','testo'=>'Iban','autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputSwitch',['campo'=>'paga_con_paypal','testo'=>'Paga con paypal',])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect',['campo'=>'listino_telefonia_id','testo'=>'Listino telefonia','array' => \App\Models\Listino::get()])
    </div>
    <div class="col-md-6">
    </div>
</div>

@if(Auth::user()->hasPermissionTo('admin'))
    <div class="separator my-6"></div>
    <div class="row">
        <div class="col-md-12">
            <h5 class="mb-1">Account Openapi Visengine (solo admin)</h5>
            <div class="text-muted mb-2">
                Credenziali dell’account Openapi <strong>dell’agente</strong> (email + API key dalla
                <a href="https://console.openapi.com/it" target="_blank" rel="noopener">console Openapi</a>)
                e Bearer scoped per Visengine/Catasto (console o
                <a href="https://github.com/openapi/openapi-cli" target="_blank" rel="noopener">openapi-cli</a>).
                Il wallet Openapi addebitato è quello dell’account agente: <strong>non è condiviso</strong> tra agenti.
            </div>
            <div class="alert alert-warning py-3 mb-4">
                Senza email + API key Openapi <em>e</em> token Bearer sul profilo, le nuove pratiche dell’agente
                finiscono in <strong>coda backoffice</strong> (lavorazione manuale admin).
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',[
                'campo'=>'openapi_email',
                'testo'=>'Email account Openapi',
                'autocomplete'=>'off',
                'required'=>false
            ])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',[
                'campo'=>'openapi_api_key',
                'testo'=>'API key account Openapi',
                'autocomplete'=>'off',
                'required'=>false
            ])
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',[
                'campo'=>'openapi_visure_token',
                'testo'=>'Token Visengine (Bearer)',
                'autocomplete'=>'off',
                'required'=>false
            ])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',[
                'campo'=>'openapi_catasto_token',
                'testo'=>'Token Catasto (Bearer)',
                'autocomplete'=>'off',
                'required'=>false
            ])
        </div>
    </div>
@endif
