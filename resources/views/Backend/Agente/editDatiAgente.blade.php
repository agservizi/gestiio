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
            <h5 class="mb-1">Credenziali servizio visure (solo admin)</h5>
            <div class="text-muted mb-4">Queste credenziali sono gestite esclusivamente dal backoffice admin e usate per separare i costi per agente.</div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            @include('Backend._inputs.inputText',[
                'campo'=>'openapi_visure_token',
                'testo'=>'Credenziale visure agente',
                'autocomplete'=>'off',
                'required'=>false
            ])
        </div>
        <div class="col-md-6">
            @include('Backend._inputs.inputText',[
                'campo'=>'openapi_catasto_token',
                'testo'=>'Credenziale catasto agente',
                'autocomplete'=>'off',
                'required'=>false
            ])
        </div>
    </div>
@endif
