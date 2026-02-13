<h3 class="card-title">Dati generali</h3>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'codice_fiscale','testo'=>'Codice fiscale','required'=>true,'autocomplete'=>'off','classe'=>'uppercase'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'nome','testo'=>'Nome','required'=>true,'autocomplete'=>'off'])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cognome','testo'=>'Cognome','required'=>true,'autocomplete'=>'off'])
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'email','testo'=>'Email','autocomplete'=>'off','required'=>true])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cellulare','testo'=>'Cellulare','required'=>true,'autocomplete'=>'off'])
    </div>
</div>
<h3 class="card-title">Indirizzo</h3>

<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'indirizzo','testo'=>'Indirizzo','autocomplete'=>'off'])
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        @include('Backend._inputs.inputSelect2',['campo'=>'citta','testo'=>'Citta','selected'=>\App\Models\Comune::selected(old('citta',$record->citta))])
    </div>
    <div class="col-md-6">
        @include('Backend._inputs.inputText',['campo'=>'cap','testo'=>'Cap','autocomplete'=>'off'])
    </div>
</div>
